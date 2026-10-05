<?php

namespace App\Enums;

enum OrderType: string
{
    case Bullion = 'bullion';
    case Reservation = 'reservation';
    case Sell = 'sell';

    public function label(): string
    {
        return match ($this) {
            self::Bullion => 'طلب سبائك',
            self::Reservation => 'حجز قطعة',
            self::Sell => 'معاد بيع ذهب',
        };
    }
}
