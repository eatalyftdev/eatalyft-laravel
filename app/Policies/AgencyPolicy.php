<?php

namespace App\Policies;

use App\Models\User;

class AgencyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('agencies.read');
    }

    public function view(User $user, mixed $agency): bool
    {
        if (!$user->can('agencies.read')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $agency->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('agencies.write') && ($user->hasRole('superadmin') || $user->hasRole('admin'));
    }

    public function update(User $user, mixed $agency): bool
    {
        if (!$user->can('agencies.write')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $agency->id;
        }

        return false;
    }

    public function delete(User $user, mixed $agency): bool
    {
        return $user->hasRole('superadmin') || $user->hasRole('admin');
    }
}