<?php

use App\Enums\Modality;
use App\Enums\Recommendation;
use App\Enums\ReviewerDegree;
use App\Enums\SaudiCity;
use App\Livewire\Concerns\ModeratesRecords;
use App\Models\Rating;
use App\Support\ModerationStatus;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/*
 * A rating workspace, not a CRUD form: moderation (approve / reject / delete)
 * lives in the header for one-click access, while the editable content is
 * grouped into cards with the rarely-touched reviewer details collapsed.
 * The overall score is never edited — the model recomputes it on save.
 */
new #[Layout('layouts.admin')] #[Title('تعديل تقييم')] class extends Component {
    use ModeratesRecords;

    public Rating $rating;

    // الجهة والوظيفة
    public ?int $company_id = null;

    public ?string $role_title = null;

    public ?string $department = null;

    public ?string $city = null;

    public string $status = 'pending';

    // تفاصيل التدريب
    public ?int $duration_months = null;

    public ?string $modality = null;

    public ?int $stipend_sar = null;

    public bool $had_supervisor = false;

    public bool $mixed_env = false;

    public bool $job_offer = false;

    // التقييمات
    public ?int $rating_learning = null;

    public ?int $rating_mentorship = null;

    public ?int $rating_real_work = null;

    public ?int $rating_team_environment = null;

    public ?int $rating_organization = null;

    public ?string $recommendation = null;

    // المراجعة
    public ?string $pros = null;

    public ?string $cons = null;

    public ?string $review_text = null;

    public ?string $application_method = null;

    // المقيّم
    public ?string $reviewer_name = null;

    public ?string $reviewer_major = null;

    public ?string $reviewer_university = null;

    public ?string $reviewer_college = null;

    public ?string $reviewer_degree = null;

    public bool $willing_to_help = false;

    public ?string $contact_method = null;

    public function mount(Rating $rating): void
    {
        $this->rating = $rating->load('company');

        $this->company_id = $rating->company_id;
        $this->role_title = $rating->role_title;
        $this->department = $rating->department;
        $this->city = $rating->city;
        $this->status = (string) $rating->status;
        $this->duration_months = $rating->duration_months;
        $this->modality = $rating->modality?->value;
        $this->stipend_sar = $rating->stipend_sar;
        $this->had_supervisor = (bool) $rating->had_supervisor;
        $this->mixed_env = (bool) $rating->mixed_env;
        $this->job_offer = (bool) $rating->job_offer;
        $this->rating_learning = $rating->rating_learning;
        $this->rating_mentorship = $rating->rating_mentorship;
        $this->rating_real_work = $rating->rating_real_work;
        $this->rating_team_environment = $rating->rating_team_environment;
        $this->rating_organization = $rating->rating_organization;
        $this->recommendation = $rating->recommendation?->value;
        $this->pros = $rating->pros;
        $this->cons = $rating->cons;
        $this->review_text = $rating->review_text;
        $this->application_method = $rating->application_method;
        $this->reviewer_name = $rating->reviewer_name;
        $this->reviewer_major = $rating->reviewer_major;
        $this->reviewer_university = $rating->reviewer_university;
        $this->reviewer_college = $rating->reviewer_college;
        $this->reviewer_degree = $rating->reviewer_degree?->value;
        $this->willing_to_help = (bool) $rating->willing_to_help;
        $this->contact_method = $rating->contact_method;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $metricRules = collect(array_keys(Rating::metricWeights()))
            ->mapWithKeys(fn (string $field) => [$field => ['required', 'integer', 'between:1,5']])
            ->all();

        return [
            'company_id' => ['required', 'exists:companies,id'],
            'role_title' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'in:'.implode(',', SaudiCity::values())],
            'status' => ['required', 'in:pending,approved,rejected'],
            'duration_months' => ['nullable', 'integer', 'between:1,12'],
            'modality' => ['required', 'in:'.implode(',', Modality::values())],
            'stipend_sar' => ['nullable', 'integer', 'min:0'],
            ...$metricRules,
            'recommendation' => ['required', 'in:'.implode(',', Recommendation::values())],
            'pros' => ['nullable', 'string', 'max:1000'],
            'cons' => ['nullable', 'string', 'max:1000'],
            'review_text' => ['nullable', 'string', 'max:5000'],
            'application_method' => ['nullable', 'string', 'max:255'],
            'reviewer_name' => ['nullable', 'string', 'max:255'],
            'reviewer_major' => ['nullable', 'string', 'max:255'],
            'reviewer_university' => ['nullable', 'string', 'max:255'],
            'reviewer_college' => ['nullable', 'string', 'max:255'],
            'reviewer_degree' => ['nullable', 'in:'.implode(',', ReviewerDegree::values())],
            'contact_method' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        $metricMessages = collect(array_keys(Rating::metricWeights()))
            ->mapWithKeys(fn (string $field) => [
                "{$field}.required" => 'قيّم هذا الجانب من 1 إلى 5.',
                "{$field}.between" => 'يجب أن يكون التقييم بين 1 و 5.',
            ])
            ->all();

        return [
            'company_id.required' => 'اختر الجهة.',
            'company_id.exists' => 'الجهة المختارة غير موجودة.',
            'city.in' => 'اختر مدينة من القائمة.',
            'status.required' => 'اختر حالة التقييم.',
            'status.in' => 'حالة التقييم غير صالحة.',
            'duration_months.between' => 'المدة يجب أن تكون بين 1 و 12 شهراً.',
            'modality.required' => 'اختر نمط الحضور.',
            'modality.in' => 'نمط الحضور غير صالح.',
            'stipend_sar.integer' => 'أدخل مبلغاً صحيحاً.',
            'stipend_sar.min' => 'المكافأة لا يمكن أن تكون سالبة.',
            ...$metricMessages,
            'recommendation.required' => 'اختر التوصية.',
            'recommendation.in' => 'التوصية غير صالحة.',
            'reviewer_degree.in' => 'الدرجة العلمية غير صالحة.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        // overall_rating is intentionally never written here — the model's
        // saving() hook recomputes it from the metric fields.
        $this->rating->fill([
            'company_id' => $this->company_id,
            'role_title' => blank($this->role_title) ? null : trim($this->role_title),
            'department' => blank($this->department) ? null : trim($this->department),
            'city' => $this->city,
            'status' => $this->status,
            'duration_months' => $this->duration_months,
            'modality' => $this->modality,
            'stipend_sar' => $this->stipend_sar,
            'had_supervisor' => $this->had_supervisor,
            'mixed_env' => $this->mixed_env,
            'job_offer' => $this->job_offer,
            'rating_learning' => $this->rating_learning,
            'rating_mentorship' => $this->rating_mentorship,
            'rating_real_work' => $this->rating_real_work,
            'rating_team_environment' => $this->rating_team_environment,
            'rating_organization' => $this->rating_organization,
            'recommendation' => $this->recommendation,
            'pros' => blank($this->pros) ? null : trim($this->pros),
            'cons' => blank($this->cons) ? null : trim($this->cons),
            'review_text' => blank($this->review_text) ? null : trim($this->review_text),
            'application_method' => blank($this->application_method) ? null : trim($this->application_method),
            'reviewer_name' => blank($this->reviewer_name) ? null : trim($this->reviewer_name),
            'reviewer_major' => blank($this->reviewer_major) ? null : trim($this->reviewer_major),
            'reviewer_university' => blank($this->reviewer_university) ? null : trim($this->reviewer_university),
            'reviewer_college' => blank($this->reviewer_college) ? null : trim($this->reviewer_college),
            'reviewer_degree' => $this->reviewer_degree,
            'willing_to_help' => $this->willing_to_help,
            'contact_method' => $this->willing_to_help ? (blank($this->contact_method) ? null : trim($this->contact_method)) : null,
        ])->save();

        $this->rating->refresh()->load('company');

        $this->dispatch('toast', message: 'تم الحفظ', type: 'success');
    }

    public function delete(): void
    {
        $this->rating->delete();

        session()->flash('success', 'تم حذف التقييم');

        $this->redirectRoute('admin.ratings.index', navigate: true);
    }

    protected function afterModeration(): void
    {
        $this->rating->refresh()->load('company');
        $this->status = (string) $this->rating->status;
    }

    /**
     * @param  array<string, string>  $options
     * @return list<array{id: string, name: string}>
     */
    protected function toChoices(array $options): array
    {
        return collect($options)
            ->map(fn (string $label, string $value): array => ['id' => $value, 'name' => $label])
            ->values()
            ->all();
    }

    /** @return list<array{id: string, name: string}> */
    #[Computed]
    public function cityChoices(): array
    {
        return SaudiCity::toChoicesOptions();
    }

    /** @return list<array{id: string, name: string}> */
    #[Computed]
    public function statusChoices(): array
    {
        return $this->toChoices(ModerationStatus::options());
    }

    /** @return list<array{id: int, name: string}> */
    #[Computed]
    public function durationChoices(): array
    {
        return collect(range(1, 12))
            ->map(fn (int $month): array => ['id' => $month, 'name' => $month.' '.($month <= 2 ? ($month === 1 ? 'شهر' : 'شهران') : ($month <= 10 ? 'أشهر' : 'شهراً'))])
            ->all();
    }

    /** @return list<array{id: string, name: string}> */
    #[Computed]
    public function modalityChoices(): array
    {
        return $this->toChoices(Modality::options());
    }

    /** @return list<array{id: string, name: string}> */
    #[Computed]
    public function recommendationChoices(): array
    {
        return $this->toChoices(Recommendation::options());
    }

    /** @return list<array{id: string, name: string}> */
    #[Computed]
    public function degreeChoices(): array
    {
        return $this->toChoices(ReviewerDegree::options());
    }
}; ?>

