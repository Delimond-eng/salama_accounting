@extends('layouts.app')

@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('parametres._nav', ['active' => 'journaux', 'title' => 'Configuration des journaux', 'breadcrumb' => 'Journaux'])

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3 flex-wrap gap-3">
            <div>
                <h5 class="mb-0 fw-bold text-primary">Référentiel des Journaux</h5>
                <p class="mb-0 text-muted small">Définissez les codes et types de journaux pour la saisie comptable.</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                @include('components.export-buttons')
                <button type="button" class="btn btn-primary btn-sm px-3" @click="openForm()">
                    <i class="ti ti-plus me-1"></i>Nouveau journal
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 100px">Code</th>
                            <th>Intitulé du journal</th>
                            <th>Type de journal</th>
                            <th>Contrepartie</th>
                            <th>Numérotation</th>
                            <th class="text-center">Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="7" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement…</td></tr>
                        <tr v-else-if="!journaux.length"><td colspan="7" class="text-center py-5 text-muted">Aucun journal configuré</td></tr>
                        <tr v-for="j in journaux" :key="j.id">
                            <td class="font-monospace fw-bold text-primary text-center"><span class="badge bg-label-primary px-3">@{{ j.code }}</span></td>
                            <td class="fw-medium">@{{ j.libelle }}</td>
                            <td><span class="badge bg-soft-info text-info text-uppercase fs-11">@{{ j.type }}</span></td>
                            <td>
                                <span v-if="j.compte_contrepartie" class="font-monospace text-muted">@{{ j.compte_contrepartie }}</span>
                                <span v-else class="text-light-soft">—</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fs-13 text-capitalize">@{{ j.format_numerotation }}</span>
                                    <small v-if="j.prefixe_piece" class="text-muted">Préfixe: @{{ j.prefixe_piece }}</small>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill" :class="j.actif ? 'bg-soft-success text-success' : 'bg-soft-secondary text-secondary'">
                                    @{{ j.actif ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-icon btn-sm btn-label-primary" @click="editJournal(j)">
                                    <i class="ti ti-edit"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Journal -->
    <div class="modal fade" id="modal_journal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary py-3">
                    <h5 class="modal-title text-white fw-bold">@{{ form.id ? 'Modifier le journal' : 'Nouveau journal' }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="saveJournal">
                    <div class="modal-body p-4">
                        <div class="row g-4">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Code Journal <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-2 text-uppercase font-monospace" v-model="form.code" required maxlength="10" placeholder="ex: BQ-01">
                            </div>
                            <div class="col-md-9">
                                <label class="form-label fw-bold">Libellé du journal <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-2" v-model="form.libelle" required placeholder="Nom complet du journal">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Type de journal <span class="text-danger">*</span></label>
                                <select class="form-select border-2" v-model="form.type" required>
                                    <option value="achats">Achats</option>
                                    <option value="ventes">Ventes</option>
                                    <option value="banque">Banque</option>
                                    <option value="caisse">Caisse</option>
                                    <option value="operations_diverses">Opérations diverses</option>
                                    <option value="salaires">Salaires</option>
                                    <option value="stocks">Stocks</option>
                                    <option value="immobilisations">Immobilisations</option>
                                    <option value="ouverture">Ouverture / Report</option>
                                    <option value="cloture">Clôture</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Compte de contrepartie par défaut</label>
                                @include('components.compte-select', ['compteKey' => 'journal_cp', 'inputClass' => 'form-control border-2'])
                            </div>
                            <div class="col-12"><hr class="my-0"></div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Préfixe des pièces</label>
                                <input type="text" class="form-control border-2" v-model="form.prefixe_piece" placeholder="ex: FA-">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Mode de numérotation</label>
                                <select class="form-select border-2" v-model="form.format_numerotation">
                                    <option value="annuel">Annuel (2024-001)</option>
                                    <option value="mensuel">Mensuel (05-001)</option>
                                    <option value="continu">Continu (00001)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Nombre de zéros (Padding)</label>
                                <input type="number" class="form-control border-2" v-model.number="form.padding_numero" min="1" max="8">
                            </div>
                            <div class="col-12">
                                <div class="bg-light p-3 rounded-3 border">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" v-model="form.actif" id="chk_act">
                                                <label class="form-check-label fw-medium" for="chk_act">Journal Actif</label>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" v-model="form.saisie_tiers_obligatoire" id="chk_st">
                                                <label class="form-check-label fw-medium" for="chk_st">Saisie tiers obligatoire</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fs-12 mb-0">Ordre d'affichage</label>
                                            <input type="number" class="form-control form-control-sm" v-model.number="form.ordre_affichage">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top-0 p-3">
                        <button type="button" class="btn btn-white px-4 border" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary px-4" :disabled="isLoading">
                            <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                            Enregistrer le journal
                        </button>
                    </div>
                </form>
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
    .bg-label-primary { background: #e7e7ff; color: #696cff; }
    .btn-label-primary { background: #e7e7ff; color: #696cff; border: none; }
    .btn-label-primary:hover { background: #696cff; color: #fff; }
    .text-light-soft { color: #cbd5e1; }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/parametres/journaux.js') }}"></script>
@endpush
