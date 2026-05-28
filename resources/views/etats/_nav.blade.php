@php
    $navItems = [
        'bilan' => ['route' => 'accounting.etats.bilan', 'label' => 'Bilan', 'icon' => 'ti-columns', 'ref' => 'BI'],
        'compte-resultat' => ['route' => 'accounting.etats.compte-resultat', 'label' => 'Compte de résultat', 'icon' => 'ti-chart-bar', 'ref' => 'CR'],
        'flux-tresorerie' => ['route' => 'accounting.etats.flux-tresorerie', 'label' => 'Flux trésorerie', 'icon' => 'ti-arrows-shuffle', 'ref' => 'TFT'],
        'variation-kp' => ['route' => 'accounting.etats.variation-kp', 'label' => 'Variation KP', 'icon' => 'ti-trending-up', 'ref' => 'TVCP'],
        'annexes' => ['route' => 'accounting.etats.annexes', 'label' => 'Annexes', 'icon' => 'ti-file-description'],
        'comparatif' => ['route' => 'accounting.etats.comparatif', 'label' => 'Comparatif N/N-1', 'icon' => 'ti-git-compare'],
        'exports' => ['route' => 'accounting.etats.exports', 'label' => 'Exports', 'icon' => 'ti-file-export'],
    ];
    $active = $active ?? $page ?? '';
@endphp

<div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
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
    <div class="gap-2 d-flex align-items-center flex-wrap">
        <div class="d-flex align-items-center gap-2 me-2" v-if="exercice">
            <span class="badge bg-soft-info text-info">@{{ exercice.libelle }}</span>
        </div>

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
                <i class="ti {{ $item['icon'] }} me-1"></i>
                <span>{{ $item['label'] }}</span>
                @if(isset($item['ref']))
                    <span class="badge bg-info-subtle text-info ms-1 fs-10 px-1">#{{ $item['ref'] }}</span>
                @endif
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
    <i class="ti ti-circle-check me-2"></i>@{{ message }}<button type="button" class="btn-close" @click="message=null"></button>
</div>

<style>
    .bg-label-info { background-color: #d7f5fc !important; color: #03c3ec !important; }
    .fs-10 { font-size: 10px !important; }
</style>
