@php
    $navItems = [
        'plan-comptable' => ['route' => 'accounting.parametres.plan-comptable', 'label' => 'Plan comptable', 'icon' => 'ti-list-tree'],
        'journaux' => ['route' => 'accounting.parametres.journaux', 'label' => 'Journaux', 'icon' => 'ti-notebook'],
        'devises' => ['route' => 'accounting.parametres.devises', 'label' => 'Devises', 'icon' => 'ti-currency-euro'],
        'tiers' => ['route' => 'accounting.parametres.tiers', 'label' => 'Tiers', 'icon' => 'ti-address-book'],
        'societe' => ['route' => 'accounting.parametres.societe', 'label' => 'Société & exercice', 'icon' => 'ti-building-community'],
    ];
    $active = $active ?? '';
@endphp

<div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
    <div>
        <h4 class="mb-1">{{ $title ?? 'Paramètres' }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('accounting.modules.show', ['module' => 'parametres']) }}">Paramètres</a></li>
                <li class="breadcrumb-item active">{{ $breadcrumb ?? 'Configuration' }}</li>
            </ol>
        </nav>
    </div>
    <div class="gap-2 d-flex align-items-center flex-wrap">
        <div class="d-flex align-items-center gap-2 me-2" v-if="societes && societes.length">
            <label class="form-label mb-0 text-muted fs-13 d-none d-sm-block">Société active</label>
            <select class="form-select form-select-sm w-auto border-0 shadow-sm" v-model="societeId" @change="changeSociete">
                <option v-for="s in societes" :key="s.id" :value="s.id">@{{ s.code }} — @{{ s.raison_sociale }}</option>
            </select>
        </div>

        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow"
           @click="loadData" :disabled="isLoading" data-bs-toggle="tooltip" title="Actualiser">
            <i class="ti ti-refresh" :class="{'ti-spin': isLoading}"></i>
        </a>
        <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" id="collapse-header">
            <i class="ti ti-transition-top"></i>
        </a>
    </div>
</div>

@can('parametres.view')
<ul class="nav nav-tabs nav-tabs-solid nav-tabs-rounded mb-4 flex-wrap">
    @foreach ($navItems as $key => $item)
        <li class="nav-item">
            <a class="nav-link {{ $active === $key ? 'active' : '' }}" href="{{ route($item['route']) }}">
                <i class="ti {{ $item['icon'] }} me-1"></i>{{ $item['label'] }}
            </a>
        </li>
    @endforeach
</ul>
@endcan

<div v-if="error" class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0" v-if="Array.isArray(error)"><li v-for="(e,i) in error" :key="i">@{{ e }}</li></ul>
    <span v-else>@{{ error }}</span>
    <button type="button" class="btn-close" @click="error=null"></button>
</div>

<div v-if="message" class="alert alert-success alert-dismissible fade show">
    @{{ message }}
    <button type="button" class="btn-close" @click="message=null"></button>
</div>
