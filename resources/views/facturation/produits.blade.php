@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => 'produits', 'title' => 'Catalogue Produits & Services', 'breadcrumb' => 'Produits'])

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-lg-top" style="top: 100px; z-index: 10;">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold text-primary">@{{ form.id ? 'Modifier le produit' : 'Nouveau produit / service' }}</h5>
                </div>
                <div class="card-body p-4">
                    <form @submit.prevent="save">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Désignation <span class="text-danger">*</span></label>
                                <input class="form-control border-2" v-model="form.libelle" required placeholder="ex: Prestation de service, Vente de marchandises...">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Prix unitaire par défaut</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control border-2" v-model.number="form.prix_unitaire" placeholder="0.00">
                                    <span class="input-group-text bg-light border-2 border-start-0">CDF</span>
                                </div>
                            </div>
                            <div class="col-12"><hr class="my-2"></div>
                            <div class="col-12">
                                <label class="form-label fw-bold text-uppercase fs-11 text-muted">Comptes automatiques</label>
                                <div class="mb-3">
                                    <label class="form-label small">Compte de vente (Classe 7)</label>
                                    <input class="form-control form-control-sm border-2 font-monospace" v-model="form.compte_vente" placeholder="ex: 701100">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Compte d'achat (Classe 6)</label>
                                    <input class="form-control form-control-sm border-2 font-monospace" v-model="form.compte_achat" placeholder="ex: 601100">
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                                    <i class="ti ti-device-floppy me-1"></i>Enregistrer le produit
                                </button>
                                <button v-if="form.id" type="button" class="btn btn-outline-secondary btn-sm w-100 mt-2" @click="resetForm">
                                    Annuler la modification
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light-soft border-top p-3">
                    <a href="{{ route('accounting.facturation.workflow') }}" class="btn btn-link btn-sm text-decoration-none p-0">
                        <i class="ti ti-settings me-1"></i>Configurer le workflow de validation
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary">Liste des articles</h5>
                    <span class="badge bg-soft-info text-info">@{{ produits.length }} Éléments</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Désignation</th>
                                    <th class="text-end" style="width: 150px">Prix Unitaire</th>
                                    <th style="width: 200px">Comptes (V/A)</th>
                                    <th class="text-end" style="width: 80px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in produits" :key="p.id">
                                    <td class="fw-medium text-dark">@{{ p.libelle }}</td>
                                    <td class="text-end fw-bold text-primary">@{{ fmt(p.prix_unitaire) }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-label-secondary font-monospace" title="Vente">@{{ p.compte_vente || '—' }}</span>
                                            <i class="ti ti-arrows-left-right text-muted small"></i>
                                            <span class="badge bg-label-secondary font-monospace" title="Achat">@{{ p.compte_achat || '—' }}</span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-icon btn-sm btn-label-primary" @click="edit(p)">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!produits.length && !isLoading">
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="ti ti-package-off fs-32 mb-2 d-block"></i>
                                        Aucun produit dans le catalogue
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
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
    .bg-light-soft { background-color: #f8fafc; }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/facturation/produits.js') }}"></script>
@endpush
