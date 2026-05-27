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
                <h4 class="mb-0 text-primary fw-bold">Tableau de Variation des Capitaux Propres (TVCP)</h4>
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
                            <th>Libellé des mouvements</th>
                            <th class="text-end" style="width: 200px">Ouverture</th>
                            <th class="text-end" style="width: 200px">Variation (+/-)</th>
                            <th class="text-end" style="width: 200px">Clôture</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(l,i) in data.lignes" :key="i" :class="rowClass(l)">
                            <td :class="{'ps-4': !isTitre(l) && !isTotal(l)}">@{{ l.libelle }}</td>
                            <td class="text-end font-monospace">@{{ fmt(l.ouverture) }}</td>
                            <td class="text-end font-monospace" :class="l.variation >= 0 ? 'text-success' : 'text-danger'">
                                @{{ l.variation > 0 ? '+' : '' }}@{{ fmt(l.variation) }}
                            </td>
                            <td class="text-end fw-bold text-dark font-monospace">@{{ fmt(l.cloture) }}</td>
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
</style>
@endpush

@push('scripts')
<script>window.__ETATS_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/etats/variation-kp.js') }}"></script>
@endpush
