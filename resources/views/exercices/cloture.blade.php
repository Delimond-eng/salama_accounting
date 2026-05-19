@extends('layouts.app')
@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
        @include('exercices._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])

        <div class="row g-3">
            <div class="col-xl-4">
                <div class="card border-0 rounded-0">
                    <div class="card-header"><h5 class="mb-0">Clôture mensuelle</h5></div>
                    <div class="card-body">
                        <p class="text-muted fs-13">Contrôles sur un mois (brouillons, cohérence) avant la clôture annuelle.</p>
                        <label class="form-label">Exercice</label>
                        <select v-model="exerciceId" class="form-select mb-2">
                            <option v-for="ex in exercicesActifs" :key="ex.id" :value="ex.id">@{{ ex.libelle }}</option>
                        </select>
                        <label class="form-label">Mois</label>
                        <input v-model="moisCloture" type="month" class="form-control mb-3">
                        <button type="button" class="btn btn-warning w-100" :disabled="isLoading" @click="controlesMensuels">
                            <i class="ti ti-lock me-1"></i>Contrôler le mois
                        </button>
                        <div v-if="controlesMois" class="mt-3 p-2 rounded" :class="controlesMois.pret ? 'bg-success-light' : 'bg-danger-light'">
                            <p class="mb-1 fw-medium">@{{ controlesMois.periode }}</p>
                            <p class="mb-0 fs-13">@{{ controlesMois.ecritures_validees }} écriture(s) validée(s).</p>
                            <ul v-if="controlesMois.erreurs.length" class="mb-0 mt-1 fs-13 text-danger">
                                <li v-for="(e,i) in controlesMois.erreurs" :key="i">@{{ e }}</li>
                            </ul>
                            <p v-else-if="controlesMois.message" class="mb-0 mt-1 fs-13 text-success">@{{ controlesMois.message }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card border-0 rounded-0">
                    <div class="card-header d-flex justify-content-between flex-wrap gap-2">
                        <h5 class="mb-0">Clôture annuelle</h5>
                        <select v-model="exerciceId" class="form-select form-select-sm w-auto">
                            <option v-for="ex in exercicesActifs" :key="ex.id" :value="ex.id">@{{ ex.libelle }}</option>
                        </select>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" :disabled="isLoading" @click="lancerControles">
                                <i class="ti ti-clipboard-check me-1"></i>Contrôles pré-clôture
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm" :disabled="isLoading || !controles?.pret" @click="preCloture">
                                Pré-clôture
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" :disabled="isLoading" @click="cloturer">
                                <i class="ti ti-archive me-1"></i>Clôturer définitivement
                            </button>
                        </div>
                        <div v-if="controles" class="border rounded p-3 mb-3">
                            <p class="mb-2">Résultat net estimé : <strong>@{{ fmt(controles.resultat_net) }}</strong></p>
                            <p class="mb-2">Bilan équilibré : <span :class="controles.bilan_equilibre ? 'text-success' : 'text-danger'">@{{ controles.bilan_equilibre ? 'Oui' : 'Non' }}</span></p>
                            <p class="mb-0">Brouillons : @{{ controles.brouillons }}</p>
                            <ul v-if="controles.erreurs.length" class="text-danger mt-2 mb-0">
                                <li v-for="(e,i) in controles.erreurs" :key="i">@{{ e }}</li>
                            </ul>
                            <ul v-if="controles.avertissements.length" class="text-warning mt-2 mb-0">
                                <li v-for="(a,i) in controles.avertissements" :key="i">@{{ a }}</li>
                            </ul>
                        </div>
                        <label class="form-label">Notes de clôture (optionnel)</label>
                        <textarea v-model="notes" class="form-control mb-0" rows="2"></textarea>
                        <p class="text-muted fs-12 mt-2 mb-0">La clôture génère une écriture journal CL (soldes des comptes 6 et 7 vers 131/139) puis verrouille l'exercice.</p>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/exercices/cloture.js') }}"></script>
@endpush
