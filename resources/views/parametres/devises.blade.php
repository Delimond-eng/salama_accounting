@extends('layouts.app')

@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('parametres._nav', ['active' => 'devises', 'title' => 'Devises & taux', 'breadcrumb' => 'Devises'])

    <div class="alert alert-info border-0 rounded-0 mb-3 py-2 fs-13">
        <i class="ti ti-info-circle me-1"></i>
        <strong>Devise principale de la société :</strong> @{{ devisePrincipale }}.
        Saisissez le nombre de @{{ devisePrincipale }} pour <strong>1 unité</strong> de devise étrangère
        (exemple : <strong>1 USD = 2&nbsp;200 @{{ devisePrincipale }}</strong> → saisir <strong>2200</strong>).
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 rounded-0 h-100">
                <div class="card-header"><h5 class="mb-0 fs-16">Référentiel devises</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-nowrap mb-0">
                            <thead class="table-light"><tr><th>Code</th><th>Libellé</th><th>Symbole</th></tr></thead>
                            <tbody>
                                <tr v-if="!devises.length"><td colspan="3" class="text-center py-4 text-muted">Aucune devise</td></tr>
                                <tr v-for="d in devises" :key="d.code_iso" :class="d.code_iso === devisePrincipale ? 'table-primary' : ''">
                                    <td>
                                        <span class="badge" :class="d.code_iso === devisePrincipale ? 'badge-soft-primary' : 'badge-soft-secondary'">@{{ d.code_iso }}</span>
                                        <span v-if="d.code_iso === devisePrincipale" class="fs-11 text-primary ms-1">(principale)</span>
                                    </td>
                                    <td>@{{ d.libelle }}</td>
                                    <td>@{{ d.symbole || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
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
    </div>

    <div class="modal fade" id="modal_taux" tabindex="-1">
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
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" :disabled="isLoading">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/parametres/devises.js') }}"></script>
@endpush
