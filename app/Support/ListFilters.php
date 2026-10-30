<?php

namespace App\Support;

use Illuminate\Http\Request;

class ListFilters
{
    public static function apply($query, Request $request, array $exact, array $dates, array $search = []): void
    {
        $rules = ['per_page' => ['nullable', 'integer', 'in:10,25,50,100']];
        foreach (array_merge($exact, $search) as $column) {
            $rules[$column] = str_ends_with($column, '_id')
                ? ['nullable', 'integer', 'min:1']
                : ['nullable', 'string', 'max:100'];
        }
        foreach ($dates as $column) {
            $rules[$column . '_from'] = ['nullable', 'date_format:Y-m-d'];
            $rules[$column . '_to'] = ['nullable', 'date_format:Y-m-d', 'after_or_equal:' . $column . '_from'];
        }
        $request->validate($rules);
        foreach ($exact as $column) {
            if ($request->filled($column)) $query->where($column, $request->input($column));
        }
        foreach ($search as $column) {
            if ($request->filled($column)) $query->where($column, 'like', '%' . $request->input($column) . '%');
        }
        foreach ($dates as $column) {
            if ($request->filled($column . '_from')) $query->whereDate($column, '>=', $request->input($column . '_from'));
            if ($request->filled($column . '_to')) $query->whereDate($column, '<=', $request->input($column . '_to'));
        }
    }
}
