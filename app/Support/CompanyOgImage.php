<?php

namespace App\Support;

use App\Http\Controllers\OgImageController;
use App\Models\Company;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Renders and caches a company's Open Graph card.
 *
 * The card itself is drawn by scripts/og-company.mjs with the Takumi renderer
 * (see that file); this class owns everything on the PHP side: the payload the
 * script reads, the on-disk cache keyed to the data the card shows, and the
 * one-shot node invocation on a miss.
 *
 * The cache key is a hash of the visible facts (name, type, score, count,
 * host), so a card is regenerated exactly when what it would show changes — a
 * new approved rating shifts the average, the file name changes, the next
 * request re-renders. Stale variants for the same company are swept on write.
 *
 * Rendering is best-effort: any failure (node missing, script error, timeout)
 * returns null so {@see OgImageController} can fall back
 * to the static card rather than 500 a crawler.
 */
final class CompanyOgImage
{
    /** Seconds to wait on a single render before giving up. */
    private const RENDER_TIMEOUT = 30;

    /**
     * A readable path to the company's cached card, rendering it first on a
     * miss, or null when rendering failed.
     */
    public function ensure(Company $company): ?string
    {
        $path = $this->path($company);

        if (is_readable($path)) {
            return $path;
        }

        try {
            $png = $this->render($this->payload($company));
        } catch (Throwable) {
            return null;
        }

        File::ensureDirectoryExists(dirname($path));

        // Sweep older variants for this company so a data change never leaves a
        // stale card behind, then write atomically so a concurrent request
        // never serves a half-written file.
        foreach (File::glob($this->directory().'/company-'.$company->getKey().'-*.png') as $stale) {
            if ($stale !== $path) {
                File::delete($stale);
            }
        }

        $temp = $path.'.'.getmypid().'.tmp';
        File::put($temp, $png);
        File::move($temp, $path);

        return $path;
    }

    /**
     * The content hash of the company's card — a stable ETag that changes only
     * when the rendered card would.
     */
    public function etag(Company $company): string
    {
        return $this->key($company);
    }

    /**
     * The payload scripts/og-company.mjs reads from stdin.
     *
     * @return array{name: string, typeLabel: ?string, score: ?string, scoreTier: ?string, countLabel: ?string, host: ?string}
     */
    public function payload(Company $company): array
    {
        $average = $company->average_rating;
        $count = $company->ratings_count;

        return [
            'name' => $company->name,
            'typeLabel' => $company->type?->label(),
            'score' => $average ? number_format((float) $average, 1) : null,
            'scoreTier' => $average ? $this->tier((float) $average) : null,
            // The count chip is dropped for a company with no ratings; the card
            // shows a "جديدة" badge instead of "لا توجد تقييمات".
            'countLabel' => $count > 0 ? trans_choice('counts.ratings', $count) : null,
            'host' => $company->website ? (parse_url($company->website, PHP_URL_HOST) ?: null) : null,
        ];
    }

    /**
     * Render a payload to PNG bytes by shelling out to the Takumi script.
     *
     * @param  array<string, mixed>  $payload
     */
    public function render(array $payload): string
    {
        $process = new Process(
            ['node', base_path('scripts/og-company.mjs')],
            base_path(),
            timeout: self::RENDER_TIMEOUT,
        );

        $process->setInput(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    /** Absolute path to the company's cached card. */
    public function path(Company $company): string
    {
        return $this->directory().'/company-'.$company->getKey().'-'.$this->key($company).'.png';
    }

    /** Short hash of the visible facts; the cache-busting component of the file name. */
    private function key(Company $company): string
    {
        return substr(hash('xxh128', json_encode($this->payload($company), JSON_UNESCAPED_UNICODE)), 0, 16);
    }

    private function directory(): string
    {
        return storage_path('app/og');
    }

    /**
     * Map an average to the same colour tier the overall-score component uses
     * (>= 4 good, >= 3 ok, else low).
     */
    private function tier(float $average): string
    {
        return match (true) {
            $average >= 4 => 'good',
            $average >= 3 => 'ok',
            default => 'low',
        };
    }
}
