<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\CompanyOgImage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves a company's Open Graph card at a stable, per-company URL
 * (route `companies.og`), so a shared link previews with the company's own
 * name and score rather than the generic site card.
 *
 * Only approved companies are public — the same gate the show page applies.
 * When rendering fails, the static site card stands in so the tag a crawler
 * fetched never 404s.
 */
class OgImageController extends Controller
{
    public function __invoke(Request $request, Company $company, CompanyOgImage $ogImage): BinaryFileResponse
    {
        abort_unless($company->status === 'approved', 404);

        $path = $ogImage->ensure($company);

        if ($path === null) {
            return response()->file(public_path('og-image.png'), [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'public, max-age=300',
            ]);
        }

        $response = response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);

        $response->setEtag($ogImage->etag($company));
        $response->isNotModified($request);

        return $response;
    }
}
