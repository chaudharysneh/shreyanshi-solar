<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Schema;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Scope a query to only include records owned by the current user, 
     * unless the user is an administrator.
     */
    protected function scopeOwnedRecords($query)
    {
        $user = auth()->user();
        if (!$user || $user->isAdmin()) {
            return $query;
        }

        $model = $query->getModel();

        // Use visibleTo scope if available (e.g. on Customer model)
        if (method_exists($model, 'scopeVisibleTo')) {
            return $query->visibleTo($user);
        }

        $table = $model->getTable();
        $columns = [];

        foreach (['created_by', 'assigned_user_id', 'user_id'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                $columns[] = $model->qualifyColumn($column);
            }
        }

        if (!empty($columns)) {
            return $query->where(function ($scoped) use ($columns, $user) {
                foreach ($columns as $index => $column) {
                    if ($index === 0) {
                        $scoped->where($column, $user->id);
                    } else {
                        $scoped->orWhere($column, $user->id);
                    }
                }
            });
        }

        // For other models, if they use the OwnedByUser trait,
        // the global scope is already applied automatically.
        return $query;
    }
}
