<?php

namespace App\Enums;

/** How an invoice was settled, as on the shop's paper invoice. */
enum Settlement: string
{
    case Cash = 'cash';
    case Exchange = 'exchange';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'كاش',
            self::Exchange => 'استبدال',
            self::Transfer => 'تحويل بنكي',
        };
    }
}
