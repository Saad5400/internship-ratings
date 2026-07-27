<?php

use App\Enums\SearchField;
use App\Models\Company;
use App\Models\Rating;
use App\Models\SearchDocument;
use App\Support\CompanyFacets;
use App\Support\Search\CompanySearch;
use App\Support\Search\Embedder;
use App\Support\Search\SearchIndexer;
use App\Support\Search\Vector;
use Livewire\Livewire;

/**
 * Hybrid search: the literal leg and the embedding leg, and the field weighting
 * that fuses them.
 *
 * The suite runs on the deterministic fake embedder (see phpunit.xml), which
 * models shared vocabulary but not meaning. Tests that need real paraphrase
 * behaviour bind {@see conceptEmbedder} instead — a stub whose vectors are
 * authored by hand, so a "semantic match" assertion is testing our ranking
 * rather than a provider's mood.
 */

/**
 * @param  array<string, mixed>  $attributes
 */
function searchableCompany(string $name, array $attributes = [], array $companyAttributes = []): Company
{
    $company = Company::create(array_merge([
        'name' => $name,
        'status' => 'approved',
        'type' => 'private',
    ], $companyAttributes));

    Rating::create(array_merge([
        'company_id' => $company->id,
        'status' => 'approved',
        'role_title' => 'مطور',
        'city' => 'الرياض',
        'duration_months' => 3,
        'modality' => 'onsite',
        'recommendation' => 'yes',
        'rating_mentorship' => 4,
        'rating_learning' => 4,
        'rating_real_work' => 4,
        'rating_team_environment' => 4,
        'rating_organization' => 4,
    ], $attributes));

    return $company->refresh();
}

/**
 * An embedder that places listed phrases at a known point in vector space.
 *
 * Any text containing a concept's trigger word gets that concept's vector, so
 * two texts sharing no characters can still be near-identical vectors — which
 * is exactly the case only the semantic leg can serve.
 *
 * @param  array<string, list<string>>  $concepts  concept name => trigger words
 */
function conceptEmbedder(array $concepts): Embedder
{
    return new class($concepts) implements Embedder
    {
        public function __construct(private array $concepts) {}

        public function embed(array $texts): array
        {
            return array_map(function (string $text): array {
                $vector = array_fill(0, 8, 0.01);
                $index = 0;

                foreach ($this->concepts as $triggers) {
                    foreach ($triggers as $trigger) {
                        if (str_contains($text, $trigger)) {
                            $vector[$index] = 1.0;
                        }
                    }

                    $index++;
                }

                return Vector::normalize($vector);
            }, array_values($texts));
        }

        public function dimensions(): int
        {
            return 8;
        }

        public function model(): string
        {
            return 'concept-stub';
        }
    };
}

/**
 * An embedder with no notion of similarity: every distinct text gets its own
 * basis vector, so unrelated texts score exactly 0. Use it to take the semantic
 * leg out of a test that is only about literal matching.
 */
function orthogonalEmbedder(): Embedder
{
    return new class implements Embedder
    {
        public function embed(array $texts): array
        {
            return array_map(function (string $text): array {
                $vector = array_fill(0, 64, 0.0);
                $vector[abs(crc32($text)) % 64] = 1.0;

                return $vector;
            }, array_values($texts));
        }

        public function dimensions(): int
        {
            return 64;
        }

        public function model(): string
        {
            return 'orthogonal-stub';
        }
    };
}

test('a name match outranks a comment match of the same term', function () {
    $named = searchableCompany('شركة الأفق');
    $mentioned = searchableCompany('مؤسسة النخبة', [
        'review_text' => 'تدربت هنا بعد أن رفضتني شركة الأفق، والتجربة كانت جيدة.',
    ]);

    $hits = app(CompanySearch::class)->search('الأفق');

    expect($hits->keys()->all())->toBe([$named->id, $mentioned->id]);
    expect($hits[$named->id]->score)->toBeGreaterThan($hits[$mentioned->id]->score);
    expect($hits[$named->id]->topField())->toBe(SearchField::Name);
    expect($hits[$mentioned->id]->topField())->toBe(SearchField::Comments);
});

test('comments still surface a company when nothing stronger matches', function () {
    $company = searchableCompany('مؤسسة النخبة', [
        'review_text' => 'المرافق ممتازة وتوجد حضانة للأطفال في المقر.',
    ]);

    $hits = app(CompanySearch::class)->search('حضانة');

    expect($hits)->toHaveKey($company->id);
    expect($hits[$company->id]->topField())->toBe(SearchField::Comments);
});

