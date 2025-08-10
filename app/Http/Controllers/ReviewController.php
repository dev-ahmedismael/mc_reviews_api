<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->filled('per_page') ? (int) $request->per_page : 10;
        $search = $request->input('search');

        $translateValue = fn($value) => match ($value) {
            1 => 'غير راضي',
            2 => 'محايد',
            3 => 'راضي',
            4 => 'راضي جدا',
            default => 'غير محدد',
        };

        $reviews = Review::with(['branch', 'employee'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('branch', fn($q) => $q->where('name', 'like', "%$search%"))
                    ->orWhereHas('employee', fn($q) => $q->where('name', 'like', "%$search%"))
                    ->orWhere('notes', 'like', "%$search%");
            })
            ->latest()
            ->paginate($perPage);

        $formatted = $reviews->getCollection()->map(function ($review) use ($translateValue) {
            return [
                'branch_name' => optional($review->branch)->name,
                'employee_name' => optional($review->employee)->name,
                'value' => $translateValue($review->value),
                'notes' => $review->notes,
                'created_at' => $review->created_at->setTimezone('Asia/Riyadh')->toDateTimeString(),
            ];
        });

        return response()->json([
            'data' => $formatted,
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }


    public function review_position(string $id)
    {
        $reviews = Review::with(['branch', 'employee'])
            ->whereHas('employee', function ($query) use ($id) {
                $query->where('position_id', $id);
            })
            ->paginate(10);

        return response()->json($reviews);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReviewRequest $request)
    {
        $review = $request->validated();

        $employee = Employee::where('code', $request->input('employee_code'))->first();

        if (!$employee) {
            return response()->json(['message' => 'الموظف غير موجود أو الكود غير صحيح.'], 404);
        }

        if ($employee->branch_id !== (int) $request->input('branch_id')) {
            return response()->json([
                'message' => 'لا يمكن استخدام كود موظف من فرع مختلف.'
            ], 422);
        }

        $review['employee_id'] = $employee->id;

        try {
            Review::create($review);
            return response()->json(['message' => 'شكراً لتقييمك، نقدر ملاحظاتك!'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'حدث خطأ أثناء حفظ التقييم. حاول مرة أخرى لاحقاً.'], 500);
        }

        return response()->json(['message' => 'شكراً لتقييمك، نقدر ملاحظاتك!'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Review $review)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Review $review)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Review $review)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Review $review)
    {
        //
    }

    public function stats()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfYear = $now->copy()->startOfYear();

        // 1. Review Distribution (Current Month)
        $distribution = DB::table('reviews')
            ->select('value', DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $startOfMonth)
            ->groupBy('value')
            ->pluck('total', 'value');

        $totalReviews = $distribution->sum();
        $reviewLabels = [
            4 => 'راضي جداً',
            3 => 'راضي',
            2 => 'محايد',
            1 => 'غير راضي',
        ];

        $reviewDistribution = collect($reviewLabels)->map(function ($label, $value) use ($distribution, $totalReviews) {
            return [
                'label' => $label,
                'percentage' => $totalReviews ? round(($distribution[$value] ?? 0) / $totalReviews * 100, 1) : 0,
            ];
        })->values();

        // 2. Average Rating (Current Month)
        $averageRating = DB::table('reviews')
            ->where('created_at', '>=', $startOfMonth)
            ->avg('value');

        // 3. Average Rating per Branch (Current Month)
        $ratingPerBranch = DB::table('reviews')
            ->join('branches', 'reviews.branch_id', '=', 'branches.id')
            ->select('branches.name as branch', DB::raw('AVG(reviews.value) as avg_rating'))
            ->where('reviews.created_at', '>=', $startOfMonth)
            ->groupBy('reviews.branch_id', 'branches.name')
            ->get();

        // 4. Monthly Rating for Current Year (All Branches)
        $ratingPerMonth = DB::table('reviews')
            ->selectRaw('MONTH(created_at) as month, AVG(value) as avg_rating')
            ->where('created_at', '>=', $startOfYear)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month')
            ->get();

        // 5. Daily Review Count per Branch (Today)
        $today = Carbon::today();

        $branches = Branch::with(['reviews' => function ($query) use ($today) {
            $query->whereDate('created_at', $today);
        }])->get();

        $ratingsPerBranch = $branches->map(function ($branch) {
            $counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

            foreach ($branch->reviews as $review) {
                $counts[$review->value]++;
            }

            return [
                'branch' => $branch->name,
                '1' => $counts[1],
                '2' => $counts[2],
                '3' => $counts[3],
                '4' => $counts[4],
            ];
        });

        $employees = Employee::with(['reviews' => function ($query) use ($today) {
            $query->whereDate('created_at', $today);
        }])->get();

        $reviewsPerEmployee = $employees->map(function ($employee) {
            $counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

            foreach ($employee->reviews as $review) {
                $counts[$review->value]++;
            }

            return [
                'employee' => $employee->name,
                '1' => $counts[1],
                '2' => $counts[2],
                '3' => $counts[3],
                '4' => $counts[4],
            ];
        });


        return response()->json([
            'reviewDistribution' => $reviewDistribution,
            'averageRating' => round($averageRating, 2),
            'ratingPerBranch' => $ratingPerBranch,
            'ratingPerMonth' => $ratingPerMonth,
            'dailyReviewPerBranch' => $ratingsPerBranch,
            'dailyReviewPerEmployee' => $reviewsPerEmployee,
        ]);
    }
}
