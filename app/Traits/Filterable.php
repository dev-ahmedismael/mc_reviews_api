<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

trait Filterable
{
    public function scopeFilter($query, Request $request)
    {
         if ($request->has('filter')) {
            foreach ($request->query('filter') as $field => $condition) {
                list($operator, $value) = explode(',', $condition, 2);

                if (!empty($value)) {
                    $columnType = Schema::getColumnType($query->getModel()->getTable(), $field);

                    // Handle Date Fields
                    if ($columnType === 'date' || $columnType === 'datetime') {
                        $query->whereDate($field, $operator, $value);
                    }
                    // Handle String Fields with LIKE
                    elseif ($operator === 'LIKE') {
                        $query->where($field, 'LIKE', "%{$value}%");
                    }
                    // Handle All Other Fields
                    else {
                        $query->where($field, $operator, $value);
                    }
                }
            }
        }

         if ($request->has('sort')) {
            foreach ($request->query('sort') as $field => $direction) {
                $query->orderBy($field, $direction);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }
}
