@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('fiscalite._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('fiscalite._filtres')

    <div class="card border-0 shadow-sm" v-if="data">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0 text-primary fw-bold">TVA déductible</h4>
                <p class="text-muted mb-0 small"><i class="ti ti-calendar-event me-1"></i>@{{ filtres.date_debut }} au @{{ filtres.date_fin }}</p>
            </div>
            <div class="text-end">
                <span class="badge bg-soft-success text-success fs-13 px-3 py-2">Total : @{{ fmt(data.total) }} @{{ data.devise }}</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-fiscalite mb-0">
                    <thead>
                        <tr>
                            <th>Compte</th>
                            <th class="text-end">Montant débit net (@{{ data.devise }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(l,i) in data.detail" :key="i">
                            <td><span class="badge bg-label-secondary font-monospace fs-12">@{{ l.num_compte }}</span></td>
                            <td class="text-end fw-semibold">@{{ fmt(l.montant) }}</td>
                        </tr>
                        <tr v-if="!data.detail.length">
                            <td colspan="2" class="text-muted text-center py-5">
                                <i class="ti ti-info-circle fs-32 mb-2 d-block"></i>
                                Aucun mouvement TVA déductible sur la période.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-light fw-bold" v-if="data.detail.length">
                        <tr>
                            <td class="ps-4">TOTAL GÉNÉRAL</td>
                            <td class="text-end pe-4 text-success">@{{ fmt(data.total) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div v-else-if="!isLoading" class="alert alert-info shadow-sm border-0">
        <div class="d-flex align-items-center">
            <i class="ti ti-info-circle fs-24 me-2"></i>
            <div>Aucune donnée — vérifiez les comptes 445x.</div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('styles')
<style>
    .table-fiscalite thead th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        font-weight: 700;
        padding: 12px 15px;
        border-bottom: 2px solid #dee2e6;
        color: #475569;
    }
    .table-fiscalite tbody td {
        padding: 10px 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13.5px;
    }
    .bg-label-secondary { background-color: #f1f3f4 !important; color: #5f6368 !important; }
</style>
@endpush

@push('scripts')
<script>window.__FISCALITE_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/fiscalite/tva-deductible.js') }}"></script>
@endpush
