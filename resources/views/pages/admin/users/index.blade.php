<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url as UrlParam;
use Livewire\Component;
use Livewire\WithPagination;

/*
 * Users management. There is no mailer, so inviting an admin produces a
 * temporary signed setup link (7 days) that is handed over manually — the
 * invitee sets their own password on /admin/setup/{user}. The same link
 * doubles as a password-reset flow via the per-row "رابط تعيين كلمة المرور".
 */
new #[Layout('layouts.admin')] #[Title('المستخدمون')] class extends Component {
    use WithPagination;

    #[UrlParam(as: 'q')]
    public string $search = '';

    /** '' (all) | 'admins' | 'members' */
    #[UrlParam(as: 'role')]
    public string $adminFilter = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    // --- Invite dialog state ---

    public string $inviteName = '';

    public string $inviteEmail = '';

    public bool $inviteIsAdmin = true;

    public string $invitePassword = '';

    /** The generated signed setup link shown in the result dialog. */
    public string $inviteLink = '';

    /** Name of the user the current setup link belongs to. */
    public string $inviteLinkUserName = '';

    // --- Edit dialog state ---

    public ?int $editingUserId = null;

    public string $editName = '';

    public string $editEmail = '';

    public bool $editIsAdmin = false;

    public string $editPassword = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAdminFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['created_at'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->adminFilter = '';
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->adminFilter === 'admins', fn ($query) => $query->where('is_admin', true))
            ->when($this->adminFilter === 'members', fn ($query) => $query->where('is_admin', false))
            ->orderBy($this->sortField, $this->sortDirection)
            ->orderBy('id', $this->sortDirection)
            ->paginate(15);
    }

    #[Computed]
    public function adminCount(): int
    {
        return User::query()->where('is_admin', true)->count();
    }

    public function invite(): void
    {
        $this->validate(
            rules: [
                'inviteName' => ['required', 'string', 'max:255'],
                'inviteEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'inviteIsAdmin' => ['boolean'],
                'invitePassword' => ['nullable', 'string', Password::defaults()],
            ],
            messages: [
                'inviteName.required' => 'يرجى إدخال الاسم.',
                'inviteEmail.required' => 'يرجى إدخال البريد الإلكتروني.',
                'inviteEmail.email' => 'يرجى إدخال بريد إلكتروني صحيح.',
                'inviteEmail.unique' => 'هذا البريد الإلكتروني مسجّل مسبقاً.',
            ],
        );

        $hasManualPassword = $this->invitePassword !== '';

        /*
         * With no manual password we still store a real (random) hash so the
         * account can never be entered with an empty password; the invitee
         * replaces it via the signed setup link.
         */
        $user = User::create([
            'name' => $this->inviteName,
            'email' => $this->inviteEmail,
            'is_admin' => $this->inviteIsAdmin,
            'password' => $hasManualPassword ? $this->invitePassword : Str::password(40),
        ]);

        $this->reset('inviteName', 'inviteEmail', 'invitePassword');
        $this->inviteIsAdmin = true;

        unset($this->users, $this->adminCount);

        $this->dispatch('close-dialog', id: 'invite-user');

        if ($hasManualPassword) {
            $this->dispatch('toast', message: 'تمت إضافة المستخدم', type: 'success');

            return;
        }

        $this->openSetupLinkDialog($user);
    }

    public function generateSetupLink(int $userId): void
    {
        $this->openSetupLinkDialog(User::query()->findOrFail($userId));
    }

    public function startEdit(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->editIsAdmin = $user->is_admin;
        $this->editPassword = '';

        $this->resetValidation();
        $this->dispatch('open-dialog', id: 'edit-user');
    }

    public function saveEdit(): void
    {
        $user = User::query()->findOrFail($this->editingUserId);

        $this->validate(
            rules: [
                'editName' => ['required', 'string', 'max:255'],
                'editEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'editIsAdmin' => ['boolean'],
                'editPassword' => ['nullable', 'string', Password::defaults()],
            ],
            messages: [
                'editName.required' => 'يرجى إدخال الاسم.',
                'editEmail.required' => 'يرجى إدخال البريد الإلكتروني.',
                'editEmail.email' => 'يرجى إدخال بريد إلكتروني صحيح.',
                'editEmail.unique' => 'هذا البريد الإلكتروني مسجّل مسبقاً.',
            ],
        );

        if ($user->is_admin && ! $this->editIsAdmin && $this->adminCount <= 1) {
            throw ValidationException::withMessages([
                'editIsAdmin' => 'لا يمكن إزالة آخر مدير',
            ]);
        }

        $user->name = $this->editName;
        $user->email = $this->editEmail;
        $user->is_admin = $this->editIsAdmin;

        // A blank password keeps the existing hash — only assign when filled.
        if ($this->editPassword !== '') {
            $user->password = $this->editPassword;
        }

        $user->save();

        $this->reset('editingUserId', 'editName', 'editEmail', 'editPassword');
        unset($this->users, $this->adminCount);

        $this->dispatch('close-dialog', id: 'edit-user');
        $this->dispatch('toast', message: 'تم حفظ التعديلات', type: 'success');
    }

    public function deleteUser(int $userId): void
    {
        // Server-side guards mirror the disabled-with-reason UI — never UI alone.
        if ($userId === auth()->id()) {
            $this->dispatch('toast', message: 'لا يمكنك حذف حسابك', type: 'danger');

            return;
        }

        $user = User::query()->findOrFail($userId);

        if ($user->is_admin && $this->adminCount <= 1) {
            $this->dispatch('toast', message: 'لا يمكن حذف آخر مدير', type: 'danger');

            return;
        }

        $user->delete();

        unset($this->users, $this->adminCount);

        $this->dispatch('toast', message: 'تم حذف المستخدم', type: 'success');
    }

    protected function openSetupLinkDialog(User $user): void
    {
        $this->inviteLink = URL::temporarySignedRoute('admin.setup', now()->addDays(7), ['user' => $user->id]);
        $this->inviteLinkUserName = $user->name;

        $this->dispatch('open-dialog', id: 'invite-link');
    }
}; ?>

