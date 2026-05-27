@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('analytique._nav', ['active' => 'rentabilite', 'title' => 'Rentabilité projets'])
    @include('analytique._filtres')
    <div class="card border-0">
        <div class="card-body p-0">
            <table class="table table-nowrap mb-0">
                <thead class="table-light">
                    <tr><th>Axe</th><th>Compte analytique</th><th class="text-end">Produits</th><th class="text-end">Charges</th><th class="text-end">Résultat</th></tr>
                </thead>
                <tbody>
                    <tr v-if="isLoading"><td colspan="5" class="text-center py-4">Chargement…</td></tr>
                    <tr v-else-if="!result?.projets?.length"><td colspan="5" class="text-center py-4 text-muted">Aucune donnée</td></tr>
                    <tr v-for="p in result.projets" :key="p.section_id">
                        <td><span class="badge badge-soft-info">@{{ p.axe_code }}</span></td>
                        <td>@{{ p.code }} — @{{ p.libelle }}</td>
                        <td class="text-end text-success">@{{ fmt(p.produits) }}</td>
                        <td class="text-end text-danger">@{{ fmt(p.charges) }}</td>
                        <td class="text-end fw-bold" :class="p.resultat >= 0 ? 'text-success' : 'text-danger'">@{{ fmt(p.resultat) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/analytique/rentabilite.js') }}"></script>
@endpush
