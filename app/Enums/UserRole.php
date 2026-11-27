<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'superadmin';
    case Admin = 'admin';
    case Accountant = 'accountant';
    case Dispatch = 'dispatch';
    case AgencyOwner = 'agency_owner';
    case SubAgent = 'sub_agent';
    case Influencer = 'influencer';
    case Creator = 'creator';
    case Driver = 'driver';
    case Agent = 'agent';
    case Handyman = 'handyman';
    case Nanny = 'nanny';
    case Maid = 'maid';
    case Hotel = 'hotel';
    case Vendor = 'vendor';
    case Customer = 'customer';
    case User = 'user';

    public static function staffRoles(): array
    {
        return [
            self::SuperAdmin, self::Admin, self::Accountant, self::Dispatch,
            self::AgencyOwner, self::SubAgent,
        ];
    }

    public static function requiresActiveRoles(): array
    {
        return [
            self::Driver, self::Agent, self::Handyman, self::Nanny, self::Maid,
            self::Hotel, self::Vendor, self::Influencer, self::Creator, self::Customer,
        ];
    }

    public function homeRoute(): string
    {
        return match ($this) {
            self::SuperAdmin, self::Admin => '/admin',
            self::Accountant => '/admin/finance',
            self::Dispatch => '/dispatch',
            self::AgencyOwner => '/agency',
            self::SubAgent => '/agency/tickets',
            self::Influencer, self::Creator => '/creator',
            self::Driver => '/driver',
            self::Agent => '/agent',
            self::Handyman => '/handyman',
            self::Nanny, self::Maid => '/nanny',
            self::Hotel => '/hotel',
            self::Vendor => '/vendor',
            self::Customer, self::User => '/home',
        };
    }
}