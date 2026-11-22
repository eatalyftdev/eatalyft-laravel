<?php

namespace App\Policies;

use App\Models\User;

class BusBookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bus.bookings.read');
    }

    public function view(User $user, mixed $booking): bool
    {
        if (!$user->can('bus.bookings.read')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $booking->agency_id;
        }

        if ($user->hasRole('branch_manager') || $user->hasRole('ticket_agent')) {
            return $user->branch_id === $booking->branch_id;
        }

        if ($user->hasRole('support_agent')) {
            return true;
        }

        if ($user->hasRole('customer') || $user->hasRole('creator')) {
            return $user->id === $booking->user_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('bus.bookings.write');
    }

    public function update(User $user, mixed $booking): bool
    {
        if (!$user->can('bus.bookings.write')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $booking->agency_id;
        }

        if ($user->hasRole('branch_manager') || $user->hasRole('ticket_agent')) {
            return $user->branch_id === $booking->branch_id;
        }

        return false;
    }

    public function confirm(User $user, mixed $booking): bool
    {
        if (!$user->can('bus.bookings.confirm')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $booking->agency_id;
        }

        if ($user->hasRole('branch_manager') || $user->hasRole('ticket_agent')) {
            return $user->branch_id === $booking->branch_id;
        }

        return false;
    }

    public function cancel(User $user, mixed $booking): bool
    {
        if (!$user->can('bus.bookings.cancel')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $booking->agency_id;
        }

        if ($user->hasRole('branch_manager') || $user->hasRole('ticket_agent')) {
            return $user->branch_id === $booking->branch_id;
        }

        if ($user->hasRole('customer') || $user->hasRole('creator')) {
            return $user->id === $booking->user_id && $booking->status === 'requested';
        }

        return false;
    }
}