<div class="mx-auto max-w-3xl space-y-6">
    @php
        $inputClass = 'w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none';
        $toggleClass = 'flex cursor-pointer items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-xs transition-colors hover:bg-slate-50 has-checked:border-blue-300 has-checked:bg-blue-50/60';
    @endphp

    <x-admin.page-header
        :title="$rating->role_title ?? 'تقييم بدون مسمى'"
        :subtitle="($rating->company?->name ?? 'جهة غير معروفة').' · أُرسل '.$rating->created_at->format('Y-m-d')"
    >
        <x-public.status-badge :status="$rating->status" />

        <x-admin.moderation-actions :record="$rating" type="rating" />

        <x-admin.confirm-dialog
            title="حذف التقييم"
            message="سيتم حذف هذا التقييم نهائياً"
            confirm-label="حذف نهائياً"
            wire-click="delete"
        >
            <x-slot:trigger>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    حذف
                </button>
            </x-slot:trigger>
        </x-admin.confirm-dialog>
    </x-admin.page-header>

    <form wire:submit="save" class="space-y-6">
        {{-- 1. الجهة والوظيفة --}}
        <section aria-labelledby="section-company" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6">
            <h2 id="section-company" class="flex items-center gap-2 font-bold text-slate-900">
                <svg class="size-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                الجهة والوظيفة
            </h2>

            <x-public.nice-select
                label="الجهة"
                name="company_id"
                wire:model="company_id"
                :options="$this->reassignCompanyOptions"
                placeholder="اختر الجهة..."
                offline
                searchable
                required
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-public.form-field label="المسمى الوظيفي" name="role_title">
                    <input type="text" wire:model="role_title" id="role_title" placeholder="مثلاً: مهندس برمجيات" class="{{ $inputClass }}" />
                </x-public.form-field>

                <x-public.form-field label="القسم" name="department">
                    <input type="text" wire:model="department" id="department" placeholder="مثلاً: هندسة البرمجيات" class="{{ $inputClass }}" />
                </x-public.form-field>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-public.nice-select
                    label="المدينة"
                    name="city"
                    wire:model="city"
                    :options="$this->cityChoices"
                    placeholder="اختر المدينة..."
                    offline
                    searchable
                />

                <x-public.nice-select
                    label="الحالة"
                    name="status"
                    wire:model="status"
                    :options="$this->statusChoices"
                    offline
                    required
                    :clearable="false"
                />
            </div>
        </section>

        {{-- 2. تفاصيل التدريب --}}
        <section aria-labelledby="section-details" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6">
            <h2 id="section-details" class="flex items-center gap-2 font-bold text-slate-900">
                <svg class="size-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                تفاصيل التدريب
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-public.nice-select
                    label="المدة"
                    name="duration_months"
                    wire:model="duration_months"
                    :options="$this->durationChoices"
                    placeholder="اختر المدة..."
                    offline
                />

                <x-public.nice-select
                    label="نمط الحضور"
                    name="modality"
                    wire:model="modality"
                    :options="$this->modalityChoices"
                    placeholder="اختر النمط..."
                    offline
                    required
                />

                <x-public.form-field label="المكافأة الشهرية" name="stipend_sar">
                    <div class="relative">
                        <input type="number" wire:model="stipend_sar" id="stipend_sar" min="0" inputmode="numeric" placeholder="0" class="{{ $inputClass }} pe-16" />
                        <span class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-4 text-xs text-slate-400">ر.س/شهر</span>
                    </div>
                </x-public.form-field>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <label class="{{ $toggleClass }}">
                    <input type="checkbox" wire:model="had_supervisor" class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500/30" />
                    <span>كان هناك مشرف مباشر</span>
                </label>
                <label class="{{ $toggleClass }}">
                    <input type="checkbox" wire:model="mixed_env" class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500/30" />
                    <span>بيئة العمل مختلطة</span>
                </label>
                <label class="{{ $toggleClass }}">
                    <input type="checkbox" wire:model="job_offer" class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500/30" />
                    <span>انتهى بعرض عمل</span>
                </label>
            </div>
        </section>

        {{-- 3. التقييمات --}}
        <section aria-labelledby="section-ratings" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="section-ratings" class="flex items-center gap-2 font-bold text-slate-900">
                    <svg class="size-5 text-slate-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    التقييمات
                </h2>
                <div class="flex items-center gap-2">
                    <x-admin.score-badge :score="$rating->overall_rating" size="lg" />
                    <span class="text-xs text-slate-400">يُحسب تلقائياً من التقييمات الفرعية</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-public.star-picker field="rating_learning" label="التعلّم واكتساب المهارات" :required="true" />
                <x-public.star-picker field="rating_mentorship" label="الإشراف والتوجيه" :required="true" />
                <x-public.star-picker field="rating_real_work" label="العمل الحقيقي" :required="true" />
                <x-public.star-picker field="rating_team_environment" label="بيئة الفريق" :required="true" />
                <x-public.star-picker field="rating_organization" label="التنظيم" :required="true" />
            </div>

            <div class="sm:max-w-xs">
                <x-public.nice-select
                    label="التوصية"
                    name="recommendation"
                    wire:model="recommendation"
                    :options="$this->recommendationChoices"
                    placeholder="اختر التوصية..."
                    offline
                    required
                />
            </div>
        </section>

        {{-- 4. المراجعة --}}
        <section aria-labelledby="section-review" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6">
            <h2 id="section-review" class="flex items-center gap-2 font-bold text-slate-900">
                <svg class="size-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h6m-6 8l-4-4V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2H7z"/></svg>
                المراجعة
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-public.form-field label="الإيجابيات" name="pros">
                    <input type="text" wire:model="pros" id="pros" placeholder="أبرز ما أعجب المتدرب" class="{{ $inputClass }}" />
                </x-public.form-field>

                <x-public.form-field label="السلبيات" name="cons">
                    <input type="text" wire:model="cons" id="cons" placeholder="ما يمكن تحسينه" class="{{ $inputClass }}" />
                </x-public.form-field>
            </div>

            <x-public.form-field label="نص المراجعة" name="review_text">
                <textarea wire:model="review_text" id="review_text" rows="4" placeholder="تجربة المتدرب بالتفصيل" class="{{ $inputClass }}"></textarea>
            </x-public.form-field>

            <x-public.form-field label="طريقة التقديم" name="application_method">
                <input type="text" wire:model="application_method" id="application_method" placeholder="مثلاً: عبر الموقع الرسمي" class="{{ $inputClass }}" />
            </x-public.form-field>
        </section>

        {{-- 5. المقيّم — rarely edited, so collapsed by default --}}
        <section aria-labelledby="section-reviewer" x-data="{ open: false }" class="rounded-2xl border border-slate-200 bg-white shadow-xs">
            <button
                type="button"
                @click="open = ! open"
                :aria-expanded="open"
                class="flex w-full items-center justify-between gap-2 p-5 text-start sm:p-6"
            >
                <h2 id="section-reviewer" class="flex items-center gap-2 font-bold text-slate-900">
                    <svg class="size-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    المقيّم
                    <span class="text-xs font-normal text-slate-400">اختياري</span>
                </h2>
                <svg class="size-5 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="open" x-cloak class="space-y-4 border-t border-slate-100 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-public.form-field label="الاسم" name="reviewer_name">
                        <input type="text" wire:model="reviewer_name" id="reviewer_name" class="{{ $inputClass }}" />
                    </x-public.form-field>

                    <x-public.form-field label="التخصص" name="reviewer_major">
                        <input type="text" wire:model="reviewer_major" id="reviewer_major" class="{{ $inputClass }}" />
                    </x-public.form-field>

                    <x-public.form-field label="الجامعة" name="reviewer_university">
                        <input type="text" wire:model="reviewer_university" id="reviewer_university" class="{{ $inputClass }}" />
                    </x-public.form-field>

                    <x-public.form-field label="الكلية" name="reviewer_college">
                        <input type="text" wire:model="reviewer_college" id="reviewer_college" class="{{ $inputClass }}" />
                    </x-public.form-field>
                </div>

                <div class="sm:max-w-xs">
                    <x-public.nice-select
                        label="الدرجة العلمية"
                        name="reviewer_degree"
                        wire:model="reviewer_degree"
                        :options="$this->degreeChoices"
                        placeholder="اختر الدرجة..."
                        offline
                    />
                </div>

                <label class="{{ $toggleClass }} sm:max-w-xs">
                    <input type="checkbox" wire:model.live="willing_to_help" class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500/30" />
                    <span>مستعد لمساعدة المتقدمين</span>
                </label>

                @if($willing_to_help)
                    <x-public.form-field label="وسيلة التواصل" name="contact_method">
                        <input type="text" wire:model="contact_method" id="contact_method" placeholder="مثلاً: بريد إلكتروني أو حساب لينكدإن" class="{{ $inputClass }}" />
                    </x-public.form-field>
                @endif
            </div>
        </section>

        {{-- Sticky save bar: clears the mobile tab bar, hugs the viewport on desktop --}}
        <div class="sticky bottom-20 z-30 flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur-sm md:bottom-4">
            <a
                href="{{ route('admin.ratings.index') }}"
                wire:navigate
                class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100"
            >
                إلغاء
            </a>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-700 active:scale-[0.98] disabled:opacity-60"
            >
                <svg wire:loading wire:target="save" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                حفظ التعديلات
            </button>
        </div>
    </form>
</div>