test('a company is findable by the city of its approved ratings', function () {
    $jeddah = searchableCompany('شركة البحر', ['city' => 'جدة']);
    searchableCompany('شركة الصحراء', ['city' => 'الرياض']);

    $hits = app(CompanySearch::class)->search('جدة');

    expect($hits->keys()->all())->toBe([$jeddah->id]);
    expect($hits[$jeddah->id]->topField())->toBe(SearchField::City);
});

test('fields are ranked in weight order, so city beats role beats comments', function () {
    $city = searchableCompany('شركة ألف', ['city' => 'ينبع']);
    $role = searchableCompany('شركة باء', ['role_title' => 'ينبع']);
    $comment = searchableCompany('شركة جيم', ['review_text' => 'ينبع']);

    $hits = app(CompanySearch::class)->search('ينبع');

    expect($hits->keys()->all())->toBe([$city->id, $role->id, $comment->id]);
});

test('search normalizes Arabic letter variants', function () {
    $company = searchableCompany('شركة الإنماء');

    expect(app(CompanySearch::class)->search('الانماء'))->toHaveKey($company->id);
});

test('embedding similarity finds a company that shares no words with the query', function () {
    app()->bind(Embedder::class, fn () => conceptEmbedder([
        'software' => ['برمجيات', 'تطوير التطبيقات'],
        'catering' => ['تموين', 'إعاشة'],
    ]));

    $software = searchableCompany('مؤسسة الصقر', [], ['description' => 'تطوير التطبيقات']);
    $catering = searchableCompany('مؤسسة النسر', [], ['description' => 'إعاشة']);

    app()->forgetScopedInstances();
    $hits = app(CompanySearch::class)->search('برمجيات');

    expect($hits)->toHaveKey($software->id);
    expect($hits[$software->id]->isSemanticMatch())->toBeTrue();
    expect($hits->keys()->first())->toBe($software->id);
    expect($hits->get($catering->id)?->score ?? 0.0)
        ->toBeLessThan($hits[$software->id]->score);
});

test('search degrades to literal matching when the embedding provider fails', function () {
    $company = searchableCompany('شركة الأفق');

    app()->bind(Embedder::class, fn () => new class implements Embedder
    {
        public function embed(array $texts): array
        {
            throw new RuntimeException('provider down');
        }

        public function dimensions(): int
        {
            return 8;
        }

        public function model(): string
        {
            return 'broken';
        }
    });

    app()->forgetScopedInstances();

    $hits = app(CompanySearch::class)->search('الأفق');

    expect($hits)->toHaveKey($company->id);
    expect($hits[$company->id]->fields[SearchField::Name->value]['semantic'])->toBe(0.0);
    expect($hits[$company->id]->fields[SearchField::Name->value]['lexical'])->toBeGreaterThan(0.0);
});

test('every result carries a per-field score breakdown', function () {
    $company = searchableCompany('شركة الأفق', ['city' => 'الأفق']);

    $hit = app(CompanySearch::class)->search('الأفق')[$company->id];

    expect($hit->fields)->toHaveKeys([SearchField::Name->value, SearchField::City->value]);
    expect($hit->fields[SearchField::Name->value])->toHaveKeys(['lexical', 'semantic', 'score']);
    // Ordered strongest first, which is what the "matched on" line reads.
    expect(array_key_first($hit->fields))->toBe(SearchField::Name->value);
});

test('unapproved companies are kept out of the index', function () {
    $pending = Company::create(['name' => 'شركة المستقبل', 'status' => 'pending']);

    expect(SearchDocument::where('searchable_id', $pending->id)->count())->toBe(0);
    expect(app(CompanySearch::class)->search('المستقبل'))->toBeEmpty();
});

test('a company dropped from approved leaves the index', function () {
    $company = searchableCompany('شركة الأفق');

    expect(app(CompanySearch::class)->search('الأفق'))->toHaveKey($company->id);

    $company->update(['status' => 'rejected']);
    app()->forgetScopedInstances();

    expect(SearchDocument::where('searchable_id', $company->id)->count())->toBe(0);
    expect(app(CompanySearch::class)->search('الأفق'))->toBeEmpty();
});

test('approving a rating makes its content searchable', function () {
    $company = Company::create(['name' => 'شركة الأفق', 'status' => 'approved']);

    $rating = Rating::create([
        'company_id' => $company->id,
        'status' => 'pending',
        'role_title' => 'مصمم جرافيك',
        'city' => 'أبها',
        'modality' => 'onsite',
        'recommendation' => 'yes',
        'rating_mentorship' => 4,
        'rating_learning' => 4,
        'rating_real_work' => 4,
        'rating_team_environment' => 4,
        'rating_organization' => 4,
    ]);

    app()->forgetScopedInstances();
    expect(app(CompanySearch::class)->search('أبها'))->toBeEmpty();

    $rating->update(['status' => 'approved']);
    app()->forgetScopedInstances();

    expect(app(CompanySearch::class)->search('أبها'))->toHaveKey($company->id);
});

