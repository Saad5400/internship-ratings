<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\SearchDocument;
use App\Support\Search\SearchIndexer;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Builds or repairs the search index.
 *
 * Safe to re-run: unchanged fields are skipped without an embeddings call, so
 * this is the normal way to backfill after a deploy or to heal rows a failed
 * queue job left without a vector.
 */
class IndexSearchCommand extends Command
{
    protected $signature = 'search:index
                            {--fresh : Discard the existing index and re-embed everything}
                            {--chunk=50 : Companies loaded per pass}';

    protected $description = 'Index approved companies for hybrid (lexical + semantic) search';

    public function handle(SearchIndexer $indexer): int
    {
        if ($this->option('fresh')) {
            SearchDocument::query()->delete();
            $this->components->info('Existing index cleared.');
        }

        $total = Company::approved()->count();

        if ($total === 0) {
            $this->components->warn('No approved companies to index.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $embedded = 0;
        $indexed = 0;

        Company::approved()
            ->with('approvedRatings')
            ->chunkById((int) $this->option('chunk'), function (EloquentCollection $companies) use ($indexer, $bar, &$embedded, &$indexed): void {
                $embedded += $indexer->indexMany($companies);
                $indexed += $companies->count();
                $bar->advance($companies->count());
            });

        $bar->finish();
        $this->newLine(2);

        // Companies that fell out of `approved` keep stale rows otherwise, and
        // would stay findable after being unpublished.
        $orphaned = SearchDocument::query()
            ->ofType((new Company)->getMorphClass())
            ->whereNotIn('searchable_id', Company::approved()->select('id'))
            ->delete();

        $this->components->info("Indexed {$indexed} companies · {$embedded} field vectors written · {$orphaned} stale rows removed.");

        $missing = SearchDocument::query()->unembedded()->count();

        if ($missing > 0) {
            $this->components->warn("{$missing} fields still have no vector — re-run to retry them.");
        }

        return self::SUCCESS;
    }
}
