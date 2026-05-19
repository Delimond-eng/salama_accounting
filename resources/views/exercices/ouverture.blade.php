@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
        @include('exercices._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border-0 rounded-0 h-100">
                    <div class="card-header"><h5 class="mb-0">1. Créer l'exercice suivant</h5></div>
                    <div class="card-body">
                        <p class="text-muted fs-13">À partir d'un exercice <strong>clôturé</strong>, crée automatiquement l'exercice N+1 (dates et libellé).</p>
                        <label class="form-label">Exercice source (clôturé)</label>
                        <select v-model="sourceId" class="form-select mb-3">
                            <option :value="null">— Choisir —</option>
                            <option v-for="ex in exercicesClotures" :key="ex.id" :value="ex.id">@{{ ex.libelle }} (@{{ ex.annee }})</option>
                        </select>
                        <button type="button" class="btn btn-primary w-100" :disabled="!sourceId || isLoading" @click="creerSuivant">
                            <i class="ti ti-calendar-plus me-1"></i>Créer l'exercice suivant
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 rounded-0 h-100">
                    <div class="card-header"><h5 class="mb-0">2. Bilan d'ouverture (journal AN)</h5></div>
                    <div class="card-body">
                        <p class="text-muted fs-13">Reprise des soldes des comptes de bilan (classes 1 à 5) depuis l'exercice précédent clôturé.</p>
                        <label class="form-label">Exercice cible (ouvert)</label>
                        <select v-model="cibleId" class="form-select mb-3">
                            <option :value="null">— Choisir —</option>
                            <option v-for="ex in exercicesOuverts" :key="ex.id" :value="ex.id">@{{ ex.libelle }}</option>
                        </select>
                        <button type="button" class="btn btn-success w-100" :disabled="!cibleId || isLoading" @click="genererBilan">
                            <i class="ti ti-file-import me-1"></i>Générer le bilan d'ouverture
                        </button>
                        <p v-if="cibleSelectionnee && cibleSelectionnee.bilan_ouverture_genere" class="text-success mt-2 mb-0 fs-13">
                            <i class="ti ti-check"></i> Bilan d'ouverture déjà généré pour cet exercice.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/exercices/ouverture.js') }}"></script>
@endpush
