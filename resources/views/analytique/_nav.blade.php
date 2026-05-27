@php $active = $active ?? 'balance'; @endphp
<div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
    <div>
        <h4 class="mb-1">{{ $title ?? 'Analytique' }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('accounting.modules.show', ['module' => 'analytique']) }}">Analytique</a></li>
                <li class="breadcrumb-item active">{{ $title ?? '' }}</li>
            </ol>
        </nav>
    </div>
</div>
<ul class="nav nav-tabs nav-tabs-bottom mb-3 flex-wrap">
    <li class="nav-item"><a class="nav-link {{ $active === 'dashboard' ? 'active' : '' }}" href="{{ route('accounting.analytique.dashboard') }}"><i class="ti ti-chart-pie me-1"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link {{ $active === 'axes' ? 'active' : '' }}" href="{{ route('accounting.analytique.axes') }}"><i class="ti ti-sitemap me-1"></i>Axes & comptes</a></li>
    <li class="nav-item"><a class="nav-link {{ $active === 'balance' ? 'active' : '' }}" href="{{ route('accounting.analytique.balance') }}"><i class="ti ti-scale me-1"></i>Balance</a></li>
    <li class="nav-item"><a class="nav-link {{ $active === 'grand-livre' ? 'active' : '' }}" href="{{ route('accounting.analytique.grand-livre') }}"><i class="ti ti-book-2 me-1"></i>Grand livre</a></li>
    <li class="nav-item"><a class="nav-link {{ $active === 'rentabilite' ? 'active' : '' }}" href="{{ route('accounting.analytique.rentabilite') }}"><i class="ti ti-trending-up me-1"></i>Rentabilité</a></li>
    <li class="nav-item"><a class="nav-link {{ $active === 'centres-cout' ? 'active' : '' }}" href="{{ route('accounting.analytique.centres-cout') }}"><i class="ti ti-building me-1"></i>Centres de coût</a></li>
</ul>
