@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="search-box" style="min-width: 300px;">
                    <div class="input-group input-group-sm border rounded-2 px-2 bg-light">
                        <span class="input-group-text bg-transparent border-0 p-0 me-2"><i class="ti ti-search text-muted"></i></span>
                        <input type="text" class="form-control bg-transparent border-0 ps-0" placeholder="Rechercher une facture, un tiers..." v-model="search" @input="debounceLoad">
                    </div>
                </div>
                <div class="d-flex gap-2">
                    @include('components.export-buttons')
                    <button type="button" class="btn btn-outline-primary btn-sm px-3" @click="loadList">
                        <i class="ti ti-refresh me-1"></i>Actualiser
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-primary">Suivi des Échéances</h5>
                <p class="mb-0 text-muted small">Visualisation des factures en attente de règlement et retards de paiement.</p>
            </div>
            <div class="text-end" v-if="items.length">
                <span class="badge bg-soft-danger text-danger px-3 py-2">@{{ items.filter(i => i.en_retard).length }} En retard</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 130px">N° Facture</th>
                            <th>Tiers / Partenaire</th>
                            <th style="width: 120px">Date Échéance</th>
                            <th class="text-end" style="width: 150px">Reste à payer</th>
                            <th class="text-center" style="width: 120px">Délai / Retard</th>
                            <th class="text-end" style="width: 80px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="6" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Analyse des échéances...</td></tr>
                        <tr v-else-if="!items.length"><td colspan="6" class="text-center py-5 text-muted">Aucune échéance à venir</td></tr>
                        <tr v-for="i in items" :key="i.id" :class="{'table-danger-soft': i.en_retard}">
                            <td class="font-monospace fw-bold text-primary">@{{ i.numero }}</td>
                            <td class="fw-medium text-dark">@{{ i.tiers }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="ti ti-calendar me-2 text-muted"></i>
                                    <span>@{{ i.date_echeance }}</span>
                                </div>
                            </td>
                            <td class="text-end fw-bold">@{{ fmt(i.montant_ttc) }} <small class="text-muted">@{{ i.devise }}</small></td>
                            <td class="text-center">
                                <span v-if="i.en_retard" class="badge bg-danger rounded-pill px-3">
                                    <i class="ti ti-alert-triangle me-1"></i>Retard @{{ i.retard_jours }} j
                                </span>
                                <span v-else class="badge bg-soft-info text-info rounded-pill px-3">
                                    @{{ Math.abs(i.retard_jours) }} jours restants
                                </span>
                            </td>
                            <td class="text-end">
                                <a :href="'/accounting/facturation/factures/' + i.id + '/pdf'" class="btn btn-icon btn-sm btn-label-primary" target="_blank" title="Voir PDF">
                                    <i class="ti ti-file-invoice"></i>
                                </a>
                            </td>
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
        padding: 12px 15px;
        vertical-align: middle;
        font-size: 13.5px;
        border-bottom: 1px solid #f1f5f9;
    }
    .table-danger-soft { background-color: rgba(231, 13, 13, 0.03) !important; }
    .btn-label-primary { background: #e7e7ff; color: #696cff; border: none; }
    .btn-label-primary:hover { background: #696cff; color: #fff; }
</style>
@endpush

@push('scripts')
<script>window.__ECHEANCIER_CIBLE__ = @json($cible); window.__FACTURATION_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/facturation/echeancier.js') }}"></script>
@endpush
