<?php


namespace App\Exports;

use App\Models\Review;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReviewsByPositionExport implements FromCollection, WithHeadings
{
    protected $positionId;

    public function __construct($positionId)
    {
        $this->positionId = $positionId;
    }

    public function collection()
    {
        return Review::with(['branch', 'employee'])
            ->whereHas('employee', function ($query) {
                $query->where('position_id', $this->positionId);
            })
            ->get()
            ->map(function ($review) {
                return [
                    'Branch'   => $review->branch->name ?? '',
                    'Employee' => $review->employee->name ?? '',
                    'Value'    => $review->value,
                    'Notes'    => $review->notes,
                    'Date'     => $review->created_at->format('Y-m-d'),
                ];
            });
    }

    public function headings(): array
    {
        return ['Branch', 'Employee', 'Value', 'Notes', 'Date'];
    }
}
