<?php

namespace App\Enums;

enum Category: string
{
    case Ring = 'ring';
    case Band = 'band';
    case Necklace = 'necklace';
    case Set = 'set';
    case Bracelet = 'bracelet';
    case Earring = 'earring';
    case Bar = 'bar';
    case Coin = 'coin';

    public function label(): string
    {
        return match ($this) {
            self::Ring => 'خواتم',
            self::Band => 'دبل',
            self::Necklace => 'سلاسل',
            self::Set => 'أطقم',
            self::Bracelet => 'أساور',
            self::Earring => 'حلقان',
            self::Bar => 'سبائك',
            self::Coin => 'جنيهات ذهب',
        };
    }

    public function isBullion(): bool
    {
        return $this === self::Bar || $this === self::Coin;
    }

    /** The shop filter this category is listed under. */
    public function filter(): string
    {
        return match ($this) {
            self::Set => 'necklace',
            self::Coin => 'bullion',
            self::Bar => 'bullion',
            default => $this->value,
        };
    }

    /** Shop filters, in display order: slug => label. */
    public static function filters(): array
    {
        return [
            'ring' => 'خواتم',
            'band' => 'دبل',
            'necklace' => 'سلاسل وأطقم',
            'bracelet' => 'أساور',
            'earring' => 'حلقان',
            'bullion' => 'سبائك وعملات',
        ];
    }

    /** @return list<self> */
    public static function forFilter(string $filter): array
    {
        return array_values(array_filter(self::cases(), fn (self $c) => $c->filter() === $filter));
    }
}
