@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
        @include('exercices._nav', ['active' => 'cloture', 'title' => 'Processus de Clôture', 'breadcrumb' => 'Clôture annuelle'])

        <div class="row g-4">
            <!-- Section Clôture Mensuelle -->
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="mb-0 fw-bold text-primary"><i class="ti ti-lock-access me-2"></i>Clôture mensuelle</h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted fs-13 mb-4">Verrouillage des écritures d'un mois spécifique après vérification des brouillons et de la cohérence.</p>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Exercice cible</label>
                            <select v-model="exerciceId" class="form-select border-2">
                                <option v-for="ex in exercicesActifs" :key="ex.id" :value="ex.id">@{{ ex.libelle }}</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small">Mois à clôturer</label>
                            <input v-model="moisCloture" type="month" class="form-control border-2">
                        </div>

                        <button type="button" class="btn btn-warning w-100 py-2 fw-bold" :disabled="isLoading || !moisCloture" @click="controlesMensuels">
                            <i class="ti ti-shield-check me-1"></i>Contrôler & Verrouiller
                        </button>

                        <div v-if="controlesMois" class="mt-4 p-3 rounded-3 border border-dashed" :class="controlesMois.pret ? 'bg-soft-success border-success' : 'bg-soft-danger border-danger'">
                            <div class="d-flex align-items-center mb-2">
                                <i class="ti fs-20 me-2" :class="controlesMois.pret ? 'ti-circle-check text-success' : 'ti-alert-circle text-danger'"></i>
                                <span class="fw-bold">@{{ controlesMois.periode }}</span>
                            </div>
                            <p class="mb-2 fs-13 text-dark">@{{ controlesMois.ecritures_validees }} écritures validées détectées.</p>

                            <ul v-if="controlesMois.erreurs.length" class="mb-0 ps-3 fs-12 text-danger fw-medium">
                                <li v-for="(e,i) in controlesMois.erreurs" :key="i" class="mb-1">@{{ e }}</li>
                            </ul>
                            <p v-else-if="controlesMois.message" class="mb-0 fs-12 text-success fw-bold">@{{ controlesMois.message }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Clôture Annuelle -->
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-primary"><i class="ti ti-archive me-2"></i>Clôture annuelle définitive</h5>
                        <select v-model="exerciceId" class="form-select form-select-sm w-auto border-2">
                            <option v-for="ex in exercicesActifs" :key="ex.id" :value="ex.id">@{{ ex.libelle }}</option>
                        </select>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert bg-label-info border-1 border-info mb-4">
                            <div class="d-flex">
                                <i class="ti ti-info-circle fs-24 me-2"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Information importante</h6>
                                    <p class="mb-0 small">La clôture annuelle est irréversible. Elle génère automatiquement l'écriture de solde des comptes de gestion (CL) et bascule le résultat en instance d'affectation.</p>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <button type="button" class="btn btn-outline-secondary px-3" :disabled="isLoading" @click="lancerControles">
                                <i class="ti ti-clipboard-check me-1"></i>Vérifications pré-clôture
                            </button>
                            <button type="button" class="btn btn-outline-warning px-3" :disabled="isLoading || !controles?.pret" @click="preCloture">
                                <i class="ti ti-player-pause me-1"></i>Passer en Pré-clôture
                            </button>
                            <button type="button" class="btn btn-danger px-4 shadow-sm" :disabled="isLoading" @click="cloturer">
                                <i class="ti ti-lock-square-rounded me-1"></i>Exécuter la clôture finale
                            </button>
                        </div>

                        <div v-if="controles" class="bg-light p-4 rounded-4 border mb-4">
                            <div class="row g-4">
                                <div class="col-sm-4 text-center border-end">
                                    <label class="text-muted small text-uppercase d-block mb-1">Résultat estimé</label>
                                    <h4 class="fw-bold mb-0" :class="controles.resultat_net >= 0 ? 'text-success' : 'text-danger'">@{{ fmt(controles.resultat_net) }}</h4>
                                </div>
                                <div class="col-sm-4 text-center border-end">
                                    <label class="text-muted small text-uppercase d-block mb-1">Équilibre Bilan</label>
                                    <span v-if="controles.bilan_equilibre" class="badge bg-soft-success text-success px-3 py-2 mt-1">ÉQUILIBRÉ</span>
                                    <span v-else class="badge bg-soft-danger text-danger px-3 py-2 mt-1">DÉSÉQUILIBRÉ</span>
                                </div>
                                <div class="col-sm-4 text-center">
                                    <label class="text-muted small text-uppercase d-block mb-1">Brouillons restants</label>
                                    <h4 class="fw-bold mb-0" :class="controles.brouillons > 0 ? 'text-warning' : 'text-success'">@{{ controles.brouillons }}</h4>
                                </div>
                            </div>

                            <div v-if="controles.erreurs.length" class="mt-4 p-3 bg-white rounded-3 border border-danger">
                                <h6 class="text-danger fw-bold mb-2 small text-uppercase">Erreurs bloquantes :</h6>
                                <ul class="mb-0 ps-3 fs-13 text-danger">
                                    <li v-for="(e,i) in controles.erreurs" :key="i">@{{ e }}</li>
                                </ul>
                            </div>

                            <div v-if="controles.avertissements.length" class="mt-3 p-3 bg-white rounded-3 border border-warning">
                                <h6 class="text-warning fw-bold mb-2 small text-uppercase">Alertes d'attention :</h6>
                                <ul class="mb-0 ps-3 fs-13 text-warning">
                                    <li v-for="(a,i) in controles.avertissements" :key="i">@{{ a }}</li>
                                </ul>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-bold small">Notes de clôture (justification, PV d'assemblée...)</label>
                            <textarea v-model="notes" class="form-control border-2" rows="3" placeholder="Saisir un commentaire pour l'archive..."></textarea>
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
    .bg-soft-success { background-color: rgba(3, 201, 90, 0.1); }
    .bg-soft-danger { background-color: rgba(231, 13, 13, 0.1); }
    .bg-soft-warning { background-color: rgba(255, 171, 0, 0.1); }
    .bg-label-info { background-color: #e0f7fa; color: #00acc1; }
    .rounded-4 { border-radius: 1rem !important; }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/exercices/cloture.js') }}"></script>
@endpush
