<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Code 128 (set B) barcodes as inline SVG, for the shop's piece labels.
 * Any printable ASCII text works; the shop's own codes are 8 digits.
 */
class Code128
{
    /** Bar and space widths for each symbol value, in modules (bar first). */
    public const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    public const START_B = 104;

    public const STOP = 106;

    private const QUIET = 10;

    /** Symbol values: start, data, checksum, stop. @return list<int> */
    public static function symbols(string $text): array
    {
        if ($text === '' || preg_match('/[^\x20-\x7E]/', $text)) {
            throw new InvalidArgumentException('Code 128 B takes printable ASCII only.');
        }

        $values = array_map(fn (string $c) => ord($c) - 32, str_split($text));
        $sum = self::START_B;
        foreach ($values as $i => $v) {
            $sum += ($i + 1) * $v;
        }

        return [self::START_B, ...$values, $sum % 103, self::STOP];
    }

    /** Bar/space widths for the whole symbol, quiet zones excluded. @return list<int> */
    public static function widths(string $text): array
    {
        $widths = [];
        foreach (self::symbols($text) as $value) {
            foreach (str_split(self::PATTERNS[$value]) as $w) {
                $widths[] = (int) $w;
            }
        }

        return $widths;
    }

    public static function svg(string $text, int $height = 40): string
    {
        $x = self::QUIET;
        $rects = '';
        foreach (self::widths($text) as $i => $w) {
            if ($i % 2 === 0) {
                $rects .= '<rect x="'.$x.'" y="0" width="'.$w.'" height="'.$height.'"/>';
            }
            $x += $w;
        }
        $total = $x + self::QUIET;

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$total.' '.$height.'" preserveAspectRatio="none" shape-rendering="crispEdges" role="img" aria-label="'.e($text).'">'
            .'<rect width="100%" height="100%" fill="#fff"/><g fill="#000">'.$rects.'</g></svg>';
    }
}
