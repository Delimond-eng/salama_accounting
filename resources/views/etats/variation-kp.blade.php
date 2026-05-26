@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('etats._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('etats._filtres')
    <div class="card border-0 shadow-sm" v-if="data">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4">
            <h4 class="mb-0 text-primary fw-bold">@{{ data.titre }}</h4>
            <p class="text-muted mb-0 small">Tableau de variation des capitaux propres</p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-etats mb-0">
                    <thead>
                        <tr>
                            <th>Libellé</th>
                            <th class="text-end" style="width: 200px">Ouverture</th>
                            <th class="text-end" style="width: 200px">Variation</th>
                            <th class="text-end" style="width: 200px">Clôture</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(l,i) in data.lignes" :key="i" :class="rowClass(l)">
                            <td :class="{'ps-4': !isTitre(l) && !isTotal(l)}">@{{ l.libelle }}</td>
                            <td class="text-end">@{{ fmt(l.ouverture) }}</td>
                            <td class="text-end">@{{ fmt(l.variation) }}</td>
                            <td class="text-end fw-bold text-dark">@{{ fmt(l.cloture) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('styles')
<style>
    .table-etats thead th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        font-weight: 700;
        padding: 12px 15px;
        border-bottom: 2px solid #dee2e6;
    }
    .table-etats tbody td {
        padding: 10px 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f1f1;
    }
    .row-titre td {
        background-color: #fdfdfd !important;
        color: #334155;
        font-size: 13px;
        border-top: 1px solid #eee;
    }
    .row-formule td {
        background-color: #f0f7ff !important;
        color: #000;
        border-top: 2px solid #0d6efd !important;
        border-bottom: 2px solid #0d6efd !important;
    }
    .row-formule td:first-child { border-left: 4px solid #0d6efd; }
</style>
@endpush

@push('scripts')
<script>window.__ETATS_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/etats/variation-kp.js') }}"></script>
@endpush
