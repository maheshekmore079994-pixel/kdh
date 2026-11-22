<?php

namespace App\Enums;

enum Permission: string
{
    case Billing = 'billing';
    case Jobs = 'jobs';
    case Customers = 'customers';
    case Expenses = 'expenses';
    case Purchases = 'purchases';
    case Inventory = 'inventory';
    case Reports = 'reports';
    case Website = 'website';
    case Settings = 'settings';
    case Users = 'users';

    public function label(): string
    {
        return match ($this) {
            self::Billing => 'Billing & payments',
            self::Jobs => 'Jobs',
            self::Customers => 'Customers',
            self::Expenses => 'Expenses',
            self::Purchases => 'Purchases & suppliers',
            self::Inventory => 'Inventory & stock',
            self::Reports => 'Reports & accounts',
            self::Website => 'Website & enquiries',
            self::Settings => 'Settings',
            self::Users => 'User management',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Billing => 'Create bills, record and reverse payments, cancel invoices.',
            self::Jobs => 'Create jobs, update status, print job receipts.',
            self::Customers => 'Add and edit customers, view history.',
            self::Expenses => 'Record and manage shop expenses.',
            self::Purchases => 'Record supplier purchases and supplier payments.',
            self::Inventory => 'Manage products and stock adjustments.',
            self::Reports => 'View and export accounts and reports.',
            self::Website => 'Edit website content and handle enquiries.',
            self::Settings => 'Change business settings, catalogue and prices.',
            self::Users => 'Create users and assign permissions.',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }

    /** Default permission set for a new operator. */
    public static function operatorDefaults(): array
    {
        return [self::Billing->value, self::Jobs->value, self::Customers->value];
    }
}
