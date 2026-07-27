<?php

namespace App\Providers;

use App\Support\Search\CompanySearch;
use App\Support\Search\Embedder;
use App\Support\Search\FakeEmbedder;
use App\Support\Search\OpenRouterEmbedder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerSearch();
    }

    /**
     * The search stack's two bindings.
     *
     * The embedder is resolved from config so no caller names a provider; it
     * falls back to the deterministic fake whenever the driver says so or no
     * key is configured, which keeps local work and tests running offline.
     *
     * CompanySearch is `scoped`, not `singleton`: it memoizes per query, and a
     * singleton would carry one request's results into the next under Octane.
     */
    protected function registerSearch(): void
    {
        $this->app->bind(Embedder::class, function (): Embedder {
            $config = config('search.embeddings');

            if ($config['driver'] !== 'openrouter' || blank($config['key'])) {
                return new FakeEmbedder((int) $config['dimensions']);
            }

            return new OpenRouterEmbedder(
                model: $config['model'],
                dimensions: (int) $config['dimensions'],
                apiKey: $config['key'],
                baseUrl: $config['url'],
                timeout: (int) $config['timeout'],
            );
        });

        $this->app->scoped(CompanySearch::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        if (in_array($this->app->environment(), ['production', 'staging'])) {
            URL::forceScheme('https');
        }

        if ($this->app->environment('local')) {
            Vite::useScriptTagAttributes(['data-navigate-track' => false]);
            Vite::useStyleTagAttributes(['data-navigate-track' => false]);
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
