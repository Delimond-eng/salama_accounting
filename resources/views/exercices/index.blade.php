@extends('layouts.app')

@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('exercices._nav', ['active' => 'index', 'title' => 'Registre des Exercices', 'breadcrumb' => 'Exercices'])

    <div class="card border-0 shadow-sm mb-4" v-if="showForm || !exercices.length">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold text-primary">@{{ form.id ? 'Modifier l\'exercice' : 'Créer un nouvel exercice comptable' }}</h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-uppercase">Libellé de l'exercice</label>
                    <input v-model="form.libelle" type="text" class="form-control border-2" placeholder="ex: EXERCICE 2024">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-uppercase">Année</label>
                    <input v-model.number="form.annee" type="number" class="form-control border-2 text-center font-monospace fw-bold">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-uppercase">Date début</label>
                    <input v-model="form.date_debut" type="date" class="form-control border-2">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-uppercase">Date fin</label>
                    <input v-model="form.date_fin" type="date" class="form-control border-2">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-uppercase">État</label>
                    <select v-model="form.statut" class="form-select border-2">
                        <option value="ouvert">Ouvert</option>
                        <option value="pre_cloture">Pré-clôture</option>
                        <option value="cloture">Clôturé</option>
                        <option value="archive">Archivé</option>
                    </select>
                </div>
                <div class="col-12 mt-4 d-flex gap-2">
                    <button type="button" class="btn btn-primary px-4" :disabled="isLoading" @click="saveExercice">
                        <i class="ti ti-device-floppy me-1"></i>Enregistrer l'exercice
                    </button>
                    <button v-if="exercices.length" type="button" class="btn btn-white border px-4" @click="showForm = false">Annuler</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="mb-0 fw-bold text-primary text-uppercase fs-14">Historique des exercices</h5>
                <p class="mb-0 text-muted small">Gestion des périodes comptables et activation de l'exercice courant.</p>
            </div>
            <button v-if="!showForm" type="button" class="btn btn-primary btn-sm px-3" @click="showForm = true">
                <i class="ti ti-plus me-1"></i>Nouvel Exercice
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Désignation</th>
                            <th>Période</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Écritures</th>
                            <th class="text-center">Indicateurs</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="6" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement…</td></tr>
                        <tr v-else-if="!exercices.length"><td colspan="6" class="text-center py-5 text-muted">Aucun exercice configuré</td></tr>
                        <tr v-for="ex in exercices" :key="ex.id" :class="{'table-primary-soft': ex.est_courant}">
                            <td>
                                <div class="d-flex align-items-center">
                                    <i v-if="ex.est_courant" class="ti ti-star-filled text-warning me-2 fs-18"></i>
                                    <strong class="text-dark">@{{ ex.libelle }}</strong>
                                    <span v-if="ex.est_courant" class="badge bg-primary ms-2 fs-10">ACTIF</span>
                                </div>
                            </td>
                            <td class="fs-13 text-muted">@{{ ex.date_debut }} <i class="ti ti-arrow-narrow-right mx-1"></i> @{{ ex.date_fin }}</td>
                            <td class="text-center">
                                <span class="badge rounded-pill text-uppercase fs-10" :class="statutClass(ex.statut)">@{{ statutLabel(ex.statut) }}</span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column">
                                    <span class="text-success fw-bold">@{{ ex.nb_ecritures }} validées</span>
                                    <small v-if="ex.nb_brouillons" class="text-danger fw-medium">@{{ ex.nb_brouillons }} brouillons</small>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <span v-if="ex.bilan_ouverture_genere" class="badge bg-label-info" title="Bilan d'ouverture généré">BO</span>
                                    <span v-if="ex.report_a_nouveau_genere" class="badge bg-label-secondary" title="Report à nouveau effectué">RAN</span>
                                    <span v-if="!ex.bilan_ouverture_genere && !ex.report_a_nouveau_genere" class="text-light-soft">—</span>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <button v-if="!ex.est_courant" type="button" class="btn btn-xs btn-outline-primary" @click="setCourant(ex.id)" title="Définir comme exercice de travail">
                                        Activer
                                    </button>
                                    <button type="button" class="btn btn-icon btn-sm btn-label-primary" @click="editExercice(ex)">
                                        <i class="ti ti-edit"></i>
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
    .table-primary-soft { background-color: rgba(63, 122, 253, 0.05); }
    .btn-xs { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
    .btn-label-primary { background: #e7e7ff; color: #696cff; border: none; }
    .btn-label-primary:hover { background: #696cff; color: #fff; }
    .bg-label-info { background-color: #e0f7fa; color: #00acc1; }
    .bg-label-secondary { background-color: #ebeef0; color: #8592a3; }
    .text-light-soft { color: #cbd5e1; }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/exercices/index.js') }}"></script>
@endpush
