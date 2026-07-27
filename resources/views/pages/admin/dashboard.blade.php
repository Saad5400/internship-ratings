<?php

use App\Models\Company;
use App\Models\Rating;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin')] #[Title('المراجعة')] class extends Component {
    #[Computed]
    public function pendingRatingsCount(): int
    {
        return Rating::pending()->count();
    }

    #[Computed]
    public function pendingCompaniesCount(): int
    {
        return Company::pending()->count();
    }
}; ?>

<div class="space-y-8">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">المراجعة</h1>
        <p class="mt-2 text-slate-500">
            @if($this->pendingRatingsCount + $this->pendingCompaniesCount > 0)
                لديك {{ $this->pendingRatingsCount }} تقييم و{{ $this->pendingCompaniesCount }} جهة بانتظار المراجعة
            @else
                لا يوجد شيء بانتظار المراجعة — كل شيء تمت مراجعته 🎉
            @endif
        </p>
    </div>
</div>
