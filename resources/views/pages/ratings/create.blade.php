<?php

use App\Enums\CompanyType;
use App\Enums\Modality;
use App\Enums\Recommendation;
use App\Enums\ReviewerDegree;
use App\Enums\SaudiCity;
use App\Models\Company;
use App\Models\Rating;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use NjoguAmos\Turnstile\Rules\TurnstileRule;

new #[Layout('layouts.public')] #[Title('أضف تقييم')] class extends Component {
    public int $currentStep = 1;
    public int $totalSteps = 3;

    // Step 1 — company & role
    public ?string $companyId = null;
    public ?string $newCompanyName = null;
    public string $companySearch = '';
    public array $companyOptions = [];

    public array $cityOptions = [];

    public ?string $role_title = null;
    public ?string $department = null;
    public ?string $city = null;
    public ?int $duration_months = null;
    public ?string $newCompanyType = null;  // government | private | nonprofit | other
    public ?string $modality = null;

    // Step 2 — facts & scores
    public ?int $stipend_sar = null;
    public ?bool $had_supervisor = null;
    public ?bool $mixed_env = null;
    public ?bool $job_offer = null;
    public ?int $rating_mentorship = null;
    public ?int $rating_learning = null;
    public ?int $rating_real_work = null;
    public ?int $rating_team_environment = null;
    public ?int $rating_organization = null;

    // Step 3 — experience
    public ?string $recommendation = null;
    public bool $recommendationOverridden = false;
    public string $review_text = '';
    public ?string $pros = null;
    public ?string $cons = null;
    public ?string $reviewer_name = null;
    public ?string $reviewer_university = null;
    public ?string $reviewer_college = null;
    public ?string $reviewer_major = null;
    public ?string $reviewer_degree = null;
    public ?string $application_method = null;
    public ?bool $willing_to_help = null;
    public ?string $contact_method = null;
    public string $turnstile = '';
    protected bool $syncingRecommendation = false;

    public function mount(): void
    {
        $this->companyOptions = $this->defaultCompanyOptions();
        $this->cityOptions = $this->normalizeChoicesOptions(SaudiCity::toChoicesOptions());

        $companyId = request()->integer('company');

        if ($companyId) {
            $selected = Company::approved()->find($companyId);
            if ($selected) {
                $this->companyId = (string) $selected->id;
                $this->companyOptions = $this->normalizeChoicesOptions(collect($this->companyOptions)
                    ->push(['id' => (string) $selected->id, 'name' => $selected->name])
                    ->unique('id')
                    ->values()
                    ->all());
            }
        }
    }

    public function hydrate(): void
    {
        $this->companyOptions = $this->normalizeChoicesOptions($this->companyOptions);
        $this->cityOptions = $this->normalizeChoicesOptions($this->cityOptions);
    }

    protected function defaultCompanyOptions(): array
    {
        return $this->normalizeChoicesOptions(Company::approved()
            ->orderBy('name')
            ->take(8)
            ->get(['id', 'name'])
            ->map(fn ($c) => ['id' => (string) $c->id, 'name' => $c->name])
            ->all());
    }

    protected function normalizeChoicesOptions(array $options): array
    {
        return collect($options)
            ->values()
            ->map(fn ($option) => [
                'id' => data_get($option, 'id'),
                'name' => data_get($option, 'name'),
            ])
            ->all();
    }

    #[Computed]
    public function durationMonthOptions(): array
    {
        return collect(range(1, 12))
            ->map(fn (int $month) => ['id' => $month, 'name' => (string) $month])
            ->all();
    }

    public function searchCompanies(string $value = ''): void
    {
        $this->companySearch = trim($value);

        $query = Company::approved()->orderBy('name');

        if ($this->companySearch !== '') {
            $query->where('name', 'like', "%{$this->companySearch}%");
        }

        $results = $query->take(8)->get(['id', 'name'])
            ->map(fn ($c) => ['id' => (string) $c->id, 'name' => $c->name]);

        if ($this->companyId !== null && $this->companyId !== '__new__') {
            $selected = Company::approved()->find($this->companyId);
            if ($selected && ! $results->contains(fn ($r) => $r['id'] === (string) $selected->id)) {
                $results->push(['id' => (string) $selected->id, 'name' => $selected->name]);
            }
        }

        if ($this->companySearch !== '') {
            $exactMatch = $results->contains(fn ($r) => mb_strtolower($r['name']) === mb_strtolower($this->companySearch));
            if (! $exactMatch) {
                $results->prepend(['id' => '__new__', 'name' => $this->companySearch]);
            }
        }

        $this->companyOptions = $this->normalizeChoicesOptions($results->all());
    }

    public function updatedCompanyId($value): void
    {
        if ($value === '__new__') {
            $companyName = trim($this->companySearch);

            $this->newCompanyName = $companyName;
            $this->companySearch = '';

            if ($companyName !== '') {
                $this->companyOptions = $this->normalizeChoicesOptions([
                    ['id' => '__new__', 'name' => $companyName],
                ]);
            }

            return;
        }

        $this->newCompanyName = null;
        $this->newCompanyType = null;
        $this->companySearch = '';

        if (blank($value)) {
            $this->companyOptions = $this->defaultCompanyOptions();

            return;
        }

        $selected = Company::approved()->find($value);

        $this->companyOptions = $selected
            ? $this->normalizeChoicesOptions([['id' => (string) $selected->id, 'name' => $selected->name]])
            : $this->defaultCompanyOptions();
    }

    public function clearCompany(): void
    {
        $this->companyId = null;
        $this->newCompanyName = null;
        $this->newCompanyType = null;
        $this->companySearch = '';
        $this->companyOptions = $this->defaultCompanyOptions();
    }

    protected function ratingFields(): array
    {
        return [
            'rating_mentorship',
            'rating_learning',
            'rating_real_work',
            'rating_team_environment',
            'rating_organization',
        ];
    }

    public function updated(string $property): void
    {
        if (in_array($property, $this->ratingFields(), true)) {
            unset($this->calculatedOverallRating, $this->suggestedRecommendation);
            $this->syncSuggestedRecommendation();
        }
    }

    public function updatedRecommendation(?string $value): void
    {
        if ($this->syncingRecommendation) {
            return;
        }

        if ($value !== null) {
            $this->recommendationOverridden = true;
        }
    }

    protected function syncSuggestedRecommendation(): void
    {
        if ($this->recommendationOverridden) {
            return;
        }

        $this->syncingRecommendation = true;
        $this->recommendation = $this->suggestedRecommendation;
        $this->syncingRecommendation = false;
    }

    #[Computed]
    public function calculatedOverallRating(): ?float
    {
        $rating = new Rating([
            'rating_mentorship' => $this->rating_mentorship,
            'rating_learning' => $this->rating_learning,
            'rating_real_work' => $this->rating_real_work,
            'rating_team_environment' => $this->rating_team_environment,
            'rating_organization' => $this->rating_organization,
        ]);

        return $rating->calculateOverallRating();
    }

    #[Computed]
    public function suggestedRecommendation(): ?string
    {
        return Rating::recommendationFromOverall($this->calculatedOverallRating)?->value;
    }

    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => array_filter([
                'companyId' => 'required|string',
                'newCompanyName' => $this->companyId === '__new__' ? 'required|string|max:255' : null,
                'newCompanyType' => $this->companyId === '__new__' ? 'required|in:'.implode(',', CompanyType::values()) : null,
                'role_title' => 'nullable|string|max:255',
                'department' => 'nullable|string|max:255',
                'city' => ['required', 'string', 'in:' . implode(',', SaudiCity::values())],
                'duration_months' => 'nullable|integer|min:1|max:12',
                'modality' => 'required|in:' . implode(',', Modality::values()),
                'stipend_sar' => 'nullable|integer|min:0|max:100000',
                'had_supervisor' => 'nullable|boolean',
                'mixed_env' => 'nullable|boolean',
                'job_offer' => 'nullable|boolean',
            ]),
            2 => [
                'rating_mentorship' => 'required|integer|min:1|max:5',
                'rating_learning' => 'required|integer|min:1|max:5',
                'rating_real_work' => 'required|integer|min:1|max:5',
                'rating_team_environment' => 'required|integer|min:1|max:5',
                'rating_organization' => 'required|integer|min:1|max:5',
                'recommendation' => 'required|in:' . implode(',', Recommendation::values()),
                'review_text' => 'nullable|string|max:5000',
                'pros' => 'nullable|string|max:500',
                'cons' => 'nullable|string|max:500',
            ],
            3 => array_filter([
                'reviewer_name' => 'nullable|string|max:255',
                'reviewer_university' => 'nullable|string|max:255',
                'reviewer_college' => 'nullable|string|max:255',
                'reviewer_major' => 'nullable|string|max:255',
                'reviewer_degree' => 'nullable|in:' . implode(',', ReviewerDegree::values()),
                'application_method' => 'nullable|string|max:500',
                'willing_to_help' => 'nullable|boolean',
                'contact_method' => $this->willing_to_help === true ? 'required|string|max:500' : 'nullable|string|max:500',
                'turnstile' => config('turnstile.enabled') ? ['required', new TurnstileRule] : null,
            ]),
            default => [],
        };
    }

    protected function messages(): array
    {
        return [
            'contact_method.required' => 'يرجى إدخال طريقة التواصل إن كنت مستعداً للمساعدة.',
            'reviewer_degree.in' => 'يرجى اختيار درجة علمية صحيحة.',
            'companyId.required' => 'يرجى اختيار جهة أو إنشاء واحدة جديدة.',
            'newCompanyName.required' => 'يرجى إدخال اسم الجهة الجديدة.',
            'newCompanyType.required' => 'يرجى اختيار نوع الجهة الجديدة.',
            'city.required' => 'يرجى اختيار المدينة.',
            'duration_months.min' => 'المدة يجب أن تكون شهرًا واحدًا على الأقل.',
            'duration_months.max' => 'المدة يجب ألا تتجاوز 12 شهرًا.',
            'modality.required' => 'يرجى تحديد نمط التدريب.',
            'rating_mentorship.required' => 'تقييم الإرشاد مطلوب.',
            'rating_learning.required' => 'تقييم القيمة التعليمية مطلوب.',
            'rating_real_work.required' => 'تقييم التعرّض للعمل الحقيقي مطلوب.',
            'rating_team_environment.required' => 'تقييم بيئة الفريق مطلوب.',
            'rating_organization.required' => 'تقييم التنظيم ووضوح التوقعات مطلوب.',
            'recommendation.required' => 'يرجى اختيار توصيتك.',
            'turnstile.required' => 'يرجى إكمال التحقق الأمني.',
        ];
    }

    protected function dispatchStepChangedEvent(): void
    {
        $this->dispatch('rating-wizard-step-changed');
    }

    protected function validateOrScroll(array $rules): void
    {
        try {
            $this->validate($rules, $this->messages());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('rating-wizard-validation-failed');
            throw $e;
        }
    }

    public function nextStep(): void
    {
        $this->validateOrScroll($this->rulesForStep($this->currentStep));

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
            $this->dispatchStepChangedEvent();
        }
    }

    public function prevStep(): void
    {
        $this->resetErrorBag();
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->dispatchStepChangedEvent();
        }
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->currentStep) {
            $this->resetErrorBag();
            $this->currentStep = $step;
            $this->dispatchStepChangedEvent();
            return;
        }

        for ($s = $this->currentStep; $s < $step; $s++) {
            $this->validateOrScroll($this->rulesForStep($s));
        }

        $this->currentStep = $step;
        $this->dispatchStepChangedEvent();
    }

    public function save()
    {
        $allRules = [];
        for ($s = 1; $s <= $this->totalSteps; $s++) {
            $allRules = array_merge($allRules, $this->rulesForStep($s));
        }
        $this->validateOrScroll($allRules);

        if ($this->companyId === '__new__') {
            $company = Company::create([
                'name' => $this->newCompanyName,
                'type' => $this->newCompanyType,
                'status' => 'pending',
            ]);
            $targetCompanyId = $company->id;
            $redirectRoute = route('companies.index');
            $successMessage = 'شكراً! تم إرسال تقييمك وسيظهر بعد الموافقة على الجهة.';
        } else {
            $company = Company::approved()->findOrFail($this->companyId);
            $targetCompanyId = $company->id;
            $redirectRoute = route('companies.show', $company);
            $successMessage = 'شكراً! تم إضافة تقييمك بنجاح.';
        }

        Rating::create([
            'company_id' => $targetCompanyId,
            'role_title' => $this->role_title,
            'department' => $this->department,
            'city' => $this->city,
            'duration_months' => $this->duration_months,
            'modality' => $this->modality,
            'stipend_sar' => $this->stipend_sar,
            'had_supervisor' => $this->had_supervisor,
            'mixed_env' => $this->mixed_env,
            'job_offer' => $this->job_offer,
            'rating_mentorship' => $this->rating_mentorship,
            'rating_learning' => $this->rating_learning,
            'rating_real_work' => $this->rating_real_work,
            'rating_team_environment' => $this->rating_team_environment,
            'rating_organization' => $this->rating_organization,
            'recommendation' => $this->recommendation ?? $this->suggestedRecommendation?->value,
            'review_text' => filled($this->review_text) ? $this->review_text : null,
            'pros' => $this->pros,
            'cons' => $this->cons,
            'reviewer_name' => $this->reviewer_name,
            'reviewer_university' => $this->reviewer_university,
            'reviewer_college' => $this->reviewer_college,
            'reviewer_major' => $this->reviewer_major,
            'reviewer_degree' => $this->reviewer_degree,
            'application_method' => $this->application_method,
            'willing_to_help' => $this->willing_to_help,
            'contact_method' => $this->willing_to_help ? $this->contact_method : null,
        ]);

        session()->flash('success', $successMessage);
        return $this->redirect($redirectRoute, navigate: true);
    }
}; ?>

