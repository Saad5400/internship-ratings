<?php

use App\Enums\SaudiCity;
use App\Models\Company;
use App\Models\Rating;
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

    public ?string $role_title = null;
    public ?string $department = null;
    public ?string $city = null;
    public ?int $duration_months = null;
    public ?string $sector = null;          // government | private | nonprofit | other
    public ?string $modality = null;        // onsite | hybrid | remote

    // Step 2 — facts & scores
    public ?int $stipend_sar = null;
    public ?bool $had_supervisor = null;
    public ?bool $mixed_env = null;
    public ?bool $job_offer = null;
    public ?int $rating_mentorship = null;
    public ?int $rating_learning = null;
    public ?int $rating_culture = null;
    public ?int $rating_compensation = null;
    public ?int $overall_rating = null;

    // Step 3 — experience
    public ?string $recommendation = null;  // yes | maybe | no
    public string $review_text = '';
    public ?string $pros = null;
    public ?string $cons = null;
    public ?string $reviewer_name = null;
    public ?string $reviewer_major = null;
    public string $turnstile = '';

    public function mount(?int $company = null): void
    {
        $this->companyOptions = $this->defaultCompanyOptions();

        if ($company) {
            $selected = Company::approved()->find($company);
            if ($selected) {
                $this->companyId = (string) $selected->id;
                $this->companyOptions = collect($this->companyOptions)
                    ->push(['id' => (string) $selected->id, 'name' => $selected->name])
                    ->unique('id')
                    ->values()
                    ->all();
            }
        }
    }

    protected function defaultCompanyOptions(): array
    {
        return Company::approved()
            ->orderBy('name')
            ->take(8)
            ->get(['id', 'name'])
            ->map(fn ($c) => ['id' => (string) $c->id, 'name' => $c->name])
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

        $this->companyOptions = $results->values()->all();
    }

    public function updatedCompanyId($value): void
    {
        if ($value === '__new__') {
            $this->newCompanyName = $this->companySearch;
        } else {
            $this->newCompanyName = null;
        }
    }

    public function clearCompany(): void
    {
        $this->companyId = null;
        $this->newCompanyName = null;
        $this->companySearch = '';
        $this->companyOptions = $this->defaultCompanyOptions();
    }

    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => array_filter([
                'companyId' => 'required|string',
                'newCompanyName' => $this->companyId === '__new__' ? 'required|string|max:255' : null,
                'role_title' => 'nullable|string|max:255',
                'department' => 'nullable|string|max:255',
                'city' => ['nullable', 'string', 'in:' . implode(',', SaudiCity::values())],
                'duration_months' => 'nullable|integer|min:1|max:12',
                'sector' => 'nullable|in:government,private,nonprofit,other',
                'modality' => 'required|in:onsite,hybrid,remote',
            ]),
            2 => [
                'stipend_sar' => 'nullable|integer|min:0|max:100000',
                'had_supervisor' => 'nullable|boolean',
                'mixed_env' => 'nullable|boolean',
                'job_offer' => 'nullable|boolean',
                'rating_mentorship' => 'required|integer|min:1|max:5',
                'rating_learning' => 'required|integer|min:1|max:5',
                'rating_culture' => 'required|integer|min:1|max:5',
                'rating_compensation' => 'required|integer|min:1|max:5',
                'overall_rating' => 'required|integer|min:1|max:5',
            ],
            3 => array_filter([
                'recommendation' => 'required|in:yes,maybe,no',
                'review_text' => 'required|string|min:10|max:5000',
                'pros' => 'nullable|string|max:500',
                'cons' => 'nullable|string|max:500',
                'reviewer_name' => 'nullable|string|max:255',
                'reviewer_major' => 'nullable|string|max:255',
                'turnstile' => config('turnstile.enabled') ? ['required', new TurnstileRule] : null,
            ]),
            default => [],
        };
    }

    protected function messages(): array
    {
        return [
            'companyId.required' => 'يرجى اختيار جهة أو إنشاء واحدة جديدة.',
            'newCompanyName.required' => 'يرجى إدخال اسم الجهة الجديدة.',
            'duration_months.min' => 'المدة يجب أن تكون شهرًا واحدًا على الأقل.',
            'duration_months.max' => 'المدة يجب ألا تتجاوز 12 شهرًا.',
            'modality.required' => 'يرجى تحديد نمط التدريب.',
            'rating_mentorship.required' => 'تقييم الإرشاد مطلوب.',
            'rating_learning.required' => 'تقييم القيمة التعليمية مطلوب.',
            'rating_culture.required' => 'تقييم بيئة العمل مطلوب.',
            'rating_compensation.required' => 'تقييم المكافأة والمزايا مطلوب.',
            'overall_rating.required' => 'التقييم العام مطلوب.',
            'recommendation.required' => 'يرجى اختيار توصيتك.',
            'review_text.required' => 'المراجعة مطلوبة.',
            'review_text.min' => 'المراجعة يجب أن تكون 10 أحرف على الأقل.',
            'turnstile.required' => 'يرجى إكمال التحقق الأمني.',
        ];
    }

    public function nextStep(): void
    {
        $this->validate($this->rulesForStep($this->currentStep), $this->messages());

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function prevStep(): void
    {
        $this->resetErrorBag();
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->currentStep) {
            $this->resetErrorBag();
            $this->currentStep = $step;
            return;
        }

        for ($s = $this->currentStep; $s < $step; $s++) {
            $this->validate($this->rulesForStep($s), $this->messages());
        }

        $this->currentStep = $step;
    }

    public function save()
    {
        $allRules = [];
        for ($s = 1; $s <= $this->totalSteps; $s++) {
            $allRules = array_merge($allRules, $this->rulesForStep($s));
        }
        $this->validate($allRules, $this->messages());

        if ($this->companyId === '__new__') {
            $company = Company::create([
                'name' => $this->newCompanyName,
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
            'sector' => $this->sector,
            'modality' => $this->modality,
            'stipend_sar' => $this->stipend_sar,
            'had_supervisor' => $this->had_supervisor,
            'mixed_env' => $this->mixed_env,
            'job_offer' => $this->job_offer,
            'rating_mentorship' => $this->rating_mentorship,
            'rating_learning' => $this->rating_learning,
            'rating_culture' => $this->rating_culture,
            'rating_compensation' => $this->rating_compensation,
            'overall_rating' => $this->overall_rating,
            'recommendation' => $this->recommendation,
            'review_text' => $this->review_text,
            'pros' => $this->pros,
            'cons' => $this->cons,
            'reviewer_name' => $this->reviewer_name,
            'reviewer_major' => $this->reviewer_major,
        ]);

        session()->flash('success', $successMessage);
        return $this->redirect($redirectRoute, navigate: true);
    }
}; ?>

