@php
    $navItems = [
        'journal' => ['route' => 'accounting.livres.journal', 'label' => 'Journal général', 'icon' => 'ti-notebook'],
        'grand-livre' => ['route' => 'accounting.livres.grand-livre', 'label' => 'Grand livre', 'icon' => 'ti-book-2'],
        'balance' => ['route' => 'accounting.livres.balance', 'label' => 'Balance générale', 'icon' => 'ti-scale'],
        'auxiliaire' => ['route' => 'accounting.livres.auxiliaire', 'label' => 'Balance auxiliaire', 'icon' => 'ti-scale-outline'],
        'lettrage' => ['route' => 'accounting.livres.lettrage', 'label' => 'Lettrage', 'icon' => 'ti-checkup-list'],
        'banque' => ['route' => 'accounting.livres.banque', 'label' => 'Livre de banque', 'icon' => 'ti-building-bank'],
        'caisse' => ['route' => 'accounting.livres.caisse', 'label' => 'Livre de caisse', 'icon' => 'ti-cash'],
        'comptes-tiers' => ['route' => 'accounting.livres.comptes-tiers', 'label' => 'Comptes de tiers', 'icon' => 'ti-users-group'],
    ];
    $active = $active ?? '';
@endphp

<div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
    <div>
        <h4 class="mb-1">{{ $title ?? 'Livres comptables' }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('accounting.modules.show', ['module' => 'livres']) }}">Livres</a></li>
                <li class="breadcrumb-item active">{{ $breadcrumb ?? '' }}</li>
            </ol>
        </nav>
    </div>
    <div class="gap-2 d-flex align-items-center flex-wrap">
        @include('components.export-buttons')

        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow"
           @click="loadData" :disabled="isLoading" data-bs-toggle="tooltip" title="Actualiser">
            <i class="ti ti-refresh" :class="{'ti-spin': isLoading}"></i>
        </a>
        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" id="collapse-header">
            <i class="ti ti-transition-top"></i>
        </a>
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
