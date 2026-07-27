<?php

namespace App\Support\Search;

use App\Models\Company;
use App\Models\SearchDocument;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Keeps `search_documents` in step with the companies table.
 *
 * Embedding is a paid network call, so the indexer is built around avoiding it:
 * a field is only re-embedded when its text hash actually changes. Re-running a
 * full index after a cosmetic edit costs one row update and no API traffic.
 */
class SearchIndexer
{
    public function __construct(private readonly Embedder $embedder) {}

    /**
     * Re-index one company. Returns how many fields needed a fresh vector.
     *
     * The index only ever holds approved companies, so a company that is
     * pending or rejected is dropped instead — public search can't surface
     * unpublished records even by mistake.
     */
    public function index(Company $company): int
    {
        if ($company->status !== 'approved') {
            $this->forget($company);

            return 0;
        }

        return $this->indexMany(EloquentCollection::wrap([$company]));
    }

    /**
     * Re-index a batch of companies, embedding every changed field across the
     * whole batch in as few provider calls as possible.
     *
     * @param  EloquentCollection<int, Company>  $companies
     */
    public function indexMany(EloquentCollection $companies): int
    {
        $companies->loadMissing('approvedRatings');

        /** @var Collection<int, array{document: SearchDocument, content: string}> $pending */
        $pending = collect();

        foreach ($companies as $company) {
            $pending = $pending->merge($this->syncRows($company));
        }

        if ($pending->isEmpty()) {
            return 0;
        }

        $embedded = 0;

        foreach ($pending->chunk((int) config('search.embeddings.batch', 64)) as $chunk) {
            $embedded += $this->embedChunk($chunk->values());
        }

        return $embedded;
    }

    /** Drop a company out of the index entirely. */
    public function forget(Company $company): void
    {
        SearchDocument::query()
            ->ofType($company->getMorphClass())
            ->where('searchable_id', $company->getKey())
            ->delete();
    }

    /**
     * Write this company's field rows, remove fields it no longer has, and
     * return the rows whose text changed and therefore need embedding.
     *
     * @return Collection<int, array{document: SearchDocument, content: string}>
     */
    protected function syncRows(Company $company): Collection
    {
        $fields = CompanyDocument::build($company);
        $type = $company->getMorphClass();

        $existing = SearchDocument::query()
            ->ofType($type)
            ->where('searchable_id', $company->getKey())
            ->get()
            ->keyBy(fn (SearchDocument $document): string => $document->field->value);

        // A company that lost its last approved rating loses those fields too,
        // otherwise stale text keeps it findable by content nobody can read.
        $stale = $existing->keys()->diff(array_keys($fields));

        if ($stale->isNotEmpty()) {
            SearchDocument::query()
                ->ofType($type)
                ->where('searchable_id', $company->getKey())
                ->whereIn('field', $stale->all())
                ->delete();
        }

        $pending = collect();

        foreach ($fields as $field => $content) {
            $hash = hash('sha256', $this->embedder->model().'|'.$this->embedder->dimensions().'|'.$content);
            $document = $existing->get($field);

            if ($document !== null && $document->content_hash === $hash && $document->embedding !== null) {
                continue;
            }

            $document = SearchDocument::query()->updateOrCreate(
                ['searchable_type' => $type, 'searchable_id' => $company->getKey(), 'field' => $field],
                ['content' => $content, 'content_hash' => $hash, 'embedding' => null],
            );

            $pending->push(['document' => $document, 'content' => $content]);
        }

        return $pending;
    }

    /**
     * @param  Collection<int, array{document: SearchDocument, content: string}>  $chunk
     */
    protected function embedChunk(Collection $chunk): int
    {
        try {
            $vectors = $this->embedder->embed($chunk->pluck('content')->all());
        } catch (Throwable $exception) {
            // The rows are already written with a null embedding, so the next
            // run picks them up. Lexical search keeps working meanwhile.
            report($exception);

            return 0;
        }

        $embedded = 0;

        foreach ($chunk as $position => $row) {
            $vector = $vectors[$position] ?? null;

            if (! is_array($vector) || $vector === []) {
                continue;
            }

            $row['document']->forceFill([
                'embedding' => Vector::encode($vector),
                'embedding_model' => $this->embedder->model(),
                'dimensions' => count($vector),
                'indexed_at' => now(),
            ])->save();

            $embedded++;
        }

        return $embedded;
    }
}