@php
    $inputClass = 'w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none';
@endphp

<div
    class="mx-auto max-w-2xl space-y-6"
    x-data
    x-on:rating-wizard-step-changed.window="window.scrollTo(0, 0)"
    x-on:rating-wizard-validation-failed.window="$nextTick(() => { const el = $el.querySelector('.text-red-600'); if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' }); })"
>
    <a href="{{ route('companies.index') }}" wire:navigate class="group inline-flex items-center gap-1 text-sm font-medium text-slate-500 transition-colors hover:text-slate-900">
        <svg class="size-4 transition-transform group-hover:-translate-x-0.5 rtl:group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        العودة للجهات
    </a>

    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">أضف تقييم</h1>
        <p class="mt-2 text-slate-500">شارك تجربتك في التدريب لمساعدة الطلاب الآخرين.</p>
    </div>

    {{-- Progress header --}}
    @php
        $stepLabels = [
            1 => 'تفاصيل التدريب',
            2 => 'تقييمك وتجربتك',
            3 => 'عنك',
        ];
    @endphp

    <nav aria-label="خطوات النموذج" class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
        <ol class="flex items-center justify-between gap-2 px-10">
            @foreach($stepLabels as $num => $title)
                @php
                    $isCurrent = $currentStep === $num;
                    $isCompleted = $currentStep > $num;
                    $isClickable = $num < $currentStep;
                @endphp
                <li class="flex items-center gap-2 {{ $loop->last ? '' : 'flex-1' }}">
                    <button
                        type="button"
                        @if($isClickable) wire:click="goToStep({{ $num }})" @else disabled @endif
                        class="group flex items-center gap-2 text-start {{ $isClickable ? 'cursor-pointer' : 'cursor-default' }}"
                        aria-current="{{ $isCurrent ? 'step' : 'false' }}"
                    >
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold transition-all
                            {{ $isCompleted ? 'bg-blue-500 text-white' : '' }}
                            {{ $isCurrent ? 'bg-blue-500 text-white ring-4 ring-blue-500/15' : '' }}
                            {{ ! $isCurrent && ! $isCompleted ? 'bg-slate-100 text-slate-400' : '' }}
                        ">
                            @if($isCompleted)
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @else
                                {{ $num }}
                            @endif
                        </span>
                        <span class="hidden sm:block text-xs font-medium transition-colors
                            {{ $isCurrent ? 'text-slate-900' : '' }}
                            {{ $isCompleted ? 'text-slate-600' : '' }}
                            {{ ! $isCurrent && ! $isCompleted ? 'text-slate-400' : '' }}
                        ">{{ $title }}</span>
                    </button>
                    @if(! $loop->last)
                        <span class="flex-1 h-0.5 rounded-full {{ $isCompleted ? 'bg-blue-500' : 'bg-slate-200' }} transition-colors"></span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>

    <form wire:submit="save" class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xs">

        {{-- STEP 1: COMPANY & ROLE --}}
        <div x-show="$wire.currentStep === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-2 rtl:-translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-5">
            <div class="mb-1">
                <h2 class="text-lg font-semibold text-slate-900">تفاصيل التدريب</h2>
                <p class="mt-1 text-sm text-slate-500">معلومات موضوعية عن الجهة، الدور، والتدريب نفسه.</p>
            </div>

            {{-- Company combobox --}}
            <div>
                <x-public.nice-select
                    label="الجهة"
                    name="companyId"
                    wire:model.live="companyId"
                    :options="$companyOptions"
                    search-function="searchCompanies"
                    placeholder="ابحث عن جهة أو اكتب اسم جديدة..."
                    no-result-text="لا توجد نتائج — اكتب الاسم لإنشاء جهة جديدة"
                    debounce="300ms"
                    searchable
                    :required="true"
                >
                    @scope('item', $option)
                        @if(data_get($option, 'id') === '__new__')
                            <div class="flex items-center gap-2 p-3 border-s-4 border-s-blue-500 bg-blue-50/60 text-blue-700">
                                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                <span class="font-medium">إنشاء جهة جديدة:</span>
                                <span class="truncate">{{ data_get($option, 'name') }}</span>
                            </div>
                        @else
                            <div class="p-3 border-s-4 border-s-transparent hover:bg-slate-50">
                                <div class="font-medium text-slate-900">{{ data_get($option, 'name') }}</div>
                            </div>
                        @endif
                    @endscope
                </x-public.nice-select>

                @if($companyId === '__new__')
                    <div class="mt-3 flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50/60 p-3 text-sm text-blue-800">
                        <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="flex-1">ستُنشأ جهة جديدة باسم <strong>{{ $newCompanyName }}</strong> وستُعرض بعد الموافقة.</span>
                        <button type="button" wire:click="clearCompany" class="text-xs font-medium text-blue-600 underline hover:text-blue-700">تغيير</button>
                    </div>

                    <div class="mt-5">
                        <x-public.form-field label="نوع الجهة الجديدة" name="newCompanyType" :required="true">
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach(\App\Enums\CompanyType::options() as $val => $lbl)
                                    <label class="relative">
                                        <input type="radio" wire:model="newCompanyType" value="{{ $val }}" class="peer sr-only" />
                                        <span class="flex cursor-pointer items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-600 transition-all hover:border-slate-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 peer-checked:ring-2 peer-checked:ring-blue-500/20">{{ $lbl }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </x-public.form-field>
                    </div>
                @endif
                @error('newCompanyName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('newCompanyType') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Role --}}
            <x-public.form-field label="المسمى الوظيفي" name="role_title">
                <input type="text" wire:model="role_title" id="role_title" placeholder="مثلاً: مهندس برمجيات" class="{{ $inputClass }}" />
            </x-public.form-field>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-public.form-field label="القسم" name="department">
                    <input type="text" wire:model="department" id="department" placeholder="مثلاً: هندسة البرمجيات، تحليل البيانات" class="{{ $inputClass }}" />
                </x-public.form-field>

                {{-- City combobox --}}
                <div>
                    <x-public.nice-select
                        label="المدينة"
                        name="city"
                        wire:model="city"
                        :options="$cityOptions"
                        placeholder="ابحث عن مدينة..."
                        no-result-text="لا توجد مدينة بهذا الاسم"
                        searchable
                        offline
                        :required="true"
                    >
                        @scope('item', $option)
                            <div class="p-3 border-s-4 border-s-transparent hover:bg-slate-50">
                                <div class="font-medium text-slate-900">{{ data_get($option, 'name') }}</div>
                            </div>
                        @endscope
                    </x-public.nice-select>
                </div>
            </div>

            <x-public.nice-select
                label="المدة (بالأشهر)"
                name="duration_months"
                wire:model="duration_months"
                :options="$this->durationMonthOptions"
                placeholder="اختر عدد الأشهر..."
                no-result-text="لا توجد نتائج"
                searchable
                offline
            >
                @scope('item', $option)
                    <div class="p-3 border-s-4 border-s-transparent hover:bg-slate-50">
                        <div class="font-medium text-slate-900">{{ data_get($option, 'name') }} {{ (int) data_get($option, 'id') === 1 ? 'شهر' : 'أشهر' }}</div>
                    </div>
                @endscope
            </x-public.nice-select>

            {{-- Modality — pill radio --}}
            <x-public.form-field label="نمط التدريب" name="modality" :required="true">
                <div class="grid grid-cols-3 gap-2">
                    @foreach(\App\Enums\Modality::cases() as $case)
                        <label class="relative">
                            <input type="radio" wire:model="modality" value="{{ $case->value }}" class="peer sr-only" />
                            <span class="flex cursor-pointer items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-600 transition-all hover:border-slate-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 peer-checked:ring-2 peer-checked:ring-blue-500/20">{{ $case->label() }}</span>
                        </label>
                    @endforeach
                </div>
            </x-public.form-field>

            {{-- Stipend --}}
            <x-public.form-field label="المكافأة الشهرية (ريال سعودي)" name="stipend_sar">
                <div class="relative">
                    <input type="number" wire:model="stipend_sar" id="stipend_sar" min="0" placeholder="اتركه فارغاً إذا كان التدريب غير مدفوع" class="{{ $inputClass }} pe-14" />
                    <span class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-4 text-xs font-medium text-slate-400">ر.س</span>
                </div>
            </x-public.form-field>

            {{-- Yes/no facts about the placement --}}
            <div class="space-y-3">
                @php
                    $yesNoQuestions = [
                        'had_supervisor' => 'هل كان لديك مرشد/مشرف مباشر؟',
                        'mixed_env' => 'هل بيئة العمل مختلطة؟',
                        'job_offer' => 'هل انتهى التدريب بعرض وظيفي (دوام كامل/جزئي)؟',
                    ];
                @endphp
                @foreach($yesNoQuestions as $field => $question)
                    <div class="flex flex-col gap-2 rounded-lg border border-slate-200 bg-slate-50/50 p-3 sm:flex-row sm:items-center sm:justify-between">
                        <span class="text-sm font-medium text-slate-700">{{ $question }}</span>
                        <div class="flex gap-2">
                            <label class="relative">
                                <input type="radio" wire:model="{{ $field }}" value="1" class="peer sr-only" />
                                <span class="flex cursor-pointer items-center justify-center rounded-md border border-slate-200 bg-white px-4 py-1.5 text-xs font-medium text-slate-500 transition-all hover:border-slate-300 peer-checked:border-sky-500 peer-checked:bg-sky-50 peer-checked:text-sky-700 peer-checked:ring-2 peer-checked:ring-sky-500/20">نعم</span>
                            </label>
                            <label class="relative">
                                <input type="radio" wire:model="{{ $field }}" value="0" class="peer sr-only" />
                                <span class="flex cursor-pointer items-center justify-center rounded-md border border-slate-200 bg-white px-4 py-1.5 text-xs font-medium text-slate-500 transition-all hover:border-slate-300 peer-checked:border-slate-400 peer-checked:bg-slate-100 peer-checked:text-slate-700 peer-checked:ring-2 peer-checked:ring-slate-400/20">لا</span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- STEP 2: RATING & EXPERIENCE --}}
        <div x-show="$wire.currentStep === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-2 rtl:-translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-6">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">تقييمك وتجربتك</h2>
                <p class="mt-1 text-sm text-slate-500">قيّم تجربتك على المعايير الخمسة، ثم شاركنا توصيتك وتفاصيل تجربتك.</p>
            </div>

            {{-- Star pickers — weighted criteria --}}
            <div class="space-y-5">
                <div class="space-y-4">
                    @php
                        $criteriaFields = [
                            'rating_learning' => 'القيمة التعليمية',
                            'rating_mentorship' => 'جودة الإرشاد',
                            'rating_real_work' => 'العمل الحقيقي والمشاريع',
                            'rating_team_environment' => 'بيئة الفريق',
                            'rating_organization' => 'التنظيم ووضوح التوقعات',
                        ];
                    @endphp
                    @foreach($criteriaFields as $field => $label)
                        <div wire:key="rating-metric-{{ $field }}">
                            <x-public.star-picker :field="$field" :label="$label" :value="$$field" :required="true" />
                        </div>
                    @endforeach
                </div>

                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">التقييم العام</h3>
                        </div>

                        @if($this->calculatedOverallRating !== null)
                            <x-public.overall-score :value="$this->calculatedOverallRating" wire:key="overall-score-{{ number_format($this->calculatedOverallRating, 2) }}" compact />
                        @else
                            <div class="shrink-0 rounded-xl bg-white px-4 py-3 text-sm font-medium text-slate-400 ring-1 ring-inset ring-slate-200">أكمل التقييمات الخمسة</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Recommendation — big pills --}}
            <x-public.form-field label="هل تنصح بهذا التدريب؟" name="recommendation" :required="true">
                @if($this->suggestedRecommendation)
                    <p class="mb-3 text-xs text-slate-500">
                        الاقتراح الافتراضي مبني على التقييم المحسوب:
                        <span class="font-semibold text-slate-700">{{ Recommendation::from($this->suggestedRecommendation)->label() }}</span>
                        @if($this->calculatedOverallRating !== null)
                            <span class="tabular-nums">({{ number_format($this->calculatedOverallRating, 1) }}/5)</span>
                        @endif
                    </p>
                @endif
                <div class="grid grid-cols-3 gap-2">
                    @foreach(\App\Enums\Recommendation::cases() as $case)
                        @php
                            $recommendationCheckedClasses = match ($case->value) {
                                'yes' => 'peer-checked:border-sky-500 peer-checked:bg-sky-50 peer-checked:text-sky-700 peer-checked:ring-2 peer-checked:ring-sky-500/20',
                                'maybe' => 'peer-checked:border-slate-400 peer-checked:bg-slate-100 peer-checked:text-slate-700 peer-checked:ring-2 peer-checked:ring-slate-400/20',
                                'no' => 'peer-checked:border-slate-300 peer-checked:bg-slate-50 peer-checked:text-slate-600 peer-checked:ring-2 peer-checked:ring-slate-300/30',
                            };
                        @endphp
                        <label class="relative">
                            <input type="radio" wire:model.live="recommendation" value="{{ $case->value }}" class="peer sr-only" />
                            <span class="flex cursor-pointer items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-600 transition-all hover:border-slate-300 {{ $recommendationCheckedClasses }}">{{ $case->label() }}</span>
                        </label>
                    @endforeach
                </div>
            </x-public.form-field>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-public.form-field label="المزايا" name="pros">
                    <textarea wire:model="pros" id="pros" rows="3" placeholder="أبرز ما أعجبك" class="{{ $inputClass }}"></textarea>
                </x-public.form-field>

                <x-public.form-field label="العيوب" name="cons">
                    <textarea wire:model="cons" id="cons" rows="3" placeholder="ما يمكن تحسينه" class="{{ $inputClass }}"></textarea>
                </x-public.form-field>
            </div>

            <x-public.form-field label="المراجعة" name="review_text">
                <textarea wire:model="review_text" id="review_text" rows="6"
                    placeholder="اكتب تجربتك بالتفصيل: الدور، المشاريع، الفريق، ما تعلّمت..."
                    class="{{ $inputClass }}"></textarea>
            </x-public.form-field>
        </div>

        {{-- STEP 3: ABOUT YOU & SUBMIT --}}
        <div x-show="$wire.currentStep === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-2 rtl:-translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">عنك</h2>
                <p class="mt-1 text-sm text-slate-500">معلومات اختيارية تساعد القرّاء على فهم خلفيتك. ثم أرسل تقييمك.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-public.form-field label="اسمك" name="reviewer_name">
                    <input type="text" wire:model="reviewer_name" id="reviewer_name" placeholder="سيظهر بجانب مراجعتك" class="{{ $inputClass }}" />
                </x-public.form-field>

                <x-public.form-field label="الجامعة" name="reviewer_university">
                    <input type="text" wire:model="reviewer_university" id="reviewer_university" placeholder="مثلاً: جامعة الملك عبدالله" class="{{ $inputClass }}" />
                </x-public.form-field>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-public.form-field label="الكلية" name="reviewer_college">
                    <input type="text" wire:model="reviewer_college" id="reviewer_college" placeholder="مثلاً: كلية الحاسب الآلي" class="{{ $inputClass }}" />
                </x-public.form-field>

                <x-public.form-field label="التخصص" name="reviewer_major">
                    <input type="text" wire:model="reviewer_major" id="reviewer_major" placeholder="مثلاً: علوم الحاسب" class="{{ $inputClass }}" />
                </x-public.form-field>
            </div>

            <x-public.form-field label="الدرجة العلمية" name="reviewer_degree">
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach(\App\Enums\ReviewerDegree::cases() as $case)
                        <label class="relative">
                            <input type="radio" wire:model="reviewer_degree" value="{{ $case->value }}" class="peer sr-only" />
                            <span class="flex cursor-pointer items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-600 transition-all hover:border-slate-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 peer-checked:ring-2 peer-checked:ring-blue-500/20">{{ $case->label() }}</span>
                        </label>
                    @endforeach
                </div>
            </x-public.form-field>

            {{-- How they applied --}}
            <x-public.form-field label="كيف قدّمت؟" name="application_method">
                <input type="text" wire:model="application_method" id="application_method" placeholder="مثلاً: عبر الموقع الرسمي، عن طريق زميل، طلب تقديم مباشر..." class="{{ $inputClass }}" />
            </x-public.form-field>

            {{-- Willing to help others --}}
            <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-700">هل أنت مستعد تساعد غيرك في الحصول على قبول؟</p>
                        <p class="mt-0.5 text-xs text-slate-500">إذا وافقت، سيُعرض معلومات التواصل معك مع تقييمك.</p>
                    </div>
                    <div class="flex gap-2">
                        <label class="relative">
                            <input type="radio" wire:model.live="willing_to_help" value="1" class="peer sr-only" />
                            <span class="flex cursor-pointer items-center justify-center rounded-md border border-slate-200 bg-white px-4 py-1.5 text-xs font-medium text-slate-500 transition-all hover:border-slate-300 peer-checked:border-sky-500 peer-checked:bg-sky-50 peer-checked:text-sky-700 peer-checked:ring-2 peer-checked:ring-sky-500/20">نعم</span>
                        </label>
                        <label class="relative">
                            <input type="radio" wire:model.live="willing_to_help" value="0" class="peer sr-only" />
                            <span class="flex cursor-pointer items-center justify-center rounded-md border border-slate-200 bg-white px-4 py-1.5 text-xs font-medium text-slate-500 transition-all hover:border-slate-300 peer-checked:border-slate-400 peer-checked:bg-slate-100 peer-checked:text-slate-700 peer-checked:ring-2 peer-checked:ring-slate-400/20">لا</span>
                        </label>
                    </div>
                </div>

                @if($willing_to_help === true)
                    <div class="border-t border-slate-200 pt-3">
                        <x-public.form-field label="طريقة التواصل" name="contact_method" :required="true">
                            <input type="text" wire:model="contact_method" id="contact_method"
                                placeholder="مثلاً: تويتر: @username، واتساب: 05xxxxxxxx، بريد: name@example.com"
                                class="{{ $inputClass }}" />
                        </x-public.form-field>
                    </div>
                @endif
            </div>

            @if(config('turnstile.enabled'))
                <div
                    x-data="{
                        widgetId: null,
                        rendered: false,
                        render() {
                            if (this.rendered || typeof turnstile === 'undefined') return;
                            this.rendered = true;
                            this.widgetId = turnstile.render($refs.box, {
                                sitekey: '{{ config('turnstile.sitekey') }}',
                                language: 'ar',
                                callback: (token) => $wire.set('turnstile', token),
                                'expired-callback': () => $wire.set('turnstile', ''),
                                'error-callback': () => $wire.set('turnstile', ''),
                            });
                        }
                    }"
                    x-init="
                        $watch('$wire.currentStep', step => { if (step === 3) $nextTick(() => render()); });
                        if ($wire.currentStep === 3) $nextTick(() => render());
                        window.addEventListener('turnstile-loaded', () => { if ($wire.currentStep === 3) render(); });
                    "
                >
                    <div x-ref="box"></div>
                    @error('turnstile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif
        </div>

        {{-- WIZARD NAVIGATION --}}
        <div class="mt-8 flex items-center justify-between gap-3 border-t border-slate-100 pt-6">
            @if($currentStep > 1)
                <button type="button" wire:click="prevStep"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-xs transition-all hover:bg-slate-50 active:scale-[0.98]">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    السابق
                </button>
            @else
                <div></div>
            @endif

            @if($currentStep < $totalSteps)
                <button type="button" wire:click="nextStep"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-500 px-5 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-600 hover:shadow-sm active:scale-[0.98]">
                    التالي
                    <svg class="size-4 rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            @else
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-500 px-5 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-600 hover:shadow-sm active:scale-[0.98] disabled:opacity-50"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <svg wire:loading.remove wire:target="save" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <svg wire:loading wire:target="save" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    <span wire:loading.remove wire:target="save" class="whitespace-nowrap">إرسال التقييم</span>
                    <span wire:loading wire:target="save" class="whitespace-nowrap">جاري الإرسال...</span>
                </button>
            @endif
        </div>
    </form>
</div>
