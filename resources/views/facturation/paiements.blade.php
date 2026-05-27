@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => 'paiements', 'title' => 'Registre des Paiements', 'breadcrumb' => 'Paiements'])

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="search-box">
                        <div class="input-group input-group-sm border rounded-2 px-2 bg-light">
                            <span class="input-group-text bg-transparent border-0 p-0 me-2"><i class="ti ti-search text-muted"></i></span>
                            <input type="text" class="form-control bg-transparent border-0 ps-0" placeholder="Rechercher un paiement, une facture..." v-model="search" @input="debounceLoad">
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm border-2" v-model="filtreMethode" @change="loadList">
                        <option value="">Toutes les méthodes</option>
                        <option value="especes">Espèces</option>
                        <option value="banque">Virement / Chèque</option>
                        <option value="mobile_money">Mobile Money</option>
                    </select>
                </div>
                <div class="col-auto ms-auto">
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
                <h5 class="mb-0 fw-bold text-primary">Historique des encaissements & décaissements</h5>
                <p class="mb-0 text-muted small">Liste détaillée des transactions liées aux factures.</p>
            </div>
            <div class="text-end" v-if="paiements.length">
                <span class="badge bg-soft-success text-success px-3 py-2">@{{ paiements.length }} Transactions</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 120px">Référence</th>
                            <th style="width: 100px">Date</th>
                            <th>Facture liée</th>
                            <th class="text-end" style="width: 150px">Montant versé</th>
                            <th>Mode de règlement</th>
                            <th class="text-end" style="width: 120px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="6" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement…</td></tr>
                        <tr v-else-if="!paiements.length"><td colspan="6" class="text-center py-5 text-muted">Aucun paiement enregistré</td></tr>
                        <tr v-for="p in paiements" :key="p.id">
                            <td class="font-monospace fw-bold text-primary">@{{ p.numero }}</td>
                            <td class="text-muted">@{{ p.date_paiement }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="ti ti-file-invoice me-2 text-muted"></i>
                                    <span class="fw-medium">@{{ p.facture?.numero || '—' }}</span>
                                </div>
                            </td>
                            <td class="text-end fw-bold text-success">@{{ fmt(p.montant) }} <small>@{{ p.devise }}</small></td>
                            <td>
                                <span class="badge bg-label-secondary text-capitalize">
                                    <i class="ti ti-credit-card me-1"></i>@{{ p.methode }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a :href="'/accounting/facturation/paiements/'+p.id+'/pdf'" class="btn btn-sm btn-label-danger" target="_blank" title="Télécharger le reçu">
                                    <i class="ti ti-file-type-pdf me-1"></i>Reçu
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
    .btn-label-danger { background: #ffe5e5; color: #ff3e1d; border: none; }
    .btn-label-danger:hover { background: #ff3e1d; color: #fff; }
    .bg-label-secondary { background-color: #ebeef0; color: #8592a3; }
    .search-box { min-width: 300px; }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/facturation/paiements.js') }}"></script>
@endpush
