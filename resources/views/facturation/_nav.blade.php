@php
    $items = [
        ['key' => 'clients', 'route' => 'accounting.facturation.clients', 'label' => 'Factures clients', 'icon' => 'ti-receipt'],
        ['key' => 'fournisseurs', 'route' => 'accounting.facturation.fournisseurs', 'label' => 'Factures fournisseurs', 'icon' => 'ti-truck-delivery'],
        ['key' => 'avoirs-clients', 'route' => 'accounting.facturation.avoirs-clients', 'label' => 'Avoirs clients', 'icon' => 'ti-receipt-refund'],
        ['key' => 'paiements', 'route' => 'accounting.facturation.paiements', 'label' => 'Paiements', 'icon' => 'ti-cash'],
        ['key' => 'demandes', 'route' => 'accounting.facturation.demandes', 'label' => 'Demandes de fonds', 'icon' => 'ti-git-pull-request'],
        ['key' => 'echeancier-clients', 'route' => 'accounting.facturation.echeancier-clients', 'label' => 'Ã‰chÃ©ancier clients', 'icon' => 'ti-calendar-due'],
        ['key' => 'produits', 'route' => 'accounting.facturation.produits', 'label' => 'Produits', 'icon' => 'ti-package'],
    ];
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
</div>
<ul class="nav nav-tabs nav-tabs-solid nav-tabs-rounded mb-3 flex-wrap">
    @foreach($items as $item)
    <li class="nav-item">
        <a class="nav-link {{ ($active ?? '') === $item['key'] ? 'active' : '' }}" href="{{ route($item['route']) }}">
            <i class="ti {{ $item['icon'] }} me-1"></i>{{ $item['label'] }}
        </a>
    </li>
    @endforeach
</ul>

