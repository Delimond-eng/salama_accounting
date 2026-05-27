@php
    $items = [
        ['key' => 'clients', 'route' => 'accounting.facturation.clients', 'label' => 'Factures clients', 'icon' => 'ti-receipt'],
        ['key' => 'fournisseurs', 'route' => 'accounting.facturation.fournisseurs', 'label' => 'Factures fournisseurs', 'icon' => 'ti-truck-delivery'],
        ['key' => 'avoirs-clients', 'route' => 'accounting.facturation.avoirs-clients', 'label' => 'Avoirs clients', 'icon' => 'ti-receipt-refund'],
        ['key' => 'paiements', 'route' => 'accounting.facturation.paiements', 'label' => 'Paiements', 'icon' => 'ti-cash'],
        ['key' => 'demandes', 'route' => 'accounting.facturation.demandes', 'label' => 'Demandes de fonds', 'icon' => 'ti-git-pull-request'],
        ['key' => 'echeancier-clients', 'route' => 'accounting.facturation.echeancier-clients', 'label' => 'Echéancier clients', 'icon' => 'ti-calendar-due'],
        ['key' => 'produits', 'route' => 'accounting.facturation.produits', 'label' => 'Produits', 'icon' => 'ti-package'],
    ];
    $active = $active ?? $page ?? '';
@endphp

<div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
    <div>
        <h4 class="mb-1">{{ $title ?? 'Facturation' }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('accounting.modules.show', ['module' => 'facturation']) }}">Facturation</a></li>
                <li class="breadcrumb-item active">{{ $breadcrumb ?? $title ?? '' }}</li>
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
    @foreach($items as $item)
    <li class="nav-item">
        <a class="nav-link {{ $active === $item['key'] ? 'active' : '' }}" href="{{ route($item['route']) }}">
            <i class="ti {{ $item['icon'] }} me-1"></i>{{ $item['label'] }}
        </a>
    </li>
    @endforeach
</ul>

<div v-if="error" class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
    <ul class="mb-0" v-if="Array.isArray(error)"><li v-for="(e,i) in error" :key="i">@{{ e }}</li></ul>
    <span v-else>@{{ error }}</span>
    <button type="button" class="btn-close" @click="error=null"></button>
</div>

<div v-if="message" class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
    <i class="ti ti-circle-check me-2"></i>@{{ message }}
    <button type="button" class="btn-close" @click="message=null"></button>
</div>
