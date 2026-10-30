<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait OwnsRecord
{
    protected function ownsRecord(User $user, object $record): bool
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        foreach (['user_id', 'assigned_user_id', 'created_by'] as $column) {
            if (isset($record->{$column}) && (int) $record->{$column} === (int) $user->id) {
                return true;
            }
        }

        return false;
    }
}
