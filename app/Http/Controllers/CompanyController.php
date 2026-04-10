<?php

namespace App\Http\Controllers;

use App\Models\Company;

class CompanyController extends Controller
{
    public function show(Company $company)
    {
        abort_unless($company->status === 'approved', 404);

        $ratings = $company->ratings()->latest()->paginate(10);

        return view('public.companies.show', compact('company', 'ratings'));
    }
}
