<?php

namespace App\Enums;

enum PieceStatus: string
{
    case InStock = 'in_stock';
    case Sold = 'sold';

    public function label(): string
    {
        return match ($this) {
            self::InStock => 'في المخزون',
            self::Sold => 'اتباعت',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::InStock => 'green',
            self::Sold => 'neutral',
        };
    }
}
