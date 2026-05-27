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
                    <h5 class="mb-0 fw-bold text-primary">@{{ form.id ? 'Modifier l\'article' : 'Nouvel article / service' }}</h5>
                </div>
                <div class="card-body p-4">
                    <form @submit.prevent="save">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Code article</label>
                                <input class="form-control border-2 text-uppercase font-monospace" v-model="form.code" placeholder="ex: ART-001">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Désignation <span class="text-danger">*</span></label>
                                <input class="form-control border-2" v-model="form.libelle" required placeholder="ex: Prestation de service, Vente de marchandises...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Type</label>
                                <select class="form-select border-2" v-model="form.type_article">
                                    <option value="produit">Produit (stockable)</option>
                                    <option value="service">Service</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Unité</label>
                                <input class="form-control border-2" v-model="form.unite" placeholder="U, kg, L…">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Prix CDF</label>
                                <input type="number" step="0.01" class="form-control border-2" v-model.number="form.prix_unitaire_cdf" placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Prix USD</label>
                                <input type="number" step="0.01" class="form-control border-2" v-model.number="form.prix_unitaire_usd" placeholder="0.00">
                            </div>

                            <div v-if="form.type_article==='produit'" class="col-12 border rounded-3 p-3 bg-light-soft">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="gs" v-model="form.gestion_stock">
                                    <label class="form-check-label fw-medium" for="gs">Gestion de stock activée</label>
                                </div>
                                <div v-if="form.gestion_stock" class="row g-2 mt-2">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Stock actuel</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm border-2" v-model.number="form.stock_actuel" :disabled="!!form.id">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Stock mini</label>
                                        <input type="number" step="0.01" class="form-control form-control-sm border-2" v-model.number="form.stock_minimum">
                                    </div>
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
                                    <i class="ti ti-device-floppy me-1"></i>Enregistrer
                                </button>
                                <button v-if="form.id" type="button" class="btn btn-outline-secondary btn-sm w-100 mt-2" @click="resetForm">
                                    Annuler
                                </button>
                            </div>
                        </div>
                    </form>
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
                                    <th>Code</th>
                                    <th>Désignation</th>
                                    <th class="text-end">Prix CDF</th>
                                    <th class="text-end">Prix USD</th>
                                    <th class="text-center">Stock</th>
                                    <th class="text-end" style="width: 80px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in produits" :key="p.id">
                                    <td class="font-monospace fw-bold text-primary">@{{ p.code || '—' }}</td>
                                    <td class="fw-medium text-dark">
                                        @{{ p.libelle }}
                                        <span class="badge badge-soft-secondary ms-1">@{{ p.type_article }}</span>
                                    </td>
                                    <td class="text-end fw-bold">@{{ fmt(p.prix_unitaire_cdf) }}</td>
                                    <td class="text-end fw-bold">@{{ fmt(p.prix_unitaire_usd) }}</td>
                                    <td class="text-center">
                                        <span v-if="p.gestion_stock" :class="p.stock_actuel <= p.stock_minimum ? 'text-danger fw-bold' : 'text-success'">
                                            @{{ fmt(p.stock_actuel) }}
                                        </span>
                                        <span v-else class="text-muted small">N/A</span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-icon btn-sm btn-label-primary" @click="edit(p)">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!produits.length && !isLoading">
                                    <td colspan="6" class="text-center py-5 text-muted">
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
