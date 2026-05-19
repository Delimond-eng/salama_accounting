@php
    $navItems = [
        'nouvelle' => ['route' => 'accounting.saisie.nouvelle', 'label' => 'Nouvelle écriture', 'icon' => 'ti-file-plus'],
        'achats' => ['route' => 'accounting.saisie.achats', 'label' => 'Achats', 'icon' => 'ti-shopping-cart'],
        'ventes' => ['route' => 'accounting.saisie.ventes', 'label' => 'Ventes', 'icon' => 'ti-receipt'],
        'banque' => ['route' => 'accounting.saisie.banque', 'label' => 'Banque', 'icon' => 'ti-building-bank'],
        'caisse' => ['route' => 'accounting.saisie.caisse', 'label' => 'Caisse', 'icon' => 'ti-cash'],
        'od' => ['route' => 'accounting.saisie.od', 'label' => 'OD', 'icon' => 'ti-adjustments'],
        'devises' => ['route' => 'accounting.saisie.devises', 'label' => 'Devises', 'icon' => 'ti-currency-dollar'],
        'import' => ['route' => 'accounting.saisie.import-releve', 'label' => 'Import relevé', 'icon' => 'ti-file-upload'],
    ];
    $active = $active ?? '';
@endphp

<div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
    <div>
        <h4 class="mb-1">{{ $title ?? 'Saisie comptable' }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('accounting.modules.show', ['module' => 'saisie']) }}">Saisie</a></li>
                <li class="breadcrumb-item active">{{ $breadcrumb ?? '' }}</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap" v-if="exercice">
        <span class="badge badge-soft-info">@{{ exercice.libelle }}</span>
        <span v-if="journal" class="badge badge-soft-primary">@{{ journal.code }} — @{{ journal.libelle }}</span>
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
<div v-if="message" class="alert alert-success alert-dismissible fade show">
    @{{ message }}<button type="button" class="btn-close" @click="message=null"></button>
</div>
