@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="search-box">
                        <div class="input-group input-group-sm border rounded-2 px-2 bg-light">
                            <span class="input-group-text bg-transparent border-0 p-0 me-2"><i class="ti ti-search text-muted"></i></span>
                            <input type="text" class="form-control bg-transparent border-0 ps-0" placeholder="Rechercher par n° de facture, tiers..." v-model="search" @input="debounceLoad">
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm border-2" v-model="filtreStatut" @change="loadList">
                        <option value="">Tous les statuts</option>
                        <option value="brouillon">Brouillon</option>
                        <option value="validee">Validée</option>
                        <option value="payee">Payée</option>
                        <option value="annulee">Annulée</option>
                    </select>
                </div>
                <div class="col-auto ms-auto">
                    <a :href="createUrl" class="btn btn-primary btn-sm px-3">
                        <i class="ti ti-plus me-1"></i>Nouvelle Facture
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-primary">Registre des Factures</h5>
                <p class="mb-0 text-muted small">Consultation et gestion des pièces de facturation.</p>
            </div>
            <div class="text-end" v-if="factures.length">
                <span class="badge bg-soft-info text-info px-3 py-2">@{{ factures.length }} Factures</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 120px">Numéro</th>
                            <th style="width: 100px">Date</th>
                            <th>Tiers / Client / Fournisseur</th>
                            <th class="text-end" style="width: 150px">Montant TTC</th>
                            <th class="text-center" style="width: 100px">Statut</th>
                            <th class="text-end" style="width: 150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="6" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement…</td></tr>
                        <tr v-else-if="!factures.length"><td colspan="6" class="text-center py-5 text-muted">Aucune facture trouvée</td></tr>
                        <tr v-for="f in factures" :key="f.id">
                            <td class="font-monospace fw-bold text-primary">@{{ f.numero }}</td>
                            <td class="text-muted">@{{ f.date_facture }}</td>
                            <td class="fw-medium text-dark">@{{ f.tiers?.nom }}</td>
                            <td class="text-end fw-bold">@{{ fmt(f.montant_ttc) }} <small class="text-muted">@{{ f.devise }}</small></td>
                            <td class="text-center">
                                <span class="badge rounded-pill" :class="badgeStatut(f.statut)">@{{ f.statut }}</span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a :href="editUrl(f.id)" class="btn btn-icon btn-sm btn-label-primary" title="Modifier/Voir">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    <a :href="pdfUrl(f.id)" class="btn btn-icon btn-sm btn-label-danger" target="_blank" title="PDF">
                                        <i class="ti ti-file-type-pdf"></i>
                                    </a>
                                    <button v-if="f.statut==='brouillon'" type="button" class="btn btn-icon btn-sm btn-label-success" @click="valider(f)" title="Valider">
                                        <i class="ti ti-check"></i>
                                    </button>
                                    <button v-if="f.statut==='validee'" type="button" class="btn btn-icon btn-sm btn-label-info" @click="payer(f)" title="Enregistrer paiement">
                                        <i class="ti ti-cash"></i>
                                    </button>
                                </div>
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
    .btn-label-primary { background: #e7e7ff; color: #696cff; border: none; }
    .btn-label-primary:hover { background: #696cff; color: #fff; }
    .btn-label-danger { background: #ffe5e5; color: #ff3e1d; border: none; }
    .btn-label-danger:hover { background: #ff3e1d; color: #fff; }
    .btn-label-success { background: #e8fadf; color: #71dd37; border: none; }
    .btn-label-success:hover { background: #71dd37; color: #fff; }
    .btn-label-info { background: #d7f5fc; color: #03c3ec; border: none; }
    .btn-label-info:hover { background: #03c3ec; color: #fff; }
</style>
@endpush

@push('scripts')
<script>window.__FACTURATION_PAGE__ = @json($page); window.__FACTURATION_TYPE__ = @json($type);</script>
<script type="module" src="{{ asset('assets/js/scripts/facturation/factures-liste.js') }}"></script>
@endpush