test('the index only holds fields that have content', function () {
    $company = Company::create(['name' => 'شركة الأفق', 'status' => 'approved']);

    $fields = SearchDocument::where('searchable_id', $company->id)->pluck('field')->all();

    expect($fields)->toBe([SearchField::Name]);
});

test('the companies index ranks results by relevance while searching', function () {
    // `pros`, not `review_text`: a review would also render in the page's
    // "أحدث التقييمات" strip above the grid, which has nothing to do with the
    // result order under test.
    searchableCompany('مؤسسة النخبة', ['pros' => 'أفضل من شركة الأفق بكثير.']);
    searchableCompany('شركة الأفق');

    // Asserted on the component's own result order rather than the rendered
    // markup: Livewire's Testable does not override assertSeeInOrder, so it
    // falls through to the raw JSON payload where Arabic is \u-escaped and
    // would never match.
    $component = Livewire::test('pages::companies.index')
        ->set('search', 'الأفق')
        ->assertSet('sort', 'relevance')
        ->assertSee('شركة الأفق')
        ->assertSee('مؤسسة النخبة');

    expect($component->instance()->companies->pluck('name')->all())
        ->toBe(['شركة الأفق', 'مؤسسة النخبة']);
});

test('clearing the search restores the default sort', function () {
    searchableCompany('شركة الأفق');

    Livewire::test('pages::companies.index')
        ->set('search', 'الأفق')
        ->assertSet('sort', 'relevance')
        ->set('search', '')
        ->assertSet('sort', 'highest_rated');
});

test('an explicit sort choice survives typing a search', function () {
    searchableCompany('شركة الأفق');

    Livewire::test('pages::companies.index')
        ->set('sort', 'most_rated')
        ->set('search', 'الأفق')
        ->assertSet('sort', 'most_rated');
});

test('a result explains which field it matched on', function () {
    searchableCompany('مؤسسة النخبة', ['city' => 'حائل']);

    Livewire::test('pages::companies.index')
        ->set('search', 'حائل')
        ->assertSee('طابق:')
        ->assertSee(SearchField::City->label());
});

test('facet counts describe the searched list, not the whole catalogue', function () {
    searchableCompany('شركة الأفق', ['city' => 'جدة']);
    searchableCompany('مؤسسة أخرى', ['city' => 'الرياض']);

    $options = collect(CompanyFacets::options('الأفق', [])['city'] ?? []);

    expect($options->pluck('value')->all())->toBe(['جدة']);
    expect($options->firstWhere('value', 'جدة')['count'])->toBe(1);
});

test('a search matching nothing returns no companies', function () {
    // Semantics off: the fake embedder buckets tokens by hash, so on a corpus
    // this small an unrelated query can collide into a weak similarity. This
    // test is about the literal miss and the empty state, not about ranking.
    app()->bind(Embedder::class, fn () => orthogonalEmbedder());

    searchableCompany('شركة الأفق');
    app()->forgetScopedInstances();

    expect(app(CompanySearch::class)->search('قطاع الفضاء الخارجي'))->toBeEmpty();

    Livewire::test('pages::companies.index')
        ->set('search', 'قطاع الفضاء الخارجي')
        ->assertDontSee('شركة الأفق')
        ->assertSee('لا توجد جهات');
});

test('re-indexing unchanged text costs no embedding calls', function () {
    $company = searchableCompany('شركة الأفق');

    $before = SearchDocument::where('searchable_id', $company->id)->pluck('indexed_at', 'field');

    $this->travel(1)->minutes();
    app(SearchIndexer::class)->index($company->fresh());

    $after = SearchDocument::where('searchable_id', $company->id)->pluck('indexed_at', 'field');

    expect($after->toArray())->toEqual($before->toArray());
});

test('editing a company re-embeds only the field that changed', function () {
    $company = searchableCompany('شركة الأفق', [], ['description' => 'وصف قديم']);

    $nameIndexedAt = SearchDocument::where('searchable_id', $company->id)
        ->where('field', SearchField::Name->value)->value('indexed_at');

    $this->travel(1)->minutes();
    $company->update(['description' => 'وصف جديد تماماً']);

    $documents = SearchDocument::where('searchable_id', $company->id)->get()->keyBy('field.value');

    expect($documents[SearchField::Name->value]->indexed_at->timestamp)->toBe($nameIndexedAt->timestamp);
    expect($documents[SearchField::Profile->value]->content)->toContain('وصف جديد');
});
