@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('fiscalite._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('fiscalite._filtres')

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div>
                <h5 class="fw-bold mb-1">Générer les déclarations</h5>
                <p class="mb-0 text-muted small">Crée les brouillons pour la TVA (période sélectionnée) et l'IS (exercice en cours).</p>
            </div>
            <button type="button" class="btn btn-primary px-4" :disabled="generating" @click="generer">
                <i class="ti ti-wand me-1" v-if="!generating"></i>
                <span class="spinner-border spinner-border-sm me-1" v-else></span>
                <span v-if="generating">Génération en cours…</span>
                <span v-else>Lancer la génération</span>
            </button>
        </div>
    </div>

    <div class="row g-4 mb-4" v-if="resultat">
        <div class="col-12">
            <div class="card border-0 shadow-sm border-start border-primary border-3">
                <div class="card-header bg-white border-bottom-0 pt-3">
                    <h6 class="mb-0 fw-bold text-uppercase fs-12 text-muted">Aperçu du dernier calcul</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-3">
                        <div class="col-md-6 border-end">
                            <label class="d-block text-muted fs-11 text-uppercase mb-2">Synthèse TVA</label>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-label-secondary text-dark border">Coll. : @{{ fmt(resultat.synthese.tva_collectee) }}</span>
                                <span class="badge bg-label-secondary text-dark border">Déd. : @{{ fmt(resultat.synthese.tva_deductible) }}</span>
                                <span class="badge bg-soft-primary text-primary fw-bold">NETTE : @{{ fmt(resultat.synthese.tva_nette) }}</span>
                            </div>
                        </div>
                        <div class="col-md-6 ps-md-4">
                            <label class="d-block text-muted fs-11 text-uppercase mb-2">Synthèse Impôt (IS)</label>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-label-secondary text-dark border">Base : @{{ fmt(resultat.is_calcul.base_imposable) }}</span>
                                <span class="badge bg-soft-info text-info fw-bold">IS ESTIMÉ : @{{ fmt(resultat.is_calcul.montant_is) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4">
            <h4 class="mb-0 text-primary fw-bold">Déclarations enregistrées</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-fiscalite mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Période</th>
                            <th class="text-end">TVA Coll.</th>
                            <th class="text-end">TVA Déd.</th>
                            <th class="text-end">Impôt</th>
                            <th class="text-center">Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in declarations" :key="d.id">
                            <td><span class="badge bg-label-secondary font-monospace">@{{ d.type }}</span></td>
                            <td><span class="text-muted small">@{{ d.periode_debut }} au @{{ d.periode_fin }}</span></td>
                            <td class="text-end">@{{ fmt(d.tva_collectee) }}</td>
                            <td class="text-end">@{{ fmt(d.tva_deductible) }}</td>
                            <td class="text-end fw-semibold text-primary">@{{ fmt(d.montant_impot) }}</td>
                            <td class="text-center">
                                <span class="badge" :class="statutClass(d.statut)">@{{ statutLabel(d.statut) }}</span>
                            </td>
                            <td class="text-end">
                                <button v-if="d.statut !== 'deposee'" type="button" class="btn btn-xs btn-outline-success" @click="marquerDeposee(d)">
                                    <i class="ti ti-check me-1"></i>Marquer déposée
                                </button>
                                <span v-else class="text-muted small"><i class="ti ti-circle-check text-success me-1"></i>Déposée</span>
                            </td>
                        </tr>
                        <tr v-if="!declarations.length && !isLoading">
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="ti ti-layers-off fs-32 mb-2 d-block"></i>
                                Aucune déclaration enregistrée — lancez une génération pour la période souhaitée.
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
        padding: 12px 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13.5px;
    }
    .bg-label-secondary { background-color: #f1f3f4 !important; color: #5f6368 !important; }
    .btn-xs { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
</style>
@endpush

@push('scripts')
<script>window.__FISCALITE_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/fiscalite/declarations.js') }}"></script>
@endpush
