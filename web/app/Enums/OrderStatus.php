<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Ready = 'ready';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جديد',
            self::Confirmed => 'مؤكَّد',
            self::Ready => 'جاهز للاستلام',
            self::Completed => 'تم',
            self::Cancelled => 'ملغي',
        };
    }

    /** Tone used by the status pill: green, amber, blue, neutral, red. */
    public function tone(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Confirmed => 'amber',
            self::Ready => 'green',
            self::Completed => 'neutral',
            self::Cancelled => 'red',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Confirmed, self::Ready], true);
    }

    /** @return list<self> */
    public static function open(): array
    {
        return [self::New, self::Confirmed, self::Ready];
    }
}
