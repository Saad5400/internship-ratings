<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/*
 * Invited admins land here from a temporary signed link (7 days) handed over
 * manually — there is no mailer. The `signed` middleware on the route rejects
 * tampered/expired links with a 403. A valid link always allows setting a new
 * password, so the same link doubles as a password-reset flow.
 */
new #[Layout('layouts.public')] #[Title('تعيين كلمة المرور')] class extends Component {
    public User $user;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function save(): void
    {
        $this->validate(
            rules: [
                'password' => ['required', 'confirmed', Password::defaults()],
            ],
            messages: [
                'password.required' => 'يرجى إدخال كلمة المرور.',
                'password.confirmed' => 'كلمتا المرور غير متطابقتين.',
            ],
        );

        // The 'hashed' cast hashes on assignment.
        $this->user->password = $this->password;

        if ($this->user->email_verified_at === null) {
            $this->user->email_verified_at = now();
        }

        $this->user->save();

        Auth::login($this->user);
        session()->regenerate();
        session()->flash('success', 'تم تعيين كلمة المرور بنجاح');

        $this->redirect($this->user->is_admin ? route('admin.dashboard') : route('home'));
    }
}; ?>

<div class="mx-auto max-w-md py-6 sm:py-10">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs sm:p-8">
        <div class="mb-6 text-center">
            <span class="mx-auto flex size-12 items-center justify-center rounded-xl bg-blue-50 text-blue-500">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </span>
            <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-900">تعيين كلمة المرور</h1>
            <p class="mt-1 text-sm text-slate-500">
                مرحباً {{ $user->name }}
                <span dir="ltr" class="mt-0.5 block text-xs text-slate-400">{{ $user->email }}</span>
            </p>
        </div>

        <form wire:submit="save" class="space-y-5">
            <x-public.form-field name="password" label="كلمة المرور الجديدة" required>
                <x-password-input id="password" wire:model="password" required autofocus autocomplete="new-password" />
            </x-public.form-field>

            <x-public.form-field name="password_confirmation" label="تأكيد كلمة المرور" required>
                <x-password-input id="password_confirmation" wire:model="password_confirmation" required autocomplete="new-password" />
            </x-public.form-field>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-500 px-4 py-3 text-sm font-semibold text-white shadow-xs transition-all hover:bg-blue-600 hover:shadow-sm active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70"
            >
                <svg wire:loading wire:target="save" class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                تعيين كلمة المرور
            </button>
        </form>

        <p class="mt-5 text-center text-xs leading-relaxed text-slate-400">
            إن انتهت صلاحية الرابط فاطلب رابطاً جديداً من أحد المشرفين
        </p>
    </div>
</div>
