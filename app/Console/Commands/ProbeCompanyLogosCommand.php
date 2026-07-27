<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Support\CompanyBranding;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Records which company websites actually have a favicon.
 *
 * The favicon CDN answers 200 for every host, handing back a generic grey globe
 * when it has no icon — so the avatar cannot tell "no logo" from "logo" and its
 * fallback to the company initial never fires. A company whose site has no icon
 * therefore renders as an anonymous globe instead of its own letter. The only
 * way to know is to ask, once, and store the answer in `companies.has_logo`.
 *
 * Previews by default and writes only under `--apply`, matching
 * {@see BackfillCompanyBrandingCommand}. A probe that fails to answer leaves the
 * column alone rather than demoting a company on a timeout.
 */
#[Signature('companies:probe-logos
                {--apply : Persist the probe results — without this nothing is written}
                {--reprobe : Also re-check companies that were probed before}
                {--limit=0 : Only probe the first N candidates}')]
#[Description('Check which company websites serve a real favicon, so the avatar can fall back to the initial for the ones that do not.')]
class ProbeCompanyLogosCommand extends Command
{
    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $companies = $this->candidates();

        if ($companies->isEmpty()) {
            $this->components->info('Nothing to probe — every company with a website already has an answer.');

            return self::SUCCESS;
        }

        $withLogo = 0;
        $withoutLogo = 0;
        $unanswered = 0;

        $bar = $this->output->createProgressBar($companies->count());
        $bar->start();

        foreach ($companies as $company) {
            $hasLogo = CompanyBranding::probeLogo($company->website);
            $bar->advance();

            if ($hasLogo === null) {
                $unanswered++;

                continue;
            }

            $hasLogo ? $withLogo++ : $withoutLogo++;

            if ($apply && $company->has_logo !== $hasLogo) {
                $company->has_logo = $hasLogo;
                // saveQuietly: `has_logo` is not an indexed field, and a normal
                // save would queue an embedding job per company for nothing.
                $company->saveQuietly();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->components->info(
            "{$companies->count()} probed · {$withLogo} serve a real favicon · {$withoutLogo} fall back to the initial"
            .($unanswered > 0 ? " · {$unanswered} unanswered (left untouched)" : '')
        );

        if (! $apply) {
            $this->components->warn('Dry run — nothing was written. Re-run with --apply to persist.');
        }

        return self::SUCCESS;
    }

    /**
     * Companies whose avatar depends on a favicon and whose answer is missing.
     *
     * @return EloquentCollection<int, Company>
     */
    private function candidates(): EloquentCollection
    {
        $query = Company::query()
            ->whereNotNull('website')
            ->where('website', '!=', '')
            ->unless($this->option('reprobe'), fn (Builder $unprobed) => $unprobed->whereNull('has_logo'))
            ->orderBy('id');

        $limit = (int) $this->option('limit');

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
