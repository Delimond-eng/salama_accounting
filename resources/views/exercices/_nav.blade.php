@php
    $navItems = [
        'index' => ['route' => 'accounting.exercices.index', 'label' => 'Multi-exercices', 'icon' => 'ti-layers-linked'],
        'ouverture' => ['route' => 'accounting.exercices.ouverture', 'label' => 'Ouverture', 'icon' => 'ti-door-enter'],
        'cloture' => ['route' => 'accounting.exercices.cloture', 'label' => 'Clôture', 'icon' => 'ti-lock'],
        'report-a-nouveau' => ['route' => 'accounting.exercices.report-a-nouveau', 'label' => 'Report à nouveau', 'icon' => 'ti-arrow-forward-up'],
    ];
    $active = $active ?? $page ?? '';
@endphp

<div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
    <div>
        <h4 class="mb-1">{{ $title ?? 'Exercices comptables' }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('accounting.modules.show', ['module' => 'exercices']) }}">Exercices</a></li>
                <li class="breadcrumb-item active">{{ $breadcrumb ?? $title ?? '' }}</li>
            </ol>
        </nav>
    </div>
    <div class="gap-2 d-flex align-items-center flex-wrap">
        <a href="{{ route('accounting.etats.comparatif') }}" class="btn btn-outline-primary shadow px-3">
            <i class="ti ti-chart-dots-3 me-1"></i>Comparatif N / N-1
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
<div v-if="message" class="alert alert-success alert-dismissible fade show">
    @{{ message }}
    <button type="button" class="btn-close" @click="message=null"></button>
</div>
