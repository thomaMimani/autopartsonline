<?php

namespace App\Support;

final class PartNumber
{
    /**
     * Comparable form of a part number: uppercase, no spaces or punctuation.
     * "0 986 494 123" -> "0986494123", "W 712/75" -> "W71275", "13.0460-7184.2" -> "13046071842".
     */
    public static function normalize(?string $value): string
    {
        $value = mb_strtoupper(trim((string) $value));

        return preg_replace('/[^A-Z0-9]/u', '', $value) ?? '';
    }

    /**
     * Heuristic for VAG-style OE numbers such as 1K0698151 or 5Q0615301AA:
     * digit, letter/digit, digit, then 6 digits, optional 1-2 letter suffix.
     * Only used to decide whether to try the OE path in the matching ladder.
     */
    public static function looksLikeOe(string $normalized): bool
    {
        return (bool) preg_match('/^[0-9][A-Z0-9][0-9][0-9]{6}[A-Z]{0,2}$/', $normalized)
            || (bool) preg_match('/^A[0-9]{10}$/', $normalized); // Mercedes A-numbers
    }
}