<div class="space-y-6">
    <x-admin.page-header title="المستخدمون" subtitle="إدارة حسابات المشرفين وروابط الدعوة">
        <x-admin.dialog id="invite-user" title="إضافة مشرف">
            <x-slot:trigger>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-600 hover:shadow-sm active:scale-[0.99]"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    إضافة مشرف
                </button>
            </x-slot:trigger>

            <form wire:submit="invite" class="space-y-4">
                <x-public.form-field name="inviteName" label="الاسم" required>
                    <input
                        type="text"
                        id="inviteName"
                        wire:model="inviteName"
                        required
                        autocomplete="off"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                    />
                </x-public.form-field>

                <x-public.form-field name="inviteEmail" label="البريد الإلكتروني" required>
                    <input
                        type="email"
                        id="inviteEmail"
                        wire:model="inviteEmail"
                        dir="ltr"
                        required
                        autocomplete="off"
                        inputmode="email"
                        placeholder="new-admin@example.com"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                    />
                </x-public.form-field>

                <label class="flex cursor-pointer select-none items-start gap-2.5 text-sm text-slate-700">
                    <input type="checkbox" wire:model="inviteIsAdmin" class="mt-0.5 size-4 rounded border-slate-300 text-blue-500 focus:ring-blue-500/30" />
                    <span>
                        صلاحيات مدير
                        <span class="block text-xs text-slate-400">يستطيع الدخول إلى لوحة الإدارة والمراجعة</span>
                    </span>
                </label>

                <div x-data="{ advanced: false }" class="rounded-xl border border-slate-100 bg-slate-50/60">
                    <button
                        type="button"
                        @click="advanced = !advanced"
                        class="flex w-full items-center justify-between gap-2 px-4 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:text-slate-900"
                        :aria-expanded="advanced"
                    >
                        خيارات متقدمة
                        <svg class="size-4 transition-transform" :class="advanced ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="advanced" x-cloak class="px-4 pb-4">
                        <x-public.form-field name="invitePassword" label="كلمة مرور يدوية">
                            <div x-data="{ show: false }" class="relative">
                                <input
                                    :type="show ? 'text' : 'password'"
                                    id="invitePassword"
                                    wire:model="invitePassword"
                                    dir="ltr"
                                    autocomplete="new-password"
                                    class="w-full rounded-xl border border-slate-200 bg-white ps-4 pe-11 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                                />
                                <button
                                    type="button"
                                    @click="show = !show"
                                    class="absolute inset-y-0 end-0 flex items-center pe-4 text-slate-400 transition-colors hover:text-slate-600"
                                    :aria-label="show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
                                >
                                    <svg x-show="!show" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="show" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-slate-400">اتركها فارغة لإنشاء رابط دعوة</p>
                        </x-public.form-field>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button type="button" @click="open = false" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100">
                        إلغاء
                    </button>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="invite"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-500 px-4 py-2 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-600 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <svg wire:loading wire:target="invite" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        إضافة
                    </button>
                </div>
            </form>
        </x-admin.dialog>
    </x-admin.page-header>

    {{-- Search + admins filter --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4 text-slate-400">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="ابحث بالاسم أو البريد الإلكتروني..."
                aria-label="البحث في المستخدمين"
                class="w-full rounded-xl border border-slate-200 bg-white ps-11 pe-11 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
            />
            <span wire:loading wire:target="search" class="absolute inset-y-0 end-0 flex items-center pe-4 text-slate-400">
                <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
            </span>
        </div>

        <select
            wire:model.live="adminFilter"
            aria-label="تصفية حسب الصلاحية"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
        >
            <option value="">الكل</option>
            <option value="admins">مدراء</option>
            <option value="members">غير مدراء</option>
        </select>
    </div>

    @if($this->users->isEmpty())
        <x-admin.empty-state
            icon="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
            :message="$search !== '' || $adminFilter !== '' ? 'لا يوجد مستخدمون يطابقون بحثك' : 'لا يوجد مستخدمون بعد'"
        >
            @if($search !== '' || $adminFilter !== '')
                <button type="button" wire:click="clearFilters" class="text-sm font-medium text-blue-500 transition-colors hover:text-blue-600">
                    مسح البحث
                </button>
            @endif
        </x-admin.empty-state>
    @else
        <x-admin.table>
            <x-slot:head>
                <x-admin.th>الاسم</x-admin.th>
                <x-admin.th>البريد الإلكتروني</x-admin.th>
                <x-admin.th>الصلاحية</x-admin.th>
                <x-admin.th field="created_at" :sort="$sortField" :direction="$sortDirection">تاريخ الإضافة</x-admin.th>
                <x-admin.th align="end">إجراءات</x-admin.th>
            </x-slot:head>

            @foreach($this->users as $user)
                @php
                    $isSelf = $user->id === auth()->id();
                    $isLastAdmin = $user->is_admin && $this->adminCount <= 1;
                    $deleteBlockedReason = $isSelf ? 'لا يمكنك حذف حسابك' : ($isLastAdmin ? 'لا يمكن حذف آخر مدير' : null);
                    $deleteMessage = 'سيتم حذف حساب "'.$user->name.'" نهائياً. لا يمكن التراجع عن هذا الإجراء.';
                @endphp
                <tr wire:key="user-{{ $user->id }}" class="transition-colors hover:bg-slate-50/60">
                    <td class="whitespace-nowrap px-4 py-3">
                        <span class="font-semibold text-slate-900">{{ $user->name }}</span>
                        @if($isSelf)
                            <span class="ms-1 text-xs text-slate-400">(أنت)</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <span class="inline-flex items-center gap-1.5">
                            <span dir="ltr" class="text-slate-600">{{ $user->email }}</span>
                            <button
                                type="button"
                                x-data="{ copied: false }"
                                @click="navigator.clipboard.writeText({{ Js::from($user->email) }}); copied = true; setTimeout(() => copied = false, 1500)"
                                aria-label="نسخ البريد الإلكتروني"
                                class="rounded p-1 text-slate-300 transition-colors hover:bg-slate-100 hover:text-slate-500"
                            >
                                <svg x-show="!copied" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <svg x-show="copied" x-cloak class="size-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        @if($user->is_admin)
                            <x-badge color="success">مدير</x-badge>
                        @else
                            <x-badge>عضو</x-badge>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-slate-500">
                        {{ $user->created_at?->format('Y/m/d') }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <button
                                type="button"
                                wire:click="startEdit({{ $user->id }})"
                                class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900"
                            >
                                تعديل
                            </button>
                            <button
                                type="button"
                                wire:click="generateSetupLink({{ $user->id }})"
                                wire:loading.attr="disabled"
                                class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-blue-500 transition-colors hover:bg-blue-50 hover:text-blue-600"
                            >
                                رابط تعيين كلمة المرور
                            </button>

                            @if($deleteBlockedReason !== null)
                                {{-- Disabled-with-reason: the reason is visible, not a mystery --}}
                                <span class="inline-flex flex-col items-end">
                                    <button
                                        type="button"
                                        disabled
                                        title="{{ $deleteBlockedReason }}"
                                        class="cursor-not-allowed rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-300"
                                    >
                                        حذف
                                    </button>
                                    <span class="pe-2.5 text-[10px] leading-tight text-slate-400">{{ $deleteBlockedReason }}</span>
                                </span>
                            @else
                                <x-admin.confirm-dialog
                                    title="حذف المستخدم"
                                    :message="$deleteMessage"
                                    confirm-label="حذف"
                                    wire-click="deleteUser({{ $user->id }})"
                                >
                                    <x-slot:trigger>
                                        <button
                                            type="button"
                                            class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50 hover:text-red-700"
                                        >
                                            حذف
                                        </button>
                                    </x-slot:trigger>
                                </x-admin.confirm-dialog>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-admin.table>

        <x-admin.pagination :paginator="$this->users" />
    @endif

    {{-- Result dialog: the signed setup link, handed over manually (no mailer) --}}
    <x-admin.dialog id="invite-link" title="رابط تعيين كلمة المرور">
        <x-slot:trigger></x-slot:trigger>

        <div class="space-y-4">
            <p class="flex items-center gap-2 text-sm text-green-700">
                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                تم إنشاء رابط تعيين كلمة المرور لـ{{ $inviteLinkUserName }}
            </p>

            <div class="flex items-center gap-2">
                <input
                    type="text"
                    readonly
                    dir="ltr"
                    value="{{ $inviteLink }}"
                    aria-label="رابط تعيين كلمة المرور"
                    @click="$event.target.select()"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs text-slate-600 focus:outline-none"
                />
                <button
                    type="button"
                    x-data="{ copied: false }"
                    @click="navigator.clipboard.writeText($wire.inviteLink); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-medium text-slate-600 shadow-xs transition-colors hover:bg-slate-50"
                >
                    <svg x-show="!copied" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <svg x-show="copied" x-cloak class="size-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span x-show="!copied">نسخ</span>
                    <span x-show="copied" x-cloak class="text-green-600">تم النسخ</span>
                </button>
            </div>

            <p class="text-xs leading-relaxed text-slate-500">
                الرابط صالح لمدة 7 أيام — أرسله للمشرف الجديد ليعيّن كلمة مروره
            </p>
        </div>
    </x-admin.dialog>

    {{-- Edit dialog (shared, prefilled via startEdit) --}}
    <x-admin.dialog id="edit-user" title="تعديل المستخدم">
        <x-slot:trigger></x-slot:trigger>

        <form wire:submit="saveEdit" class="space-y-4">
            <x-public.form-field name="editName" label="الاسم" required>
                <input
                    type="text"
                    id="editName"
                    wire:model="editName"
                    required
                    autocomplete="off"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                />
            </x-public.form-field>

            <x-public.form-field name="editEmail" label="البريد الإلكتروني" required>
                <input
                    type="email"
                    id="editEmail"
                    wire:model="editEmail"
                    dir="ltr"
                    required
                    autocomplete="off"
                    inputmode="email"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                />
            </x-public.form-field>

            <x-public.form-field name="editIsAdmin">
                <label class="flex cursor-pointer select-none items-start gap-2.5 text-sm text-slate-700">
                    <input type="checkbox" wire:model="editIsAdmin" class="mt-0.5 size-4 rounded border-slate-300 text-blue-500 focus:ring-blue-500/30" />
                    <span>
                        صلاحيات مدير
                        <span class="block text-xs text-slate-400">يستطيع الدخول إلى لوحة الإدارة والمراجعة</span>
                    </span>
                </label>
            </x-public.form-field>

            <x-public.form-field name="editPassword" label="كلمة مرور جديدة">
                <div x-data="{ show: false }" class="relative">
                    <input
                        :type="show ? 'text' : 'password'"
                        id="editPassword"
                        wire:model="editPassword"
                        dir="ltr"
                        autocomplete="new-password"
                        class="w-full rounded-xl border border-slate-200 bg-white ps-4 pe-11 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-xs transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
                    />
                    <button
                        type="button"
                        @click="show = !show"
                        class="absolute inset-y-0 end-0 flex items-center pe-4 text-slate-400 transition-colors hover:text-slate-600"
                        :aria-label="show ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
                    >
                        <svg x-show="!show" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
                <p class="mt-1 text-xs text-slate-400">اتركها فارغة للإبقاء على كلمة المرور الحالية</p>
            </x-public.form-field>

            <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" @click="open = false" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100">
                    إلغاء
                </button>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="saveEdit"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-500 px-4 py-2 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-600 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70"
                >
                    <svg wire:loading wire:target="saveEdit" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    حفظ
                </button>
            </div>
        </form>
    </x-admin.dialog>
</div>
