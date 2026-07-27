<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Single source of truth for a company's visual identity.
 *
 * The platform stores no logo asset: {@see Company::getFaviconUrlAttribute()}
 * resolves the mark from the website's host through a public favicon CDN, so
 * backfilling `companies.website` is what puts a logo on the card.
 *
 * One thing about that mark cannot be derived, only measured: whether the CDN
 * has an icon for the host at all. It answers 404 when it does not — but with a
 * grey globe in the body, which browsers happily render, so the client never
 * sees a failure. That is why `companies.has_logo` exists and why
 * {@see probeLogo()} is a network call rather than a rule.
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
     * A host the CDN is certain to have no icon for.
     *
     * Used to capture the placeholder artwork; see {@see placeholderIcon()}.
     */
    private const UNKNOWN_HOST = 'domain.invalid';

    /** Seconds to wait on a single icon fetch before giving up. */
    private const PROBE_TIMEOUT = 10;

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

        return 'https://www.google.com/s2/favicons?domain='.self::host($website).'&sz=128';
    }

    /**
     * Whether the CDN serves a real favicon for this website, or null when the
     * question could not be answered.
     *
     * The CDN answers 404 for a host it has no icon for — but with a decodable
     * image in the body, a grey globe. A browser renders that body and fires
     * `load`, not `error`, which is why the avatar's own fallback cannot catch
     * it and why this has to be asked server-side.
     *
     * Status is therefore the signal. `$placeholder` is a belt-and-braces
     * second one for the day the CDN starts answering 200 with the same globe:
     * pass the bytes from {@see placeholderIcon()} and a body-identical
     * response counts as "no icon" too.
     *
     * Null is not "no": a timeout must leave {@see Company::$has_logo} alone
     * rather than demote a company that does have a logo. Callers persist only
     * a definite answer.
     */
    public static function probeLogo(?string $website, ?string $placeholder = null): ?bool
    {
        if (blank($website)) {
            return false;
        }

        $response = self::fetchIcon(self::host($website));

        if ($response === null) {
            return null;
        }

        if (! $response->successful()) {
            return false;
        }

        return $placeholder === null || $response->body() !== $placeholder;
    }

    /**
     * The artwork the CDN serves for a host it has no icon for, or null if it
     * could not be fetched. Fetch it once per run and hand it to
     * {@see probeLogo()} — asking beats hard-coding a checksum that would go
     * stale the day the artwork changes.
     */
    public static function placeholderIcon(): ?string
    {
        return self::fetchIcon(self::UNKNOWN_HOST)?->body();
    }

    /** The CDN's response for a host, or null if the request never completed. */
    private static function fetchIcon(string $host): ?Response
    {
        try {
            return Http::timeout(self::PROBE_TIMEOUT)
                ->get('https://www.google.com/s2/favicons', ['domain' => $host, 'sz' => 128]);
        } catch (Throwable) {
            return null;
        }
    }

    private static function host(string $website): string
    {
        return parse_url($website, PHP_URL_HOST) ?: $website;
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
