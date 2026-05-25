@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('saisie._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])

    <div v-if="warnings.length" class="alert alert-warning alert-dismissible fade show">
        <strong>Avertissements (écriture enregistrée)</strong>
        <ul class="mb-0 mt-1">
            <li v-for="(w, i) in warnings" :key="i">@{{ w }}</li>
        </ul>
        <button type="button" class="btn-close" @click="warnings=[]"></button>
    </div>

    <form @submit.prevent="save(false)">
        <div class="card border-0 rounded-0 mb-3">
            <div class="card-header"><h5 class="mb-0 fs-16">En-tête pièce</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Journal</label>
                        <select class="form-select" v-model.number="entete.journal_id" required :disabled="journalVerrouille">
                            <option v-for="j in journaux" :key="j.id" :value="j.id">@{{ j.code }} — @{{ j.libelle }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date comptable</label>
                        <input type="date" class="form-control" v-model="entete.date_ecriture" required>
                    </div>
                    <div class="col-md-2" v-if="multiDevise">
                        <label class="form-label">Devise</label>
                        <input type="text" class="form-control" v-model="entete.devise" maxlength="3" @change="fetchTaux">
                    </div>
                    <div class="col-md-2" v-if="multiDevise">
                        <label class="form-label">Taux</label>
                        <input type="number" step="0.000001" class="form-control" v-model.number="entete.taux_change">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Libellé</label>
                        <input type="text" class="form-control" v-model="entete.libelle" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Réf. facture</label>
                        <input type="text" class="form-control" v-model="entete.reference_facture">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Réf. externe</label>
                        <input type="text" class="form-control" v-model="entete.reference_externe">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date échéance</label>
                        <input type="date" class="form-control" v-model="entete.date_echeance">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 rounded-0 mb-3">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0 fs-16">Lignes d'écriture <span class="badge badge-soft-secondary ms-1">@{{ lignes.length }}</span></h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-light me-1" @click="appliquerTemplate"><i class="ti ti-template me-1"></i>Modèle SYSCOHADA</button>
                    <button type="button" class="btn btn-sm btn-primary" @click="ajouterLigne"><i class="ti ti-plus me-1"></i>Ligne</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:12%">Compte</th>
                                <th style="width:18%">Tiers</th>
                                <th>Libellé</th>
                                <th class="text-end" style="width:12%">Débit</th>
                                <th class="text-end" style="width:12%">Crédit</th>
                                <th v-if="multiDevise" class="text-end" style="width:10%">M. devise</th>
                                <th style="width:4%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(l, idx) in lignes" :key="idx">
                                <td>
                                    <div class="compte-select-wrap position-relative">
                                        <input type="text" class="form-control form-control-sm"
                                            :value="compteDisplayText(idx)"
                                            @input="onCompteSearchInput(idx, $event)"
                                            @focus="onCompteSearchFocus(idx)"
                                            @blur="onCompteSearchBlur(idx)"
                                            placeholder="Rechercher..."
                                            autocomplete="off">
                                        <ul v-show="compteUiOpen(idx)" class="dropdown-menu show w-100 shadow-sm compte-select-dropdown">
                                            <li v-if="compteUiLoading(idx)"><span class="dropdown-item text-muted">Recherche…</span></li>
                                            <li v-else-if="!compteUiResults(idx).length"><span class="dropdown-item text-muted">Aucun compte</span></li>
                                            <li v-for="c in compteUiResults(idx)" :key="c.id">
                                                <a href="javascript:void(0)" class="dropdown-item py-2" @mousedown.prevent="selectCompteOption(idx, c)">
                                                    <span class="fw-medium text-primary">@{{ c.num_compte }}</span>
                                                    <span class="d-block text-muted fs-12 text-truncate">@{{ c.libelle }}</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" v-model.number="l.tiers_id">
                                        <option :value="null">—</option>
                                        <option v-for="t in tiersOptions" :key="t.id" :value="t.id">@{{ t.code }} @{{ t.nom }}</option>
                                    </select>
                                </td>
                                <td><input type="text" class="form-control form-control-sm" v-model="l.libelle"></td>
                                <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" v-model.number="l.debit" @input="onMontant(l,'debit')"></td>
                                <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" v-model.number="l.credit" @input="onMontant(l,'credit')"></td>
                                <td v-if="multiDevise"><input type="number" step="0.01" class="form-control form-control-sm text-end" v-model.number="l.montant_devise"></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm text-danger" @click="supprimerLigne(idx)" :disabled="lignes.length<=2"><i class="ti ti-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-primary text-white fw-bold">
                            <tr>
                                <td colspan="3" class="text-end">TOTAUX</td>
                                <td class="text-end">@{{ formatMontantDevise(totalDebit, entete.devise) }}</td>
                                <td class="text-end">@{{ formatMontantDevise(totalCredit, entete.devise) }}</td>
                                <td :colspan="multiDevise ? 2 : 1">
                                    <span v-if="!equilibre" class="badge bg-danger">ÉCART @{{ formatMontantDevise(ecart, entete.devise) }}</span>
                                    <span v-else class="text-white-50 fs-12">ÉQUILIBRÉE</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end mb-4">
            <a :href="listeUrl" class="btn btn-white border">Annuler</a>
            <button type="submit" class="btn btn-outline-primary" :disabled="isLoading || !equilibre">Enregistrer brouillon</button>
            <button type="button" class="btn btn-primary" :disabled="isLoading || !equilibre" @click="save(true)">Valider l'écriture</button>
        </div>
    </form>
    </template>
</div>
@endsection
@push('scripts')
<script>
    window.__SAISIE_PAGE__ = @json($page);
    window.__ECRITURE_ID__ = @json($ecritureId);
</script>
<script type="module" src="{{ asset('assets/js/scripts/saisie/ecriture.js') }}"></script>
@endpush
