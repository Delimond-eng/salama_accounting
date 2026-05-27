@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('analytique._nav', ['active' => 'dashboard', 'title' => 'Tableau de bord analytique'])
    @include('analytique._filtres')
    <div class="row g-3 mb-3" v-if="result">
        <div class="col-md-4" v-for="p in result.top_projets_couteux" :key="p.section_id">
            <div class="card border-0 h-100">
                <div class="card-body">
                    <p class="text-muted fs-12 mb-1">@{{ p.code }}</p>
                    <h6 class="mb-2">@{{ p.libelle }}</h6>
                    <p class="mb-0 text-danger fw-bold">Charges : @{{ fmt(p.charges) }}</p>
                    <p class="mb-0 fs-12">Résultat : <span :class="p.resultat>=0?'text-success':'text-danger'">@{{ fmt(p.resultat) }}</span></p>
                </div>
            </div>
        </div>
    </div>
    <div class="card border-0" v-if="result?.rentabilite?.projets">
        <div class="card-header"><h6 class="mb-0">Rentabilité par projet</h6></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Projet</th><th class="text-end">Produits</th><th class="text-end">Charges</th><th class="text-end">Résultat</th></tr></thead>
                <tbody>
                    <tr v-for="p in result.rentabilite.projets" :key="p.section_id">
                        <td>@{{ p.libelle }}</td>
                        <td class="text-end">@{{ fmt(p.produits) }}</td>
                        <td class="text-end">@{{ fmt(p.charges) }}</td>
                        <td class="text-end" :class="p.resultat>=0?'text-success':'text-danger'">@{{ fmt(p.resultat) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/analytique/dashboard.js') }}"></script>
@endpush
