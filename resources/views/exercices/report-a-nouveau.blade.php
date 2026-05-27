@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
        @include('exercices._nav', ['active' => 'report-a-nouveau', 'title' => 'Affectation du Résultat', 'breadcrumb' => 'Report à nouveau'])

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold text-primary"><i class="ti ti-arrow-forward-up me-2"></i>Génération du Report à Nouveau (RAN)</h5>
            </div>
            <div class="card-body p-4">
                <div class="alert bg-label-primary border-0 mb-4">
                    <div class="d-flex">
                        <i class="ti ti-info-circle fs-24 me-2"></i>
                        <div>
                            <h6 class="fw-bold mb-1 text-primary">Logique de traitement</h6>
                            <p class="mb-0 small">
                                Cette opération permet de basculer les soldes des comptes de résultat (131/139) de l'exercice précédent vers les comptes de report à nouveau (121/129) de l'exercice courant.
                                Une écriture est générée automatiquement dans le journal <strong>AN (À-Nouveau)</strong> à la date d'ouverture.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row g-4 align-items-center">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase text-muted">Exercice de destination (ouvert)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-2 border-end-0"><i class="ti ti-calendar"></i></span>
                            <select v-model="exerciceId" class="form-select border-2">
                                <option v-for="ex in exercicesOuverts" :key="ex.id" :value="ex.id">@{{ ex.libelle }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 pt-md-4">
                        <button type="button" class="btn btn-primary px-5 py-2 fw-bold" :disabled="!exerciceId || isLoading" @click="genererRan">
                            <i class="ti ti-refresh me-1" v-if="!isLoading"></i>
                            <span class="spinner-border spinner-border-sm me-1" v-else></span>
                            Exécuter l'affectation du résultat
                        </button>
                    </div>
                </div>

                <div v-if="selection && selection.report_a_nouveau_genere" class="mt-4 p-3 bg-soft-success rounded-3 border border-success border-opacity-25 d-flex align-items-center">
                    <i class="ti ti-circle-check-filled text-success fs-20 me-2"></i>
                    <span class="text-success fw-bold">Succès : Le report à nouveau a déjà été généré pour cet exercice.</span>
                </div>
            </div>

            <div class="card-footer bg-light-soft border-top p-3 text-center">
                <p class="mb-0 text-muted small">
                    <i class="ti ti-alert-circle me-1"></i>
                    Assurez-vous que l'exercice précédent est bien clôturé avant de lancer cette procédure.
                </p>
            </div>
        </div>
    </template>
</div>
@endsection

@push('styles')
<style>
    .bg-label-primary { background-color: #e7e7ff !important; color: #696cff !important; }
    .bg-soft-success { background-color: rgba(113, 221, 55, 0.1); }
    .bg-light-soft { background-color: #f8fafc; }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/exercices/report-a-nouveau.js') }}"></script>
@endpush
