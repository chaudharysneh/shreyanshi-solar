<?php

namespace App\Policies;

use App\Models\Estimate;
use App\Models\User;
use App\Policies\Concerns\OwnsRecord;

class EstimatePolicy
{
    use OwnsRecord;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Estimate $estimate): bool
    {
        return $this->ownsRecord($user, $estimate);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Estimate $estimate): bool
    {
        return $this->ownsRecord($user, $estimate);
    }

    public function delete(User $user, Estimate $estimate): bool
    {
        return $this->ownsRecord($user, $estimate);
    }
}
