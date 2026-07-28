<?php

use App\Models\Company;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

/*
 * The admin list pages promise that only the table scrolls sideways, never the
 * page — layouts.admin pins a topbar and a mobile tab bar, and both detach the
 * moment the document itself goes wide.
 *
 * The way that promise breaks is subtle. A `min-w-max` table is far wider than a
 * phone, so an absolutely positioned descendant of a trailing cell — `sr-only`
 * on an actions header is the usual one — is laid out hundreds of pixels outside
 * the viewport. With no positioned ancestor its containing block is the initial
 * one, so `overflow-x-auto` cannot clip it and that offset lands on the document
 * as scrollable overflow. Hence the assertion below: inside a horizontal
 * scroller, every out-of-flow element needs a positioned ancestor.
 */

/** Tailwind utilities that let an element serve as a containing block. */
const POSITIONING_CLASSES = ['relative', 'absolute', 'fixed', 'sticky'];

/** Tailwind utilities that hand an element to its containing block instead of the flow. */
const OUT_OF_FLOW_CLASSES = ['absolute', 'sr-only'];

/**
 * @return list<string> a description of every out-of-flow element that escapes its scroller
 */
function elementsEscapingHorizontalScrollers(string $html): array
{
    $document = new DOMDocument;
    $document->loadHTML('<?xml encoding="UTF-8"?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    $xpath = new DOMXPath($document);

    /** @var callable(DOMElement, list<string>): bool $hasClass */
    $hasClass = fn (DOMElement $element, array $classes): bool => (bool) array_intersect(
        preg_split('/\s+/', trim($element->getAttribute('class'))) ?: [],
        $classes
    );

    $escaping = [];

    foreach ($xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' overflow-x-auto ')]") as $scroller) {
        foreach ($xpath->query('.//*', $scroller) as $candidate) {
            if (! $hasClass($candidate, OUT_OF_FLOW_CLASSES)) {
                continue;
            }

            $contained = false;

            for ($ancestor = $candidate->parentNode; $ancestor !== $scroller; $ancestor = $ancestor->parentNode) {
                if ($ancestor instanceof DOMElement && $hasClass($ancestor, POSITIONING_CLASSES)) {
                    $contained = true;
                    break;
                }
            }

            if (! $contained) {
                $escaping[] = $candidate->nodeName.'.'.$candidate->getAttribute('class').' ("'.trim($candidate->textContent).'")';
            }
        }
    }

    return $escaping;
}

test('the shared admin table shell contains absolutely positioned cell content', function () {
    $html = Blade::render(<<<'BLADE'
        <x-admin.table>
            <x-slot:head><x-admin.th align="end"><span class="sr-only">الإجراءات</span></x-admin.th></x-slot:head>
        </x-admin.table>
        BLADE);

    expect(elementsEscapingHorizontalScrollers($html))->toBe([]);
});

test('the ratings index table does not widen the page', function () {
    $admin = User::factory()->admin()->create();
    $company = Company::create(['name' => 'جهة عرض جدول', 'type' => 'private', 'status' => 'approved']);

    Rating::create([
        'company_id' => $company->id,
        'role_title' => 'مهندس اختبار التصميم',
        'modality' => 'onsite',
        'rating_learning' => 4,
        'rating_mentorship' => 4,
        'rating_real_work' => 4,
        'rating_team_environment' => 4,
        'rating_organization' => 4,
        'status' => 'pending',
    ]);

    $html = Livewire::actingAs($admin)->test('pages::admin.ratings.index')->html();

    expect(elementsEscapingHorizontalScrollers($html))->toBe([]);
});

test('the companies index table does not widen the page', function () {
    $admin = User::factory()->admin()->create();
    Company::create(['name' => 'جهة عرض جدول الجهات', 'type' => 'private', 'status' => 'pending']);

    $html = Livewire::actingAs($admin)->test('pages::admin.companies.index')->html();

    expect(elementsEscapingHorizontalScrollers($html))->toBe([]);
});
