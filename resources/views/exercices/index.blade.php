@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
        @include('exercices._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])

        <div class="card border-0 rounded-0 mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Exercices de la société</h5>
                <button type="button" class="btn btn-primary btn-sm" @click="showForm = !showForm">
                    <i class="ti ti-plus me-1"></i>Nouvel exercice
                </button>
            </div>
            <div class="card-body" v-if="showForm">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">Libellé</label>
                        <input v-model="form.libelle" type="text" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Année</label>
                        <input v-model.number="form.annee" type="number" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Début</label>
                        <input v-model="form.date_debut" type="date" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fin</label>
                        <input v-model="form.date_fin" type="date" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Statut</label>
                        <select v-model="form.statut" class="form-select form-select-sm">
                            <option value="ouvert">Ouvert</option>
                            <option value="pre_cloture">Pré-clôture</option>
                            <option value="cloture">Clôturé</option>
                            <option value="archive">Archivé</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-success btn-sm w-100" :disabled="isLoading" @click="saveExercice">OK</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 rounded-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Exercice</th>
                                <th>Période</th>
                                <th>Statut</th>
                                <th class="text-center">Écritures</th>
                                <th>Flags</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="ex in exercices" :key="ex.id">
                                <td>
                                    <strong>@{{ ex.libelle }}</strong>
                                    <span v-if="ex.est_courant" class="badge bg-primary ms-1">Courant</span>
                                </td>
                                <td class="fs-12 text-muted">@{{ ex.date_debut }} → @{{ ex.date_fin }}</td>
                                <td><span class="badge" :class="statutClass(ex.statut)">@{{ statutLabel(ex.statut) }}</span></td>
                                <td class="text-center">
                                    <span class="text-success">@{{ ex.nb_ecritures }}</span>
                                    <span v-if="ex.nb_brouillons" class="text-danger ms-1">(@{{ ex.nb_brouillons }} brouillon(s))</span>
                                </td>
                                <td class="fs-12">
                                    <span v-if="ex.bilan_ouverture_genere" class="badge badge-soft-info me-1">BO</span>
                                    <span v-if="ex.report_a_nouveau_genere" class="badge badge-soft-secondary">RAN</span>
                                </td>
                                <td class="text-end">
                                    <button v-if="!ex.est_courant" type="button" class="btn btn-sm btn-outline-primary" @click="setCourant(ex.id)">Activer</button>
                                </td>
                            </tr>
                            <tr v-if="!exercices.length"><td colspan="6" class="text-center text-muted py-4">Aucun exercice.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/exercices/index.js') }}"></script>
@endpush
