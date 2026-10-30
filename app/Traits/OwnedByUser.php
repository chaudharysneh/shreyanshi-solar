<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait OwnedByUser
{
    protected static array $ownedByUserColumnCache = [];

    public static function bootOwnedByUser(): void
    {
        static::creating(function ($model) {
            $userId = request()->user()?->id;

            if (
                $userId
                && static::hasOwnedByUserColumn($model)
                && empty($model->user_id)
            ) {
                $model->user_id = $userId;
            }
        });

        static::addGlobalScope('owned_by_user', function (Builder $builder) {
            $model = $builder->getModel();
            $table = $model->getTable();

            $hasUserIdCol    = static::hasOwnedByUserColumn($model);
            $hasCreatedByCol = Schema::hasColumn($table, 'created_by');
            $hasAssignedCol  = Schema::hasColumn($table, 'assigned_user_id');

            if (!$hasUserIdCol && !$hasCreatedByCol && !$hasAssignedCol) {
                return;
            }

            $user = request()->user();

            if (!$user) {
                return;
            }

            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return;
            }

            $userId = $user->id;

            $builder->where(function (Builder $scoped) use ($model, $userId, $hasCreatedByCol, $hasAssignedCol, $hasUserIdCol) {
                $conditions = [];

                if ($hasCreatedByCol) {
                    $conditions[] = [$model->qualifyColumn('created_by'), $userId];
                }
                if ($hasAssignedCol) {
                    $conditions[] = [$model->qualifyColumn('assigned_user_id'), $userId];
                }
                if ($hasUserIdCol) {
                    $conditions[] = [$model->qualifyColumn('user_id'), $userId];
                }

                foreach ($conditions as $i => [$col, $val]) {
                    if ($i === 0) {
                        $scoped->where($col, $val);
                    } else {
                        $scoped->orWhere($col, $val);
                    }
                }
            });
        });
    }

    public static function supportsOwnedByUserColumn(): bool
    {
        return static::hasOwnedByUserColumn(new static());
    }

    public static function syncOwnedUserFromAssignee($model): void
    {
        $userId = request()->user()?->id;

        if (!static::hasOwnedByUserColumn($model)) {
            return;
        }

        if (!empty($model->assigned_user_id)) {
            $model->user_id = $model->assigned_user_id;
        } elseif (empty($model->user_id) && $userId) {
            $model->user_id = $userId;
        }
    }

    protected static function hasOwnedByUserColumn($model): bool
    {
        $table = $model->getTable();

        if (!array_key_exists($table, static::$ownedByUserColumnCache)) {
            static::$ownedByUserColumnCache[$table] = Schema::hasColumn($table, 'user_id');
        }

        return static::$ownedByUserColumnCache[$table];
    }
}
