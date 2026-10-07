<?php

namespace App\Enums;

enum LineKind: string
{
    case Sale = 'sale';
    case Purchase = 'purchase';
}
