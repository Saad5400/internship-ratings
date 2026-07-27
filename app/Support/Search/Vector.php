<?php

namespace App\Support\Search;

/**
 * Vector storage and math for the search index.
 *
 * Vectors are persisted as base64 of packed little-endian float32 rather than a
 * binary column: SQLite (local) and Postgres (deployed) disagree about how
 * BLOB/BYTEA round-trips through PDO, and a text column behaves identically on
 * both. The 33% base64 overhead is cheaper than that portability problem.
 */
final class Vector
{
    /** Scale a vector to unit length so cosine similarity reduces to a dot product. */
    public static function normalize(array $vector): array
    {
        $sum = 0.0;

        foreach ($vector as $value) {
            $sum += $value * $value;
        }

        if ($sum <= 0.0) {
            return $vector;
        }

        $magnitude = sqrt($sum);

        return array_map(static fn (float $value): float => $value / $magnitude, $vector);
    }

    /** @param  list<float>  $vector */
    public static function encode(array $vector): string
    {
        return base64_encode(pack('g*', ...$vector));
    }

    /** @return list<float> */
    public static function decode(string $encoded): array
    {
        $binary = base64_decode($encoded, true);

        if ($binary === false || $binary === '') {
            return [];
        }

        $unpacked = unpack('g*', $binary);

        return $unpacked === false ? [] : array_values($unpacked);
    }

    /**
     * Cosine similarity between a query vector and a stored, packed vector.
     *
     * Both sides are already unit length, so this is a plain dot product. It
     * runs against the whole matrix on every search, so it decodes straight off
     * the packed string instead of materialising an intermediate PHP array.
     *
     * @param  list<float>  $query
     */
    public static function similarity(array $query, string $encoded): float
    {
        $binary = base64_decode($encoded, true);

        if ($binary === false) {
            return 0.0;
        }

        $length = min(count($query), intdiv(strlen($binary), 4));

        if ($length === 0) {
            return 0.0;
        }

        $stored = unpack("g{$length}", $binary);

        if ($stored === false) {
            return 0.0;
        }

        $dot = 0.0;

        for ($i = 1; $i <= $length; $i++) {
            $dot += $query[$i - 1] * $stored[$i];
        }

        return $dot;
    }
}
