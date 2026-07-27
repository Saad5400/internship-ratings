<?php

namespace App\Support;

use App\Models\Company;

/**
 * Single source of truth for a company's visual identity.
 *
 * The platform stores no logo asset and has no logo column: {@see Company::getFaviconUrlAttribute()}
 * resolves the mark from the website's host through a public favicon CDN. A
 * company's logo is therefore a pure function of its website, which makes
 * backfilling `companies.website` the only lever there is — fill it and the
 * avatar stops being a bare initial glyph.
 */
final class CompanyBranding
{
    /**
     * A domain was derived from the name. It is a *guess*: deterministic, but
     * unverified unless the caller checked it over the network.
     */
    public const DERIVED = 'derived';

    /** The name is Arabic-only, so there is no Latin token to build a domain from. */
    public const NO_LATIN_NAME = 'no-latin-name';

    /** The Latin part is a description ("Business Research and Development"), not a brand. */
    public const TOO_GENERIC = 'too-generic';

    /**
     * Corporate boilerplate that never belongs in a domain guess. Dropped
     * before the remaining words are joined.
     *
     * @var list<string>
     */
    private const FILLER = [
        'company', 'co', 'ltd', 'limited', 'llc', 'lc', 'inc', 'incorporated',
        'group', 'holding', 'holdings', 'est', 'establishment', 'the', 'and', 'for', 'of',
    ];

    /**
     * Beyond this many significant Latin words the name is a sentence rather
     * than a brand, and concatenating it produces a domain nobody registered.
     */
    private const MAX_WORDS = 3;

    /**
     * Derive a candidate website from a company name, or explain why it can't be.
     *
     * Deterministic and offline — the same name always yields the same answer,
     * and no lookup is performed. Callers that need certainty verify the result
     * themselves (see the `--verify` flag on the backfill command).
     *
     * @return array{website: ?string, reason: string}
     */
    public static function deriveWebsite(string $name): array
    {
        $latin = self::latinSegment($name);

        if ($latin === null) {
            return ['website' => null, 'reason' => self::NO_LATIN_NAME];
        }

        $words = self::significantWords($latin);

        if ($words === [] || count($words) > self::MAX_WORDS) {
            return ['website' => null, 'reason' => self::TOO_GENERIC];
        }

        return [
            'website' => 'https://'.implode('', $words).'.com',
            'reason' => self::DERIVED,
        ];
    }

    /**
     * The public logo URL for a website, or null when there is no website.
     *
     * No asset is ever stored server-side; the CDN resolves the host's favicon
     * at request time and the avatar component hides the image on error.
     */
    public static function logoUrl(?string $website): ?string
    {
        if (blank($website)) {
            return null;
        }

        $host = parse_url($website, PHP_URL_HOST) ?: $website;

        return "https://www.google.com/s2/favicons?domain={$host}&sz=128";
    }

    /**
     * The brandable Latin part of a company name.
     *
     * Arabic names routinely carry the brand in parentheses — "شركة الورق
     * العربية (Waraq)" — so that wins when present; otherwise the name is only
     * usable when it is entirely Latin to begin with.
     */
    private static function latinSegment(string $name): ?string
    {
        if (preg_match('/\(([^)]+)\)/u', $name, $matches) === 1 && self::isLatin($matches[1])) {
            return $matches[1];
        }

        return self::isLatin($name) ? $name : null;
    }

    /**
     * Latin here means "has a Latin letter and no Arabic one" — a mixed string
     * is ambiguous and treated as Arabic.
     */
    private static function isLatin(string $value): bool
    {
        return preg_match('/\p{Arabic}/u', $value) !== 1
            && preg_match('/[A-Za-z]/', $value) === 1;
    }

    /**
     * Lowercase alphanumeric words with the corporate filler removed.
     *
     * @return list<string>
     */
    private static function significantWords(string $latin): array
    {
        $words = preg_split('/[^a-z0-9]+/', mb_strtolower($latin), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_diff($words, self::FILLER));
    }
}
