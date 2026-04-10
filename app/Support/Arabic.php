<?php

namespace App\Support;

class Arabic
{
    /**
     * Normalize Arabic text for fuzzy matching.
     *
     * - Strips tashkeel (harakat), tatweel, and dagger alef
     * - Unifies alef variants (أ إ آ ٱ) → ا
     * - ة → ه, ى → ي, ئ → ي, ؤ → و
     * - Drops bare hamza (ء)
     * - Collapses whitespace and lowercases Latin characters
     */
    public static function normalize(?string $input): string
    {
        if ($input === null || $input === '') {
            return '';
        }

        // Drop tashkeel (U+064B–U+0652), dagger alef (U+0670), tatweel (U+0640)
        $input = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $input);

        $input = strtr($input, [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ٱ' => 'ا',
            'ة' => 'ه',
            'ى' => 'ي',
            'ئ' => 'ي',
            'ؤ' => 'و',
            'ء' => '',
        ]);

        $input = preg_replace('/\s+/u', ' ', trim($input));

        return mb_strtolower($input, 'UTF-8');
    }
}
