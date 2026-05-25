@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('etats._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('etats._filtres')
    <div class="card border-0 rounded-0" v-if="data">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">@{{ data.titre }}</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Réf</th><th>Libellés</th><th>Note</th><th class="text-end">Exercice N</th><th class="text-end" v-if="filtres.avec_n1">N-1</th></tr>
                </thead>
                <tbody>
                    <tr v-for="(l,i) in data.lignes" :key="i" :class="l.type === 'total' || l.type === 'formule' ? 'bg-primary text-white fw-bold' : (isTitre(l) ? 'bg-light fw-bold text-uppercase' : '')">
                        <td>@{{ l.ref || '' }}</td>
                        <td>@{{ l.libelle }}</td>
                        <td class="text-center">@{{ l.note || '' }}</td>
                        <td class="text-end">@{{ l.montant_n !== null ? fmt(l.montant_n) : '' }}</td>
                        <td class="text-end" :class="l.type === 'total' || l.type === 'formule' ? 'text-white' : 'text-muted'" v-if="filtres.avec_n1">@{{ l.montant_n1 !== null ? fmt(l.montant_n1) : '' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__ETATS_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/etats/flux-tresorerie.js') }}"></script>
@endpush
