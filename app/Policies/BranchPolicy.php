<?php

namespace App\Policies;

use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('agencies.branches.read');
    }

    public function view(User $user, mixed $branch): bool
    {
        if (!$user->can('agencies.branches.read')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $branch->agency_id;
        }

        if ($user->hasRole('branch_manager')) {
            return $user->branch_id === $branch->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('agencies.branches.write') && ($user->hasRole('superadmin') || $user->hasRole('admin') || $user->hasRole('agency_manager'));
    }

    public function update(User $user, mixed $branch): bool
    {
        if (!$user->can('agencies.branches.write')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $branch->agency_id;
        }

        if ($user->hasRole('branch_manager')) {
            return $user->branch_id === $branch->id;
        }

        return false;
    }

    public function delete(User $user, mixed $branch): bool
    {
        return $user->hasRole('superadmin') || $user->hasRole('admin');
    }
}