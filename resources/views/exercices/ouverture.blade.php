@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
        @include('exercices._nav', ['active' => 'ouverture', 'title' => 'Ouverture de l\'Exercice', 'breadcrumb' => 'Ouverture'])

        <div class="row g-4">
            <!-- Étape 1 : Création -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 fw-bold text-primary"><i class="ti ti-calendar-plus me-2"></i>1. Création de l'exercice suivant</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert bg-label-primary border-0 mb-4">
                            <p class="mb-0 small">Initialise l'exercice N+1 (dates et libellé) à partir d'un exercice ayant le statut <strong>clôturé</strong>.</p>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase">Exercice source (référence)</label>
                            <select v-model="sourceId" class="form-select border-2">
                                <option :value="null">— Choisir un exercice clôturé —</option>
                                <option v-for="ex in exercicesClotures" :key="ex.id" :value="ex.id">@{{ ex.libelle }} (@{{ ex.annee }})</option>
                            </select>
                        </div>

                        <button type="button" class="btn btn-primary w-100 py-2 fw-bold" :disabled="!sourceId || isLoading" @click="creerSuivant">
                            <i class="ti ti-arrow-forward me-1"></i>Générer l'exercice N+1
                        </button>
                    </div>
                </div>
            </div>

            <!-- Étape 2 : Bilan d'ouverture -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 fw-bold text-success"><i class="ti ti-file-import me-2"></i>2. Bilan d'ouverture (Journal AN)</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert bg-label-success border-0 mb-4">
                            <p class="mb-0 small">Reprise automatique des soldes de clôture (classes 1 à 5) vers l'exercice ouvert.</p>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase">Exercice cible (destination)</label>
                            <select v-model="cibleId" class="form-select border-2">
                                <option :value="null">— Choisir l'exercice ouvert —</option>
                                <option v-for="ex in exercicesOuverts" :key="ex.id" :value="ex.id">@{{ ex.libelle }}</option>
                            </select>
                        </div>

                        <button type="button" class="btn btn-success w-100 py-2 fw-bold" :disabled="!cibleId || isLoading" @click="genererBilan">
                            <i class="ti ti-copy me-1"></i>Importer les soldes à nouveau
                        </button>

                        <div v-if="cibleSelectionnee && cibleSelectionnee.bilan_ouverture_genere" class="mt-3 p-2 bg-soft-success rounded-3 text-center">
                            <span class="text-success fw-bold small"><i class="ti ti-circle-check-filled me-1"></i>Bilan d'ouverture déjà généré.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Note technique -->
        <div class="mt-4 p-3 bg-light rounded-3 border border-dashed">
            <h6 class="fw-bold mb-2 small text-uppercase text-muted">Aide au processus :</h6>
            <p class="mb-0 fs-12 text-muted">
                Le bilan d'ouverture crée une pièce comptable dans le journal <strong>AN (À-Nouveau)</strong>.
                Il est conseillé de vérifier la balance d'ouverture immédiatement après cette opération.
            </p>
        </div>
    </template>
</div>
@endsection

@push('styles')
<style>
    .bg-label-primary { background-color: #e7e7ff !important; color: #696cff !important; }
    .bg-label-success { background-color: #e8fadf !important; color: #71dd37 !important; }
    .bg-soft-success { background-color: rgba(113, 221, 55, 0.1); }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/exercices/ouverture.js') }}"></script>
@endpush
