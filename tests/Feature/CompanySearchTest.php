<?php

use App\Enums\SearchField;
use App\Models\Company;
use App\Models\Rating;
use App\Models\SearchDocument;
use App\Support\Arabic;
use App\Support\CompanyFacets;
use App\Support\Search\CompanySearch;
use App\Support\Search\Embedder;
use App\Support\Search\SearchIndexer;
use App\Support\Search\Vector;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
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
 * An embedder whose similarity to the query is dictated per text.
 *
 * Vectors are placed on the unit circle at the angle that makes a text's cosine
 * against the query exactly its listed affinity, so a test can state the
 * distribution it wants to rank rather than hope a stub happens to produce one.
 * Texts that are not listed sit orthogonal to the query at 0.
 *
 * Keys are matched exactly against the indexed content, which for a name is its
 * normalised form — see {@see Arabic::normalize()}.
 *
 * @param  array<string, float>  $affinities  exact content => cosine against the query
 */
function gradedEmbedder(array $affinities): Embedder
{
    return new class($affinities) implements Embedder
    {
        public function __construct(private array $affinities) {}

        public function embed(array $texts): array
        {
            return array_map(function (string $text): array {
                $affinity = $this->affinities[$text] ?? 0.0;

                return [$affinity, sqrt(max(1.0 - $affinity ** 2, 0.0))];
            }, array_values($texts));
        }

        public function dimensions(): int
        {
            return 2;
        }

        public function model(): string
        {
            return 'graded-stub';
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

test('a result card describes the company, not the ranker or one reviewer', function () {
    // The card used to print "طابق: <field>" and quote the latest review. Both
    // are gone: the first narrates the ranker instead of the company, and the
    // second reads as something the company said rather than one intern's
    // opinion. The match reason still exists on the hit — see the topField
    // tests above — it just is not shown.
    $company = searchableCompany('مؤسسة النخبة', [
        'city' => 'حائل',
        'review_text' => 'رأي متدرب واحد لا يمثل الجهة.',
    ]);

    $hit = app(CompanySearch::class)->search('حائل')[$company->id];

    expect($hit->topField())->toBe(SearchField::City);

    $card = Blade::render(
        '<x-public.company-card :company="$company" :hit="$hit" />',
        ['company' => $company->load('latestApprovedRating'), 'hit' => $hit],
    );

    expect($card)
        ->toContain('مؤسسة النخبة')
        ->not->toContain('طابق:')
        ->not->toContain(SearchField::City->label())
        ->not->toContain('رأي متدرب واحد');
});

test('facet counts describe the searched list, not the whole catalogue', function () {
    searchableCompany('شركة الأفق', ['city' => 'جدة']);
    searchableCompany('مؤسسة أخرى', ['city' => 'الرياض']);

    $options = collect(CompanyFacets::options('الأفق', [])['city'] ?? []);

    expect($options->pluck('value')->all())->toBe(['جدة']);
    expect($options->firstWhere('value', 'جدة')['count'])->toBe(1);
});

test('search falls back to name matching when the index is empty', function () {
    // The window on every deploy: migrations have run, `search:index` has not.
    $company = searchableCompany('شركة الأفق');
    SearchDocument::query()->delete();
    app()->forgetScopedInstances();

    expect(Company::approved()->matchingSearch('الأفق')->pluck('id')->all())
        ->toBe([$company->id]);

    Livewire::test('pages::companies.index')
        ->set('search', 'الأفق')
        ->assertSee('شركة الأفق');
});

test('search survives the index table not existing yet', function () {
    // Deploys here do not run migrations, so a release can reach users before
    // the table does. Search should degrade, not take the page down.
    $company = searchableCompany('شركة الأفق');
    Schema::drop('search_documents');
    app()->forgetScopedInstances();

    Livewire::test('pages::companies.index')
        ->set('search', 'الأفق')
        ->assertOk()
        ->assertSee('شركة الأفق');

    expect(Company::approved()->matchingSearch('الأفق')->pluck('id')->all())
        ->toBe([$company->id]);
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

test('a word most of the catalogue shares does not outrank the word that narrows it', function () {
    // Literal leg only: this is about token weighting, not about meaning.
    app()->bind(Embedder::class, fn () => orthogonalEmbedder());

    // "شركة" is the catalogue's background noise — every one of these carries
    // it, so on its own it says nothing about which company was meant.
    foreach (['شركة الأفق', 'شركة النخبة', 'شركة الواحة', 'شركة المستقبل', 'شركة الرؤية'] as $name) {
        searchableCompany($name);
    }

    $refinery = searchableCompany('شركة بترول الجزيرة');
    app()->forgetScopedInstances();

    $hits = app(CompanySearch::class)->search('شركة بترول');

    expect($hits->keys()->first())->toBe($refinery->id);
    expect($hits[$refinery->id]->score)->toBeGreaterThan(2 * $hits->values()[1]->score);
});

test('a shared word still searches on its own, it is only worth less alongside a rarer one', function () {
    app()->bind(Embedder::class, fn () => orthogonalEmbedder());

    foreach (['شركة الأفق', 'شركة النخبة', 'شركة الواحة'] as $name) {
        searchableCompany($name);
    }
    app()->forgetScopedInstances();

    // Weighting tokens against each other must not turn a common word into a
    // dead one: typing only "شركة" is still a legitimate search, and with
    // nothing rarer to compare against every company remains a valid answer.
    $hits = app(CompanySearch::class)->search('شركة');

    expect($hits)->toHaveCount(3);
    expect($hits->pluck('score')->unique())->toHaveCount(1);
});

/**
 * Builds a corpus large enough to trip `semantic.min_sample`, with every name's
 * cosine against the query stated outright.
 *
 * @param  list<float>  $band  cosine for each of the ordinary companies
 * @return array{0: string, 1: list<Company>}
 */
function gradedCorpus(array $band, float $standout): array
{
    $query = 'استعلام دلالي';
    // Keyed on the normalised form, which is what actually reaches the embedder
    // — Arabic::normalize strips the trailing hamza, and keying on the raw name
    // silently embeds the standout at zero.
    $affinities = [Arabic::normalize($query) => 1.0];
    $names = [];

    foreach ($band as $index => $cosine) {
        $name = 'جهة رقم '.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
        $names[] = $name;
        $affinities[Arabic::normalize($name)] = $cosine;
    }

    $standoutName = 'جهة الاستثناء';
    $affinities[Arabic::normalize($standoutName)] = $standout;

    app()->bind(Embedder::class, fn () => gradedEmbedder($affinities));

    $companies = array_map(fn (string $name): Company => searchableCompany($name), $names);
    $companies[] = searchableCompany($standoutName);
    app()->forgetScopedInstances();

    return [$query, $companies];
}

test('a similarity is judged against its own field rather than a fixed cutoff', function () {
    // Ordinary names sit in a tight band; one stands well clear of it.
    [$query, $companies] = gradedCorpus(
        array_map(fn (int $i): float => 0.20 + ($i % 5) * 0.02, range(0, 23)),
        0.55,
    );

    $hits = app(CompanySearch::class)->search($query);

    expect($hits->keys()->all())->toBe([end($companies)->id]);
});

test('a similarity the whole field shares does not rank, however high it reads', function () {
    // The bug this replaced. Querying "شركة بترول" scored the name of شركة
    // المياه الوطنية — a water utility — at cosine 0.559, because short company
    // names all embed near one another. Read against a fixed cutoff that is a
    // decisive match; read against the field it sits in, it is the middle of the
    // pack. The same 0.55 that wins the test above must lose here.
    [$query, $companies] = gradedCorpus(
        array_map(fn (int $i): float => 0.54 + ($i % 5) * 0.005, range(0, 23)),
        0.55,
    );

    $hits = app(CompanySearch::class)->search($query);

    // Nothing stands out, so nothing is returned — not even the band itself.
    expect($hits)->toBeEmpty();
});

test('a field where most companies share one value cannot invent a standout', function () {
    // Low-cardinality fields are the norm, not the exception: 244 companies draw
    // on a handful of cities. When more than half share a value the median
    // absolute deviation is exactly zero, and an earlier revision read that as
    // "anything above the median is infinitely unusual" — handing a perfect
    // semantic score to whichever city happened to sit a hair above the tie,
    // which ranked a medical city first for a query about web development.
    $band = array_merge(array_fill(0, 20, 0.30), [0.36, 0.42]);

    [$query, $companies] = gradedCorpus($band, 0.34);

    $hits = app(CompanySearch::class)->search($query);
    $marginal = end($companies);      // 0.34 — above the tie, but unremarkable
    $genuine = $companies[21];        // 0.42 — the one real outlier

    expect($hits->keys())->not->toContain($marginal->id);
    expect($hits)->toHaveKey($genuine->id);
    // Scored on its merits rather than saturated, which is what INF produced.
    expect($hits[$genuine->id]->fields[SearchField::Name->value]['semantic'])
        ->toBeGreaterThan(0.0)
        ->toBeLessThan(1.0);
});
