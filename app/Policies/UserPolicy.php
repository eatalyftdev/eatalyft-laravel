<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.read');
    }

    public function view(User $user, User $targetUser): bool
    {
        if (!$user->can('users.read')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin') || $user->hasRole('support_agent') || $user->hasRole('kyc_reviewer')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $targetUser->agency_id === $user->agency_id;
        }

        if ($user->hasRole('branch_manager')) {
            return $targetUser->branch_id === $user->branch_id;
        }

        if ($user->hasRole('customer') || $user->hasRole('creator')) {
            return $user->id === $targetUser->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('users.write');
    }

    public function update(User $user, User $targetUser): bool
    {
        if (!$user->can('users.write')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $targetUser->agency_id === $user->agency_id;
        }

        if ($user->hasRole('branch_manager')) {
            return $targetUser->branch_id === $user->branch_id;
        }

        if ($user->hasRole('customer') || $user->hasRole('creator')) {
            return $user->id === $targetUser->id;
        }

        return false;
    }

    public function delete(User $user, User $targetUser): bool
    {
        return $user->hasRole('superadmin') || $user->hasRole('admin');
    }
}