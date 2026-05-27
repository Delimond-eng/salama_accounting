@extends('layouts.app')

@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('parametres._nav', ['active' => 'devises', 'title' => 'Devises & Taux de change', 'breadcrumb' => 'Devises'])

<<<<<<< HEAD
    <div class="row g-4">
=======
    <div class="alert alert-info border-0 rounded-0 mb-3 py-2 fs-13">
        <i class="ti ti-info-circle me-1"></i>
        <strong>Devise principale de la société :</strong> @{{ devisePrincipale }}.
        Saisissez le nombre de @{{ devisePrincipale }} pour <strong>1 unité</strong> de devise étrangère
        (exemple : <strong>1 USD = 2&nbsp;200 @{{ devisePrincipale }}</strong> → saisir <strong>2200</strong>).
    </div>

    <div class="row g-3">
>>>>>>> 356d4919f7208489f8fadf9a5b1244abeb82c9b0
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="ti ti-currency me-2"></i>Référentiel Devises</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-custom mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 80px">Code</th>
                                    <th>Libellé de la devise</th>
                                    <th class="text-center" style="width: 80px">Symbole</th>
                                </tr>
                            </thead>
                            <tbody>
<<<<<<< HEAD
                                <tr v-for="d in devises" :key="d.code_iso">
                                    <td class="text-center"><span class="badge bg-label-primary px-3 font-monospace fw-bold">@{{ d.code_iso }}</span></td>
                                    <td class="fw-medium">@{{ d.libelle }}</td>
                                    <td class="text-center text-muted fw-bold">@{{ d.symbole || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 bg-light-soft">
                        <p class="mb-0 small text-muted italic"><i class="ti ti-info-circle me-1"></i>La devise principale est définie dans les paramètres de la société.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h5 class="mb-0 fw-bold text-primary">Historique des Taux</h5>
                        <p class="mb-0 text-muted small">Suivi des cours de change par rapport à la devise pivot.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm px-3" @click="openTauxForm()">
                        <i class="ti ti-plus me-1"></i>Nouveau taux
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Date d'application</th>
                                    <th>Devise</th>
                                    <th class="text-end">Taux Moyen</th>
                                    <th class="text-end">Cours Achat</th>
                                    <th class="text-end">Cours Vente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!taux.length && !isLoading"><td colspan="5" class="text-center py-5 text-muted">Aucun historique de taux enregistré</td></tr>
                                <tr v-if="isLoading"><td colspan="5" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement…</td></tr>
                                <tr v-for="t in taux" :key="t.id">
                                    <td class="fw-medium">@{{ t.date_taux }}</td>
                                    <td><span class="badge bg-label-secondary font-monospace">@{{ t.devise_code }}</span></td>
                                    <td class="text-end fw-bold text-primary">@{{ t.taux }}</td>
                                    <td class="text-end text-muted">@{{ t.taux_achat || '—' }}</td>
                                    <td class="text-end text-muted">@{{ t.taux_vente || '—' }}</td>
=======
                                <tr v-if="!devises.length"><td colspan="3" class="text-center py-4 text-muted">Aucune devise</td></tr>
                                <tr v-for="d in devises" :key="d.code_iso" :class="d.code_iso === devisePrincipale ? 'table-primary' : ''">
                                    <td>
                                        <span class="badge" :class="d.code_iso === devisePrincipale ? 'badge-soft-primary' : 'badge-soft-secondary'">@{{ d.code_iso }}</span>
                                        <span v-if="d.code_iso === devisePrincipale" class="fs-11 text-primary ms-1">(principale)</span>
                                    </td>
                                    <td>@{{ d.libelle }}</td>
                                    <td>@{{ d.symbole || '—' }}</td>
>>>>>>> 356d4919f7208489f8fadf9a5b1244abeb82c9b0
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
<<<<<<< HEAD
=======
        <div class="col-lg-7">
            <div class="card border-0 rounded-0">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 fs-16">Historique des taux</h5>
                    <button type="button" class="btn btn-primary btn-sm" @click="openTauxForm()" :disabled="!devisesEtrangeres.length">
                        <i class="ti ti-plus me-1"></i>Nouveau taux
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Devise</th>
                                <th>Équivalence (taux moyen)</th>
                                <th>Achat</th>
                                <th>Vente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading"><td colspan="5" class="text-center py-4">Chargement…</td></tr>
                            <tr v-else-if="!taux.length"><td colspan="5" class="text-center py-4 text-muted">Aucun taux enregistré</td></tr>
                            <tr v-for="t in taux" :key="t.id">
                                <td>@{{ fmtDate(t.date_taux) }}</td>
                                <td><span class="badge badge-soft-primary">@{{ t.devise_code }}</span></td>
                                <td class="fw-medium">@{{ equivLabel(t.devise_code, t.taux) || '—' }}</td>
                                <td class="text-muted fs-13">@{{ equivLabel(t.devise_code, t.taux_achat, 'achat') || '—' }}</td>
                                <td class="text-muted fs-13">@{{ equivLabel(t.devise_code, t.taux_vente, 'vente') || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
>>>>>>> 356d4919f7208489f8fadf9a5b1244abeb82c9b0
    </div>

    <!-- Modal Taux -->
    <div class="modal fade" id="modal_taux" tabindex="-1">
<<<<<<< HEAD
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary py-3">
                    <h5 class="modal-title text-white fw-bold">Enregistrement d'un taux</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="saveTaux">
                    <div class="modal-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Devise étrangère <span class="text-danger">*</span></label>
                                <select class="form-select border-2" v-model="formTaux.devise_code" required>
                                    <option v-for="d in devises" :key="d.code_iso" :value="d.code_iso">@{{ d.code_iso }} — @{{ d.libelle }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Date de valeur <span class="text-danger">*</span></label>
                                <input type="date" class="form-control border-2" v-model="formTaux.date_taux" required>
                            </div>
                            <div class="col-12"><hr class="my-0"></div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Taux Moyen <span class="text-danger">*</span></label>
                                <input type="number" step="0.000001" class="form-control border-2" v-model.number="formTaux.taux" required placeholder="0.000000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Taux Achat</label>
                                <input type="number" step="0.000001" class="form-control border-2" v-model.number="formTaux.taux_achat" placeholder="Optionnel">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Taux Vente</label>
                                <input type="number" step="0.000001" class="form-control border-2" v-model.number="formTaux.taux_vente" placeholder="Optionnel">
=======
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form @submit.prevent="saveTaux">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau taux de change</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted fs-13 mb-3">
                            Indiquez combien vaut <strong>1 unité</strong> de la devise choisie en <strong>@{{ devisePrincipale }}</strong>.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Devise étrangère</label>
                                <select class="form-select" v-model="formTaux.devise_code" required>
                                    <option v-for="d in devisesEtrangeres" :key="d.code_iso" :value="d.code_iso">@{{ d.code_iso }} — @{{ d.libelle }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date du taux</label>
                                <input type="date" class="form-control" v-model="formTaux.date_taux" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">
                                    Montant en @{{ devisePrincipale }} pour <strong>1 @{{ formTaux.devise_code || '…' }}</strong>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">1 @{{ formTaux.devise_code }} =</span>
                                    <input type="number" step="0.01" min="0.000001" class="form-control" v-model.number="formTaux.taux" placeholder="Ex. 2200" required>
                                    <span class="input-group-text">@{{ devisePrincipale }}</span>
                                </div>
                                <div v-if="formEquivLabel" class="form-text text-primary fw-medium mt-1">@{{ formEquivLabel }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Taux achat (optionnel)</label>
                                <input type="number" step="0.01" min="0" class="form-control" v-model.number="formTaux.taux_achat" placeholder="Taux banque à l'achat">
                                <div v-if="formEquivAchat" class="form-text">@{{ formEquivAchat }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Taux vente (optionnel)</label>
                                <input type="number" step="0.01" min="0" class="form-control" v-model.number="formTaux.taux_vente" placeholder="Taux banque à la vente">
                                <div v-if="formEquivVente" class="form-text">@{{ formEquivVente }}</div>
>>>>>>> 356d4919f7208489f8fadf9a5b1244abeb82c9b0
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top-0 p-3">
                        <button type="button" class="btn btn-white px-4 border" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary px-4" :disabled="isLoading">
                            <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                            Valider le taux
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
    .bg-label-secondary { background-color: #f1f3f4; color: #5f6368; }
    .bg-light-soft { background-color: #f8fafc; }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/parametres/devises.js') }}"></script>
@endpush
