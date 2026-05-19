@extends('layouts.app')
@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('etats._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('etats._filtres')
    <div class="card border-0 rounded-0" v-if="data">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">@{{ data.titre }}<br><small class="text-muted fw-normal">@{{ data.periode }}</small></h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Réf</th><th>Libellés</th><th>Note</th><th class="text-end">@{{ data.exercice_n }}</th><th class="text-end" v-if="filtres.avec_n1">@{{ data.exercice_n1 || 'N-1' }}</th></tr>
                </thead>
                <tbody>
                    <tr v-for="(l,i) in data.lignes" :key="i" :class="{'table-secondary fw-bold': l.type==='formule'}">
                        <td>@{{ l.ref || '' }}</td>
                        <td>@{{ l.libelle }}</td>
                        <td class="text-center">@{{ l.note || '' }}</td>
                        <td class="text-end">@{{ fmt(l.montant_n) }}</td>
                        <td class="text-end text-muted" v-if="filtres.avec_n1">@{{ fmt(l.montant_n1) }}</td>
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
<script type="module" src="{{ asset('assets/js/scripts/etats/compte-resultat.js') }}"></script>
@endpush
