@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => 'demandes', 'title' => 'Demandes de Fonds', 'breadcrumb' => 'Demandes de fonds'])

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="search-box">
                        <div class="input-group input-group-sm border rounded-2 px-2 bg-light">
                            <span class="input-group-text bg-transparent border-0 p-0 me-2"><i class="ti ti-search text-muted"></i></span>
                            <input type="text" class="form-control bg-transparent border-0 ps-0" placeholder="Rechercher une demande..." v-model="search" @input="debounceLoad">
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm border-2" v-model="filtreStatut" @change="loadList">
                        <option value="">Tous les statuts</option>
                        <option value="brouillon">Brouillon</option>
                        <option value="en_validation">En attente</option>
                        <option value="approuvee">Approuvée</option>
                        <option value="executee">Exécutée</option>
                        <option value="rejetee">Rejetée</option>
                    </select>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('accounting.facturation.demandes.create') }}" class="btn btn-primary btn-sm px-3">
                        <i class="ti ti-plus me-1"></i>Nouvelle Demande
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-primary">Suivi des Demandes de Paiement</h5>
                <p class="mb-0 text-muted small">Workflow de validation des sorties de fonds.</p>
            </div>
            <div class="text-end" v-if="demandes.length">
                <span class="badge bg-soft-info text-info px-3 py-2">@{{ demandes.length }} Demandes</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 120px">N° Demande</th>
                            <th>Demandeur</th>
                            <th class="text-end" style="width: 150px">Montant</th>
                            <th>Étape actuelle</th>
                            <th class="text-center" style="width: 120px">Statut</th>
                            <th class="text-end" style="width: 100px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="6" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement des demandes...</td></tr>
                        <tr v-else-if="!demandes.length"><td colspan="6" class="text-center py-5 text-muted">Aucune demande trouvée</td></tr>
                        <tr v-for="d in demandes" :key="d.id">
                            <td class="font-monospace fw-bold text-primary">@{{ d.numero }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-xs me-2 bg-light rounded-circle">
                                        <i class="ti ti-user text-muted fs-12"></i>
                                    </div>
                                    <span class="fw-medium">@{{ d.demandeur?.name }}</span>
                                </div>
                            </td>
                            <td class="text-end fw-bold">@{{ fmt(d.montant) }} <small class="text-muted">@{{ d.devise }}</small></td>
                            <td>
                                <span v-if="d.etape_courante" class="text-dark small fw-medium">
                                    <i class="ti ti-arrow-right-circle me-1 text-primary"></i>@{{ d.etape_courante.libelle }}
                                </span>
                                <span v-else class="text-light-soft">—</span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill" :class="badgeStatut(d.statut)">@{{ d.statut }}</span>
                            </td>
                            <td class="text-end">
                                <a :href="'/accounting/facturation/demandes/'+d.id" class="btn btn-icon btn-sm btn-label-primary" title="Consulter">
                                    <i class="ti ti-eye"></i>
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
    .btn-label-primary { background: #e7e7ff; color: #696cff; border: none; }
    .btn-label-primary:hover { background: #696cff; color: #fff; }
    .search-box { min-width: 300px; }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/facturation/demandes-liste.js') }}"></script>
@endpush
