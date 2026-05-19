@php
    $navItems = [
        'bilan' => ['route' => 'accounting.etats.bilan', 'label' => 'Bilan', 'icon' => 'ti-columns'],
        'compte-resultat' => ['route' => 'accounting.etats.compte-resultat', 'label' => 'Compte de résultat', 'icon' => 'ti-chart-bar'],
        'flux-tresorerie' => ['route' => 'accounting.etats.flux-tresorerie', 'label' => 'Flux trésorerie', 'icon' => 'ti-arrows-shuffle'],
        'variation-kp' => ['route' => 'accounting.etats.variation-kp', 'label' => 'Variation KP', 'icon' => 'ti-trending-up'],
        'annexes' => ['route' => 'accounting.etats.annexes', 'label' => 'Annexes', 'icon' => 'ti-file-description'],
        'comparatif' => ['route' => 'accounting.etats.comparatif', 'label' => 'Comparatif N/N-1', 'icon' => 'ti-git-compare'],
        'exports' => ['route' => 'accounting.etats.exports', 'label' => 'Exports', 'icon' => 'ti-file-export'],
    ];
    $active = $active ?? $page ?? '';
@endphp
<div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
    <div>
        <h4 class="mb-1">{{ $title ?? 'États financiers' }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('accounting.modules.show', ['module' => 'etats']) }}">États</a></li>
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
