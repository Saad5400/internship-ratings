<?php

namespace App\Jobs;

use App\Models\Company;
use App\Support\Search\SearchIndexer;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * Re-index one company off the request path.
 *
 * Approving a rating shouldn't make a moderator wait on an embeddings call, so
 * the write path only dispatches this. If it fails the company keeps its
 * previous vectors and `search:index` repairs it on the next run.
 */
class IndexSearchDocuments implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $companyId) {}

    /**
     * A company is re-indexed by several of its ratings at once during bulk
     * moderation; overlapping runs would race on the same rows for no gain.
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping((string) $this->companyId))->expireAfter(180)];
    }

    public function handle(SearchIndexer $indexer): void
    {
        $company = Company::query()->with('approvedRatings')->find($this->companyId);

        if ($company === null) {
            return;
        }

        $indexer->index($company);
    }

    public function uniqueId(): string
    {
        return (string) $this->companyId;
    }
}
