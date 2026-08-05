<?php
namespace App\Enums;

enum ExpenseCategory: string
{
    case Food = 'Food';
    case Transport = 'Transport';
    case Rent = 'Rent';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}