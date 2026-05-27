@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('etats._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('etats._filtres')

    <div class="card border-0 shadow-sm" v-if="data">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0 text-primary fw-bold">Tableau des Flux de Trésorerie (TFT)</h4>
                <p class="text-muted mb-0 small"><i class="ti ti-calendar-event me-1"></i>@{{ pageSubtitle }}</p>
            </div>
            <div class="text-end">
                <span class="badge bg-soft-info text-info fs-13 px-3 py-2">Unité : @{{ data.devise || 'CDF' }}</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 80px">Réf</th>
                            <th>Libellés des flux</th>
                            <th class="text-center" style="width: 60px">Note</th>
                            <th class="text-end" style="width: 180px">Exercice N</th>
                            <th class="text-end" style="width: 180px" v-if="filtres.avec_n1">Exercice N-1</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(l,i) in data.lignes" :key="i" :class="rowClass(l)">
                            <td class="text-muted fs-12 font-monospace">@{{ l.ref || '' }}</td>
                            <td :class="{'ps-4': !isTitre(l) && !isTotal(l), 'fw-bold': isTotal(l)}">@{{ l.libelle }}</td>
                            <td class="text-center text-muted small">@{{ l.note || '' }}</td>
                            <td class="text-end fw-semibold" :class="{'text-primary': isTotal(l)}">@{{ l.montant_n !== null ? fmt(l.montant_n) : '' }}</td>
                            <td class="text-end text-muted" v-if="filtres.avec_n1">@{{ l.montant_n1 !== null ? fmt(l.montant_n1) : '' }}</td>
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
    .table-custom thead th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        font-weight: 700;
        padding: 12px 15px;
        border-bottom: 2px solid #dee2e6;
        color: #475569;
    }
    .table-custom tbody td {
        padding: 10px 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13.5px;
    }
    .row-titre td {
        background-color: #f8fafc !important;
        color: #1e293b !important;
        font-weight: 700 !important;
        border-top: 1px solid #e2e8f0 !important;
    }
    .row-formule td {
        background-color: #f0f7ff !important;
        color: #0d6efd !important;
        border-top: 2px solid #0d6efd !important;
        border-bottom: 2px solid #0d6efd !important;
        font-weight: 700 !important;
    }
</style>
@endpush

@push('scripts')
<script>window.__ETATS_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/etats/flux-tresorerie.js') }}"></script>
@endpush
