<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Completed = 'completed';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'سليمة',
            self::Voided => 'ملغاة',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Completed => 'green',
            self::Voided => 'red',
        };
    }
}
