<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Support\CompanyBranding;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Backfills missing company websites — and with them, missing logos.
 *
 * There is no logo column: the avatar resolves its mark from the website host
 * (see {@see CompanyBranding}), so a company without a website renders as a bare
 * initial. Filling the website is what puts a logo on the card.
 *
 * Derivation is deterministic and offline, which also means it *guesses*. The
 * command therefore previews by default and writes only under `--apply`;
 * `--verify` turns the guesses into checked facts at the cost of one HEAD
 * request per candidate.
 */
#[Signature('companies:backfill-branding
                {--apply : Persist the derived websites — without this nothing is written}
                {--dry-run : Preview without writing; the default, accepted for explicitness}
                {--verify : HEAD-request each candidate and drop the ones that do not resolve}
                {--limit=0 : Only consider the first N companies missing a website}')]
#[Description('Fill in missing company websites (and therefore logos) by deriving a domain from the company name.')]
class BackfillCompanyBrandingCommand extends Command
{
    /** Seconds to wait on a `--verify` probe before calling the host unreachable. */
    private const VERIFY_TIMEOUT = 5;

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        if ($apply && $this->option('dry-run')) {
            $this->components->error('--apply and --dry-run contradict each other; pick one.');

            return self::FAILURE;
        }

        $companies = $this->companiesMissingAWebsite();

        if ($companies->isEmpty()) {
            $this->components->info('Every company already has a website — nothing to backfill.');

            return self::SUCCESS;
        }

        $verify = (bool) $this->option('verify');
        $rows = [];
        $filled = 0;

        foreach ($companies as $company) {
            ['website' => $website, 'reason' => $reason] = CompanyBranding::deriveWebsite($company->name);

            if ($website !== null && $verify && ! $this->resolves($website)) {
                [$website, $reason] = [null, 'unreachable'];
            }

            if ($website === null) {
                $rows[] = [$company->id, $company->name, '—', '—', $this->explain($reason)];

                continue;
            }

            if ($apply) {
                $company->website = $website;
                $company->save();
            }

            $filled++;
            $rows[] = [
                $company->id,
                $company->name,
                $website,
                CompanyBranding::logoUrl($website),
                $apply ? ($verify ? 'filled (verified)' : 'filled (guess)') : 'would fill',
            ];
        }

        $this->newLine();
        $this->table(['ID', 'Company', 'Website', 'Logo', 'Outcome'], $rows);

        $this->summarize($companies->count(), $filled, $apply, $verify);

        return self::SUCCESS;
    }

    /**
     * Companies with nothing to render a logo from. An empty string counts as
     * missing — the admin edit form writes one when the field is cleared.
     *
     * @return EloquentCollection<int, Company>
     */
    private function companiesMissingAWebsite(): EloquentCollection
    {
        $query = Company::query()
            ->where(fn (Builder $missing) => $missing->whereNull('website')->orWhere('website', ''))
            ->orderBy('id');

        $limit = (int) $this->option('limit');

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Whether the candidate host answers at all. Any transport failure is a
     * "no" — this is a filter for bad guesses, not a health check.
     */
    private function resolves(string $website): bool
    {
        try {
            return Http::timeout(self::VERIFY_TIMEOUT)
                ->withoutRedirecting()
                ->head($website)
                ->status() < 400;
        } catch (Throwable) {
            return false;
        }
    }

    private function explain(string $reason): string
    {
        return match ($reason) {
            CompanyBranding::NO_LATIN_NAME => 'skipped · Arabic-only name',
            CompanyBranding::TOO_GENERIC => 'skipped · name is a description, not a brand',
            'unreachable' => 'skipped · candidate did not resolve',
            default => 'skipped',
        };
    }

    private function summarize(int $considered, int $filled, bool $apply, bool $verify): void
    {
        $skipped = $considered - $filled;

        $this->components->info(
            "{$considered} companies missing a website · {$filled} ".($apply ? 'filled' : 'fillable')." · {$skipped} skipped."
        );

        if (! $apply) {
            $this->components->warn('Dry run — nothing was written. Re-run with --apply to persist these values.');
        }

        if ($filled > 0 && ! $verify) {
            $this->components->warn('Websites are derived from the name and unverified. Add --verify to drop candidates that do not resolve.');
        }
    }
}
