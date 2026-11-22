<?php

namespace Tests;

use App\Support\Search\CompanySearch;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The search matrix is held per PHP process (Octane worker); every test
        // shares one process, so start each from an empty hold.
        CompanySearch::forgetWorkerMatrix();
    }
}
