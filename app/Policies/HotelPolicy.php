<?php

namespace App\Policies;

use App\Models\User;

class HotelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('hotels.read');
    }

    public function view(User $user, mixed $hotel): bool
    {
        if (!$user->can('hotels.read')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $hotel->agency_id;
        }

        if ($user->hasRole('hotel_manager')) {
            return $user->id === $hotel->manager_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('hotels.write') && ($user->hasRole('superadmin') || $user->hasRole('admin') || $user->hasRole('agency_manager'));
    }

    public function update(User $user, mixed $hotel): bool
    {
        if (!$user->can('hotels.write')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $hotel->agency_id;
        }

        if ($user->hasRole('hotel_manager')) {
            return $user->id === $hotel->manager_id;
        }

        return false;
    }

    public function delete(User $user, mixed $hotel): bool
    {
        return $user->hasRole('superadmin') || $user->hasRole('admin');
    }
}