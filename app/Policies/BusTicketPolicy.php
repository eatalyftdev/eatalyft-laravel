<?php

namespace App\Policies;

use App\Models\User;

class BusTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bus.tickets.read');
    }

    public function view(User $user, mixed $ticket): bool
    {
        if (!$user->can('bus.tickets.read')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $ticket->agency_id;
        }

        if ($user->hasRole('branch_manager') || $user->hasRole('ticket_agent')) {
            return $user->branch_id === $ticket->branch_id;
        }

        if ($user->hasRole('customer') || $user->hasRole('creator')) {
            return $user->id === $ticket->passenger_user_id || $user->id === $ticket->sold_by_user_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('bus.tickets.write');
    }

    public function update(User $user, mixed $ticket): bool
    {
        if (!$user->can('bus.tickets.write')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $ticket->agency_id;
        }

        if ($user->hasRole('branch_manager') || $user->hasRole('ticket_agent')) {
            return $user->branch_id === $ticket->branch_id;
        }

        return false;
    }

    public function cancel(User $user, mixed $ticket): bool
    {
        if (!$user->can('bus.tickets.cancel')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agency_manager')) {
            return $user->agency_id === $ticket->agency_id;
        }

        if ($user->hasRole('branch_manager') || $user->hasRole('ticket_agent')) {
            return $user->branch_id === $ticket->branch_id;
        }

        return false;
    }

    public function scan(User $user, mixed $ticket): bool
    {
        if (!$user->can('bus.tickets.scan')) {
            return false;
        }

        if ($user->hasRole('superadmin') || $user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('ticket_scanner') || $user->hasRole('branch_manager') || $user->hasRole('ticket_agent')) {
            return $user->branch_id === $ticket->trip->branch_id ?? $ticket->branch_id;
        }

        return false;
    }
}