@php
    $inputClass = 'w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm text-slate-900 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none';
@endphp

<div class="mx-auto max-w-2xl space-y-6">
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
            1 => 'الجهة والدور',
            2 => 'الحقائق والتقييم',
            3 => 'التجربة',
        ];
    @endphp

    <nav aria-label="خطوات النموذج" class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
        <ol class="flex items-center justify-between gap-2">
            @foreach($stepLabels as $num => $title)
                @php
                    $isCurrent = $currentStep === $num;
                    $isCompleted = $currentStep > $num;
                    $isClickable = $num < $currentStep;
                @endphp
                <li class="flex flex-1 items-center gap-2">
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
                <h2 class="text-lg font-semibold text-slate-900">الجهة والدور</h2>
                <p class="mt-1 text-sm text-slate-500">ابحث عن الجهة وأخبرنا عن دورك فيها.</p>
            </div>

            {{-- Company combobox --}}
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">
                    الجهة <span class="text-red-500">*</span>
                </label>
                <x-mary-choices
                    wire:model.live="companyId"
                    :options="$companyOptions"
                    search-function="searchCompanies"
                    placeholder="ابحث عن جهة أو اكتب اسم جديدة..."
                    no-result-text="لا توجد نتائج — اكتب الاسم لإنشاء جهة جديدة"
                    debounce="300ms"
                    single
                    searchable
                    clearable
                    omit-error
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
                </x-mary-choices>

                @if($companyId === '__new__')
                    <div class="mt-3 flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50/60 p-3 text-sm text-blue-800">
                        <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="flex-1">ستُنشأ جهة جديدة باسم <strong>{{ $newCompanyName }}</strong> وستُعرض بعد الموافقة.</span>
                        <button type="button" wire:click="clearCompany" class="text-xs font-medium text-blue-600 underline hover:text-blue-700">تغيير</button>
                    </div>
                @endif

                @error('companyId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('newCompanyName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Role --}}
            <x-public.form-field label="المسمى الوظيفي" name="role_title">
                <input type="text" wire:model="role_title" id="role_title" placeholder="مثلاً: مهندس برمجيات" class="{{ $inputClass }}" />
            </x-public.form-field>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-public.form-field label="القسم" name="department">
                    <input type="text" wire:model="department" id="department" placeholder="اختياري" class="{{ $inputClass }}" />
                </x-public.form-field>

                <x-public.form-field label="المدينة" name="city">
                    <select wire:model="city" id="city" class="{{ $inputClass }}">
                        <option value="">— اختر المدينة —</option>
                        @foreach(\App\Enums\SaudiCity::toOptions() as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-public.form-field>
            </div>

            <x-public.form-field label="المدة (بالاشهر)" name="duration_months">
                <select wire:model="duration_months" id="duration_months" class="{{ $inputClass }}">
                    <option value="">اختياري</option>
                    @for($month = 1; $month <= 12; $month++)
                        <option value="{{ $month }}">{{ $month }}</option>
                    @endfor
                </select>
            </x-public.form-field>

            {{-- Sector — pill radio --}}
            <x-public.form-field label="نوع الجهة" name="sector">
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach(['government' => 'حكومي', 'private' => 'خاص', 'nonprofit' => 'غير ربحي', 'other' => 'أخرى'] as $val => $lbl)
                        <label class="relative flex cursor-pointer items-center justify-center rounded-lg border px-3 py-2.5 text-sm font-medium transition-all {{ $sector === $val ? 'border-blue-500 bg-blue-50 text-blue-700 ring-2 ring-blue-500/20' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' }}">
                            <input type="radio" wire:model.live="sector" value="{{ $val }}" class="sr-only" />
                            <span>{{ $lbl }}</span>
                        </label>
                    @endforeach
                </div>
            </x-public.form-field>

            {{-- Modality — pill radio --}}
            <x-public.form-field label="نمط التدريب" name="modality" :required="true">
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['onsite' => 'حضوري', 'hybrid' => 'هجين', 'remote' => 'عن بُعد'] as $val => $lbl)
                        <label class="relative flex cursor-pointer items-center justify-center rounded-lg border px-3 py-2.5 text-sm font-medium transition-all {{ $modality === $val ? 'border-blue-500 bg-blue-50 text-blue-700 ring-2 ring-blue-500/20' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' }}">
                            <input type="radio" wire:model.live="modality" value="{{ $val }}" class="sr-only" />
                            <span>{{ $lbl }}</span>
                        </label>
                    @endforeach
                </div>
            </x-public.form-field>
        </div>

        {{-- STEP 2: FACTS & RATING --}}
        <div x-show="$wire.currentStep === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-2 rtl:-translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-6">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">الحقائق والتقييم</h2>
                <p class="mt-1 text-sm text-slate-500">هذه الحقائق مفيدة للطلاب الآخرين قبل التقديم.</p>
            </div>

            {{-- Stipend --}}
            <x-public.form-field label="المكافأة الشهرية (ريال سعودي)" name="stipend_sar">
                <div class="relative">
                    <input type="number" wire:model="stipend_sar" id="stipend_sar" min="0" placeholder="اتركه فارغاً إذا كان التدريب غير مدفوع" class="{{ $inputClass }} pe-14" />
                    <span class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-4 text-xs font-medium text-slate-400">ر.س</span>
                </div>
            </x-public.form-field>

            {{-- Yes/no questions --}}
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
                            <label class="relative flex cursor-pointer items-center justify-center rounded-md border px-4 py-1.5 text-xs font-medium transition-all {{ $$field === true ? 'border-green-500 bg-green-50 text-green-700' : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300' }}">
                                <input type="radio" wire:model.live="{{ $field }}" value="1" class="sr-only" />
                                نعم
                            </label>
                            <label class="relative flex cursor-pointer items-center justify-center rounded-md border px-4 py-1.5 text-xs font-medium transition-all {{ $$field === false ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300' }}">
                                <input type="radio" wire:model.live="{{ $field }}" value="0" class="sr-only" />
                                لا
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Star pickers — 4 criteria + overall --}}
            <div class="space-y-5 pt-2">
                <div class="space-y-4">
                    @php
                        $criteriaFields = [
                            'rating_mentorship' => 'الإرشاد والدعم',
                            'rating_learning' => 'القيمة التعليمية',
                            'rating_culture' => 'بيئة العمل',
                            'rating_compensation' => 'المكافأة والمزايا',
                        ];
                    @endphp
                    @foreach($criteriaFields as $field => $label)
                        <x-public.star-picker :field="$field" :label="$label" :value="$$field" :required="true" />
                    @endforeach
                </div>

                <div class="border-t border-slate-100 pt-5">
                    <x-public.star-picker field="overall_rating" label="التقييم العام" :value="$overall_rating" :required="true" />
                </div>
            </div>
        </div>

        {{-- STEP 3: EXPERIENCE --}}
        <div x-show="$wire.currentStep === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-2 rtl:-translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" class="space-y-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">التجربة</h2>
                <p class="mt-1 text-sm text-slate-500">شارك تجربتك بالتفصيل وأخبرنا بتوصيتك.</p>
            </div>

            {{-- Recommendation — big pills --}}
            <x-public.form-field label="هل تنصح بهذا التدريب؟" name="recommendation" :required="true">
                <div class="grid grid-cols-3 gap-2">
                    @php
                        $recOptions = [
                            'yes' => ['أنصح به', 'border-green-500 bg-green-50 text-green-700 ring-2 ring-green-500/20'],
                            'maybe' => ['ربما', 'border-amber-500 bg-amber-50 text-amber-700 ring-2 ring-amber-500/20'],
                            'no' => ['لا أنصح', 'border-red-500 bg-red-50 text-red-700 ring-2 ring-red-500/20'],
                        ];
                    @endphp
                    @foreach($recOptions as $val => [$lbl, $activeClass])
                        <label class="relative flex cursor-pointer items-center justify-center rounded-lg border px-3 py-3 text-sm font-semibold transition-all {{ $recommendation === $val ? $activeClass : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' }}">
                            <input type="radio" wire:model.live="recommendation" value="{{ $val }}" class="sr-only" />
                            {{ $lbl }}
                        </label>
                    @endforeach
                </div>
            </x-public.form-field>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-public.form-field label="المزايا" name="pros">
                    <textarea wire:model="pros" id="pros" rows="3" placeholder="أبرز ما أعجبك (اختياري)" class="{{ $inputClass }}"></textarea>
                </x-public.form-field>

                <x-public.form-field label="العيوب" name="cons">
                    <textarea wire:model="cons" id="cons" rows="3" placeholder="ما يمكن تحسينه (اختياري)" class="{{ $inputClass }}"></textarea>
                </x-public.form-field>
            </div>

            <x-public.form-field label="المراجعة" name="review_text" :required="true">
                <textarea wire:model="review_text" id="review_text" rows="6" minlength="10"
                    placeholder="اكتب تجربتك بالتفصيل: الدور، المشاريع، الفريق، ما تعلّمت..."
                    class="{{ $inputClass }}"></textarea>
            </x-public.form-field>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-public.form-field label="اسمك (اختياري)" name="reviewer_name">
                    <input type="text" wire:model="reviewer_name" id="reviewer_name" placeholder="سيظهر بجانب مراجعتك" class="{{ $inputClass }}" />
                </x-public.form-field>

                <x-public.form-field label="تخصصك (اختياري)" name="reviewer_major">
                    <input type="text" wire:model="reviewer_major" id="reviewer_major" placeholder="مثلاً: علوم الحاسب" class="{{ $inputClass }}" />
                </x-public.form-field>
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
                    <span wire:loading.remove wire:target="save" class="inline-flex items-center gap-1.5">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        إرسال التقييم
                    </span>
                    <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                        <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        جاري الإرسال...
                    </span>
                </button>
            @endif
        </div>
    </form>
</div>
