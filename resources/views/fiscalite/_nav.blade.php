@php
    $navItems = [
        'tva-collectee' => ['route' => 'accounting.fiscalite.tva-collectee', 'label' => 'TVA collectée', 'icon' => 'ti-percentage'],
        'tva-deductible' => ['route' => 'accounting.fiscalite.tva-deductible', 'label' => 'TVA déductible', 'icon' => 'ti-receipt-refund'],
        'dsf' => ['route' => 'accounting.fiscalite.dsf', 'label' => 'DSF', 'icon' => 'ti-file-invoice'],
        'is' => ['route' => 'accounting.fiscalite.is', 'label' => 'Impôt sociétés', 'icon' => 'ti-building'],
        'declarations' => ['route' => 'accounting.fiscalite.declarations', 'label' => 'Génération', 'icon' => 'ti-wand'],
        'echeances' => ['route' => 'accounting.fiscalite.echeances', 'label' => 'Échéances', 'icon' => 'ti-calendar-due'],
    ];
    $active = $active ?? $page ?? '';
@endphp
<div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
    <div>
        <h4 class="mb-1">{{ $title ?? 'Fiscalité' }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('accounting.modules.show', ['module' => 'fiscalite']) }}">Fiscalité</a></li>
                <li class="breadcrumb-item active">{{ $breadcrumb ?? $title ?? '' }}</li>
            </ol>
        </nav>
    </div>
</div>
<ul class="nav nav-tabs nav-tabs-solid nav-tabs-rounded mb-4 flex-wrap">
    @foreach ($navItems as $key => $item)
        <li class="nav-item">
            <a class="nav-link {{ $active === $key ? 'active' : '' }}" href="{{ route($item['route']) }}">
                <i class="ti {{ $item['icon'] }} me-1"></i>{{ $item['label'] }}
            </a>
        </li>
    @endforeach
</ul>
<div v-if="error" class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0" v-if="Array.isArray(error)"><li v-for="(e,i) in error" :key="i">@{{ e }}</li></ul>
    <span v-else>@{{ error }}</span>
    <button type="button" class="btn-close" @click="error=null"></button>
</div>
