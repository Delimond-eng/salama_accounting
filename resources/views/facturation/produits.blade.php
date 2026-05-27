@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
<<<<<<< HEAD
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
=======
    @include('facturation._nav', ['active' => 'produits', 'title' => $title, 'breadcrumb' => $title])
    <div class="row g-3">
        <div class="col-md-5">
            <div class="card border-0 rounded-0">
                <div class="card-header"><h5 class="mb-0">@{{ form.id ? 'Modifier' : 'Nouveau' }} article</h5></div>
                <div class="card-body">
                    <div class="mb-2"><label class="form-label">Code</label><input class="form-control" v-model="form.code"></div>
                    <div class="mb-2"><label class="form-label">Libellé</label><input class="form-control" v-model="form.libelle"></div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label">Type</label>
                            <select class="form-select" v-model="form.type_article">
                                <option value="produit">Produit (stockable)</option>
                                <option value="service">Service</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Unité</label><input class="form-control" v-model="form.unite" placeholder="U, kg, L…"></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label">Prix CDF</label><input type="number" step="0.01" class="form-control" v-model.number="form.prix_unitaire_cdf"></div>
                        <div class="col-6"><label class="form-label">Prix USD</label><input type="number" step="0.01" class="form-control" v-model.number="form.prix_unitaire_usd"></div>
                    </div>
                    <div v-if="form.type_article==='produit'" class="border rounded p-2 mb-2 bg-light">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="gs" v-model="form.gestion_stock">
                            <label class="form-check-label" for="gs">Gestion de stock</label>
                        </div>
                        <div v-if="form.gestion_stock" class="row g-2">
                            <div class="col-6"><label class="form-label">Stock actuel</label><input type="number" step="0.01" class="form-control" v-model.number="form.stock_actuel" :disabled="!!form.id"></div>
                            <div class="col-6"><label class="form-label">Stock minimum</label><input type="number" step="0.01" class="form-control" v-model.number="form.stock_minimum"></div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" @click="save">Enregistrer</button>
                    <button v-if="form.id" type="button" class="btn btn-outline-light ms-2" @click="resetForm">Nouveau</button>
>>>>>>> 356d4919f7208489f8fadf9a5b1244abeb82c9b0
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
<<<<<<< HEAD
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
=======
                    <table class="table mb-0 table-nowrap">
                        <thead class="table-light">
                            <tr><th>Code</th><th>Libellé</th><th class="text-end">Prix CDF</th><th class="text-end">Prix USD</th><th class="text-end">Stock</th><th></th></tr>
                        </thead>
                        <tbody>
                            <tr v-if="!produits.length"><td colspan="6" class="text-center py-4 text-muted">Aucun article</td></tr>
                            <tr v-for="p in produits" :key="p.id">
                                <td>@{{ p.code || '—' }}</td>
                                <td>@{{ p.libelle }} <span class="badge badge-soft-secondary">@{{ p.type_article }}</span></td>
                                <td class="text-end">@{{ fmt(p.prix_unitaire_cdf) }}</td>
                                <td class="text-end">@{{ fmt(p.prix_unitaire_usd) }}</td>
                                <td class="text-end">@{{ p.gestion_stock ? fmt(p.stock_actuel) : '—' }}</td>
                                <td><button type="button" class="btn btn-sm btn-outline-primary" @click="edit(p)"><i class="ti ti-edit"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
>>>>>>> 356d4919f7208489f8fadf9a5b1244abeb82c9b0
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
