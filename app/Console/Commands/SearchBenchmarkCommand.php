<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Support\Search\CompanySearch;
use App\Support\Search\SearchHit;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Scores search quality against the live catalogue.
 *
 * Ranking changes cannot be judged by reading them. The semantic window that
 * shipped first looked obviously correct and buried أرامكو at rank 44 for
 * "شركة بترول". The fix that followed also looked obviously correct, and handed
 * a medical city the top slot for a query about web development. Both were only
 * caught by running real queries against real data, so that is what this does.
 *
 * The unit tests pin *mechanisms* on synthetic corpora. This pins *outcomes* on
 * the real one — the only place field cardinality, name length and Arabic
 * morphology interact the way they actually do. Run it after touching anything
 * under app/Support/Search or config/search.php.
 *
 * A query counts as a hit when any of its accepted companies lands within
 * --depth. Accepted lists are generous where several answers are equally
 * defensible: "استشارات إدارية" has no single right answer among three
 * consultancies, and pretending otherwise would tune the ranker to a coin flip.
 */
class SearchBenchmarkCommand extends Command
{
    protected $signature = 'search:benchmark
                            {--depth=3 : Rank a query must land within to count as a hit}';

    protected $description = 'Score hybrid search against a fixed set of real queries';

    /**
     * Query => company name fragments that would each be a correct answer.
     *
     * These describe the production catalogue. If it changes enough that an
     * expectation stops being reasonable, fix the expectation — do not tune the
     * ranker until a stale one passes.
     *
     * @var array<string, list<string>>
     */
    protected const EXPECTATIONS = [
        // Semantic: the query's words appear in no matching company name, so
        // only the embedding leg can find these.
        'شركة بترول' => ['أرامكو'],
        'oil company' => ['أرامكو'],
        'بتروكيماويات' => ['سابك'],
        'كهرباء' => ['السعودية للكهرباء'],
        'مياه' => ['المياه الوطنية'],
        'تأمين' => ['نجم'],
        'ذكاء اصطناعي وتحليل بيانات' => ['سدايا', 'Lucidya'],
        'استشارات إدارية' => ['McKinsey', 'PwC', 'Deloitte'],
        'قطاع صحي ومستشفيات' => ['مستشفى', 'الطبية', 'الصحية', 'الصحة'],
        'برمجة وتطوير مواقع' => ['تقنية المعلومات', 'TechWin', 'Lucidya', 'إيسار', 'SITE'],
        'مصرف وبنك' => ['بنك', 'مصرف', 'البنك'],
        'خدمات مصرفية وتمويل' => ['بنك', 'مصرف', 'البنك', 'تمويل', 'المالية'],

        // Lexical: typing a name must still beat anything the semantic leg likes.
        'أرامكو' => ['أرامكو'],
        'سابك' => ['سابك'],
        'stc' => ['stc'],
    ];

    public function handle(CompanySearch $search): int
    {
        $depth = max(1, (int) $this->option('depth'));

        if (! $search->hasIndex()) {
            $this->components->error('No search index — run `php artisan search:index` first.');

            return self::FAILURE;
        }

        $rows = [];
        $scored = 0;
        $withinDepth = 0;
        $firstPlace = 0;
        $reciprocalRankSum = 0.0;

        foreach (self::EXPECTATIONS as $query => $accepted) {
            // "The answer is not in this catalogue" is not a ranking failure,
            // and scoring it as one makes the metric meaningless anywhere but
            // production — a local database of five companies would report 47%
            // and imply a broken ranker. Skipped rows are excluded from the
            // totals rather than counted against them.
            if (! $this->catalogueContains($accepted)) {
                $rows[] = [$query, '—', 'n/a', 'not in this catalogue'];

                continue;
            }

            $results = $search->search($query)->take(20);
            $names = Company::whereIn('id', $results->pluck('companyId'))->pluck('name', 'id');

            [$rank, $matched] = $this->locate($results, $names, $accepted);
            $scored++;

            if ($rank !== null) {
                $reciprocalRankSum += 1 / $rank;
                $withinDepth += $rank <= $depth ? 1 : 0;
                $firstPlace += $rank === 1 ? 1 : 0;
            }

            $rows[] = [
                $query,
                $rank ?? '—',
                $rank !== null && $rank <= $depth ? '✓' : '✗',
                // On a miss, showing what *did* win is the whole diagnosis.
                mb_substr($matched ?? ($names[$results->keys()->first()] ?? 'no results'), 0, 32),
            ];
        }

        $this->table(['query', 'rank', "hit@{$depth}", 'matched / top result'], $rows);
        $this->newLine();

        if ($scored === 0) {
            $this->components->warn('No expectation applies to this catalogue — nothing was scored.');

            return self::SUCCESS;
        }

        $skipped = count(self::EXPECTATIONS) - $scored;

        $this->line(sprintf(
            '  hit@%d %d/%d (%d%%)    top-1 %d/%d (%d%%)    MRR %.3f%s',
            $depth,
            $withinDepth, $scored, round(100 * $withinDepth / $scored),
            $firstPlace, $scored, round(100 * $firstPlace / $scored),
            $reciprocalRankSum / $scored,
            $skipped > 0 ? "    ({$skipped} n/a)" : '',
        ));

        return $withinDepth === $scored ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Whether any acceptable answer exists here at all.
     *
     * @param  list<string>  $accepted
     */
    protected function catalogueContains(array $accepted): bool
    {
        return Company::approved()
            ->where(function ($query) use ($accepted): void {
                foreach ($accepted as $fragment) {
                    $query->orWhere('name', 'like', '%'.$fragment.'%');
                }
            })
            ->exists();
    }

    /**
     * Where the first acceptable company lands, and which one it was.
     *
     * @param  Collection<int, SearchHit>  $results
     * @param  Collection<int, string>  $names
     * @param  list<string>  $accepted
     * @return array{0: int|null, 1: string|null}
     */
    protected function locate($results, $names, array $accepted): array
    {
        $position = 0;

        foreach ($results as $hit) {
            $position++;
            $name = (string) ($names[$hit->companyId] ?? '');

            foreach ($accepted as $fragment) {
                if (mb_stripos($name, $fragment) !== false) {
                    return [$position, $name];
                }
            }
        }

        return [null, null];
    }
}
