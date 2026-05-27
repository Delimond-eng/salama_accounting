@php
    $navItems = [
        'tva-collectee' => ['route' => 'accounting.fiscalite.tva-collectee', 'label' => 'TVA collectée', 'icon' => 'ti-percentage', 'ref' => 'TVA-C'],
        'tva-deductible' => ['route' => 'accounting.fiscalite.tva-deductible', 'label' => 'TVA déductible', 'icon' => 'ti-receipt-refund', 'ref' => 'TVA-D'],
        'dsf' => ['route' => 'accounting.fiscalite.dsf', 'label' => 'DSF', 'icon' => 'ti-file-invoice', 'ref' => 'DSF'],
        'is' => ['route' => 'accounting.fiscalite.is', 'label' => 'Impôt sociétés', 'icon' => 'ti-building', 'ref' => 'IS'],
        'declarations' => ['route' => 'accounting.fiscalite.declarations', 'label' => 'Génération', 'icon' => 'ti-wand'],
        'echeances' => ['route' => 'accounting.fiscalite.echeances', 'label' => 'Échéances', 'icon' => 'ti-calendar-due'],
    ];
    $active = $active ?? $page ?? '';
@endphp

<div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
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
                <i class="ti {{ $item['icon'] }} me-1"></i>
                <span>{{ $item['label'] }}</span>
                @if(isset($item['ref']))
                    <span class="badge bg-info-subtle text-info ms-1 fs-10 px-1">#{{ $item['ref'] }}</span>
                @endif
            </a>
        </li>
    @endforeach
</ul>

<div v-if="error" class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0" v-if="Array.isArray(error)"><li v-for="(e,i) in error" :key="i">@{{ e }}</li></ul>
    <span v-else>@{{ error }}</span>
    <button type="button" class="btn-close" @click="error=null"></button>
</div>

<style>
    .bg-label-info { background-color: #d7f5fc !important; color: #03c3ec !important; }
    .fs-10 { font-size: 10px !important; }
</style>
