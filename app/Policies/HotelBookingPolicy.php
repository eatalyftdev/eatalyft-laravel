<?php

namespace App\Policies;

use App\Models\User;

class HotelBookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('hotels.bookings.read');
    }

    public function view(User $user, mixed $booking): bool
    {
        if (!$user->can('hotels.bookings.read')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $booking->hotel->agency_id;
        }

        if ($user->hasRole('hotel_manager')) {
            return $user->id === $booking->hotel->manager_id;
        }

        if ($user->hasRole('support_agent')) {
            return true;
        }

        if ($user->hasRole('branch_manager')) {
            return $user->branch_id === $booking->hotel->branch_id;
        }

        if ($user->hasRole('customer') || $user->hasRole('creator')) {
            return $user->id === $booking->user_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('hotels.bookings.write');
    }

    public function update(User $user, mixed $booking): bool
    {
        if (!$user->can('hotels.bookings.write')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $booking->hotel->agency_id;
        }

        if ($user->hasRole('hotel_manager')) {
            return $user->id === $booking->hotel->manager_id;
        }

        if ($user->hasRole('branch_manager')) {
            return $user->branch_id === $booking->hotel->branch_id;
        }

        return false;
    }

    public function confirm(User $user, mixed $booking): bool
    {
        if (!$user->can('hotels.bookings.confirm')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $booking->hotel->agency_id;
        }

        if ($user->hasRole('hotel_manager')) {
            return $user->id === $booking->hotel->manager_id;
        }

        if ($user->hasRole('branch_manager')) {
            return $user->branch_id === $booking->hotel->branch_id;
        }

        return false;
    }

    public function cancel(User $user, mixed $booking): bool
    {
        if (!$user->can('hotels.bookings.cancel')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $booking->hotel->agency_id;
        }

        if ($user->hasRole('hotel_manager')) {
            return $user->id === $booking->hotel->manager_id;
        }

        if ($user->hasRole('branch_manager')) {
            return $user->branch_id === $booking->hotel->branch_id;
        }

        if ($user->hasRole('customer') || $user->hasRole('creator')) {
            return $user->id === $booking->user_id && in_array($booking->status, ['requested', 'held']);
        }

        return false;
    }
}