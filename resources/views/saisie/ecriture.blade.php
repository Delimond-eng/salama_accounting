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

    <div v-if="estValidee" class="alert alert-info d-flex align-items-center justify-content-between">
        <span><i class="ti ti-lock me-2"></i>Cette écriture est <strong>validée</strong> — consultation seule.</span>
        <button v-if="canUnvalidate" type="button" class="btn btn-sm btn-warning" @click="ouvrirRebrouillon(ecritureCourante)">
            <i class="ti ti-arrow-back-up me-1"></i>Remettre en brouillon
        </button>
    </div>

    <form @submit.prevent="save(false)">
        <div class="card border-0 rounded-0 mb-3">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fw-bold text-uppercase fs-14"><i class="ti {{ $icon ?? 'ti-edit' }} me-2 text-primary"></i>{{ $title }} — En-tête</h5>
                <a :href="listeUrl" class="btn btn-sm btn-label-secondary"><i class="ti ti-arrow-left me-1"></i>Retour</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Journal</label>
                        <select class="form-select" v-select2 v-model.number="entete.journal_id" required :disabled="journalVerrouille || estValidee" @change="appliquerDeviseJournal" placeholder="Choisir un journal">
                            <option></option>
                            <option v-for="j in journaux" :key="j.id" :value="j.id">@{{ j.code }} — @{{ j.libelle }}@{{ (j.devise_defaut && j.devise_defaut !== devisePrincipale) ? ' (' + j.devise_defaut + ')' : '' }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date comptable</label>
                        <input type="date" class="form-control" v-model="entete.date_ecriture" required :readonly="estValidee">
                    </div>
                    <div class="col-md-2" v-if="multiDevise">
                        <label class="form-label">Devise</label>
                        <select v-if="!deviseVerrouillee" class="form-select" v-model="entete.devise" @change="fetchTaux">
                            <option v-for="d in devises" :key="d.code_iso" :value="d.code_iso">@{{ d.code_iso }} — @{{ d.libelle }}</option>
                        </select>
                        <input v-else type="text" class="form-control bg-light" v-model="entete.devise" readonly>
                    </div>
                    <div class="col-md-2" v-if="multiDevise && entete.devise !== devisePrincipale">
                        <label class="form-label">Taux (1 @{{ entete.devise }} = X @{{ devisePrincipale }})</label>
                        <input type="number" step="0.000001" class="form-control" v-model.number="entete.taux_change">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Libellé</label>
                        <input type="text" class="form-control" v-model="entete.libelle" required :readonly="estValidee">
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

        <div class="card border-0 rounded-0 mb-3" style="z-index: 10;">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <div>
                    <h5 class="mb-0 fw-bold text-uppercase fs-14">Lignes d'écriture <span class="badge bg-label-secondary ms-1">@{{ lignes.length }}</span></h5>
                    <small class="text-muted">
                        <span class="fw-bold text-primary">Guide de saisie :</span>
                        <span class="text-info">①</span> Sélectionnez le compte
                        <i class="ti ti-chevron-right fs-10 mx-1"></i> <span class="text-info">②</span> tiers (si applicable)
                        <i class="ti ti-chevron-right fs-10 mx-1"></i> <span class="text-info">③</span> saisir libellé
                        <i class="ti ti-chevron-right fs-10 mx-1"></i> <span class="text-info">④</span> analytique (obligatoire pour classes 6 & 7)
                        <i class="ti ti-chevron-right fs-10 mx-1"></i> <span class="text-info">⑤</span> montant débit ou crédit.
                    </small>
                </div>
                <div class="d-flex gap-2" v-if="!estValidee">
                    <button type="button" class="btn btn-xs btn-outline-secondary" @click="appliquerTemplate"><i class="ti ti-template me-1"></i>Modèle</button>
                    <button type="button" class="btn btn-xs btn-primary" @click="ajouterLigne"><i class="ti ti-plus me-1"></i>Ligne</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="overflow: visible !important;">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:12%">Compte <button v-if="!estValidee" type="button" class="btn btn-xs btn-primary ms-2" @click="openQuickCompte()"><i class="ti ti-plus"></i></button></th>
                                <th style="width:18%">Tiers</th>
                                <th>Libellé</th>
                                <th v-if="showColonneAnalytique" style="width:16%">Analytique <button v-if="!estValidee" type="button" class="btn btn-xs btn-primary ms-2" @click="openQuickAnalytique()"><i class="ti ti-plus"></i></button></th>
                                <th class="text-end" style="width:12%">Débit</th>
                                <th class="text-end" style="width:12%">Crédit</th>
                                <th v-if="multiDevise && !journalDeviseEtrangere" class="text-end" style="width:10%">M. devise</th>
                                <th style="width:4%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(l, idx) in lignes" :key="l.id_vue">
                                <td style="overflow: visible !important;">
                                    <div class="compte-select-wrap position-relative">
                                        <input type="text" class="form-control form-control-sm"
                                            :value="compteDisplayText(idx)"
                                            @input="onCompteSearchInput(idx, $event)"
                                            @focus="onCompteSearchFocus(idx)"
                                            @blur="onCompteSearchBlur(idx)"
                                            placeholder="Rechercher..."
                                            autocomplete="off"
                                            :readonly="estValidee">
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
                                    <select class="form-select form-select-sm" v-select2 v-model.number="l.tiers_id" placeholder="Choisir un tiers" :disabled="estValidee">
                                        <option></option>
                                        <option v-for="t in tiersOptions" :key="t.id" :value="t.id">@{{ t.code }} @{{ t.nom }}</option>
                                    </select>
                                </td>
                                <td><input type="text" class="form-control form-control-sm" v-model="l.libelle" :readonly="estValidee"></td>
                                <td v-if="showColonneAnalytique">
                                    <select v-if="sectionsListe.length" class="form-select form-select-sm"
                                        v-select2
                                        v-model.number="l.section_analytique_id"
                                        @change="onSectionSelectChange(idx)"
                                        placeholder="Choisir l'analytique"
                                        :disabled="estValidee || !isAnalytiqueEligible(l.num_compte)"
                                        :class="{'border-danger': analytiqueObligatoireJournal && !l.section_analytique_id && isAnalytiqueEligible(l.num_compte)}">
                                        <option></option>
                                        <optgroup v-for="axe in axesAnalytiques" :key="axe.id" :label="axe.code + ' — ' + axe.libelle">
                                            <option v-for="s in (axe.sections || [])" :key="s.id" :value="s.id">
                                                @{{ s.code }} — @{{ s.libelle }}
                                            </option>
                                        </optgroup>
                                    </select>
                                    <div v-else class="position-relative">
                                        <input type="text" class="form-control form-control-sm"
                                            :value="sectionDisplayText(idx)"
                                            @input="onSectionSearchInput(idx, $event)"
                                            @focus="onSectionSearchFocus(idx)"
                                            @blur="onSectionSearchBlur(idx)"
                                            placeholder="Rechercher un analytique…"
                                            autocomplete="off"
                                            :disabled="estValidee || !isAnalytiqueEligible(l.num_compte)">
                                        <ul v-show="sectionUiOpen(idx)" class="dropdown-menu show w-100 shadow-sm" style="max-height:180px;overflow:auto">
                                            <li v-if="sectionUiLoading(idx)"><span class="dropdown-item text-muted">Recherche…</span></li>
                                            <li v-else-if="!sectionUiResults(idx).length"><span class="dropdown-item text-muted">Aucun résultat</span></li>
                                            <li v-for="s in sectionUiResults(idx)" :key="s.id">
                                                <a href="javascript:void(0)" class="dropdown-item py-1 fs-12" @mousedown.prevent="selectSectionOption(idx, s)">
                                                    <span class="badge badge-soft-info me-1">@{{ s.axe?.code }}</span>
                                                    @{{ s.code }} — @{{ s.libelle }}
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                                <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" v-model.number="l.debit" @input="onMontant(l,'debit')" :readonly="estValidee"></td>
                                <td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" v-model.number="l.credit" @input="onMontant(l,'credit')" :readonly="estValidee"></td>
                                <td v-if="multiDevise && !journalDeviseEtrangere"><input type="number" step="0.01" class="form-control form-control-sm text-end" v-model.number="l.montant_devise" :readonly="estValidee"></td>
                                <td class="text-center" v-if="!estValidee">
                                    <button type="button" class="btn btn-sm text-danger" @click="supprimerLigne(idx)" :disabled="lignes.length<=2"><i class="ti ti-trash"></i></button>
                                </td>
                                <td v-else class="text-center text-muted">—</td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light text-dark fw-bold">
                            <tr>
                                <td :colspan="showColonneAnalytique ? 4 : 3" class="text-end">TOTAUX</td>
                                <td class="text-end">@{{ formatMontantDevise(totalDebit, entete.devise) }}</td>
                                <td class="text-end">@{{ formatMontantDevise(totalCredit, entete.devise) }}</td>
                                <td :colspan="(multiDevise && !journalDeviseEtrangere) ? 2 : 1">
                                    <span v-if="!equilibre" class="badge bg-danger">ÉCART @{{ formatMontantDevise(ecart, entete.devise) }}</span>
                                    <span v-else class="text-muted fs-12">ÉQUILIBRÉE</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end mb-4">
            <a :href="listeUrl" class="btn btn-white border">@{{ estValidee ? 'Retour' : 'Annuler' }}</a>
            <template v-if="!estValidee">
                <button type="submit" class="btn btn-outline-primary" :disabled="isLoading || !equilibre">Enregistrer brouillon</button>
                <button type="button" class="btn btn-primary" :disabled="isLoading || !equilibre" @click="save(true)">Valider l'écriture</button>
            </template>
        </div>
    </form>

    <!-- Modal Création Rapide Compte + Tiers -->
    <div class="modal fade" id="modal_quick_compte" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary py-3">
                    <h5 class="modal-title text-white fw-bold">Création rapide de compte</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="saveQuickCompte">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Numéro de compte *</label>
                                <input type="text" class="form-control" v-model="formCompte.num_compte" required placeholder="ex: 411101">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Classe *</label>
                                <select class="form-select" v-model.number="formCompte.classe" required>
                                    <option v-for="n in 9" :key="n" :value="n">Classe @{{ n }}</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small">Intitulé du compte *</label>
                                <input type="text" class="form-control" v-model="formCompte.libelle" required placeholder="Libellé complet">
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" v-model="formCompte.est_compte_tiers" id="q_chk_tiers">
                                    <label class="form-check-label fw-bold" for="q_chk_tiers">Ce compte nécessite un suivi tiers</label>
                                </div>
                            </div>
                            <template v-if="formCompte.est_compte_tiers">
                                <div class="col-12"><hr class="my-2"></div>
                                <div class="col-12"><h6 class="text-primary fw-bold mb-2">Informations du Tiers</h6></div>
                                <div class="col-md-5">
                                    <label class="form-label fw-bold small">Code Tiers *</label>
                                    <input type="text" class="form-control text-uppercase" v-model="formTiers.code" :required="formCompte.est_compte_tiers">
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label fw-bold small">Type de tiers *</label>
                                    <select class="form-select" v-model="formTiers.type" :required="formCompte.est_compte_tiers">
                                        <option value="client">Client</option>
                                        <option value="fournisseur">Fournisseur</option>
                                        <option value="salarie">Salarié</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small">Nom du tiers (si différent du compte)</label>
                                    <input type="text" class="form-control" v-model="formTiers.nom">
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal" :disabled="isLoading">Annuler</button>
                        <button type="submit" class="btn btn-primary px-4" :disabled="isLoading">
                            <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Création Rapide Analytique -->
    <div class="modal fade" id="modal_quick_analytique" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark py-3">
                    <h5 class="modal-title text-white fw-bold">Nouveau compte analytique</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="saveQuickAnalytique">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold small">Axe analytique *</label>
                                <select class="form-select" v-model="formAnalytique.axe_analytique_id" required>
                                    <option v-for="axe in axesAnalytiques" :key="axe.id" :value="axe.id">@{{ axe.code }} — @{{ axe.libelle }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Code *</label>
                                <input type="text" class="form-control" v-model="formAnalytique.code" required placeholder="ex: PRJ01">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold small">Libellé du compte *</label>
                                <input type="text" class="form-control" v-model="formAnalytique.libelle" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal" :disabled="isLoading">Annuler</button>
                        <button type="submit" class="btn btn-primary px-4" :disabled="isLoading">
                            <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                            Créer le compte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    </template>

    @include('saisie._modal_rebrouillon')
</div>

<style>
    .btn-xs { padding: 0.2rem 0.4rem; font-size: 0.75rem; }
    .btn-label-secondary { background: #f1f3f4; color: #5f6368; border: none; }
    .btn-label-secondary:hover { background: #e8eaed; color: #3c4043; }

    /* Correction robuste pour Select2 */
    .select2-container--default .select2-selection--single {
        border: 1px solid #dbdade !important;
        height: 31px !important;
        display: flex !important;
        align-items: center !important;
        background-color: #fff !important;
        font-size: 0.8125rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 31px !important;
        padding-left: 10px !important;
        padding-right: 25px !important;
        color: #333 !important;
        width: 100% !important;
        text-align: left !important;
    }

    /* Z-index global pour les dropdowns */
    .select2-container--open { z-index: 10001 !important; }

    /* Autocomplete personnalisé (Comptes) */
    .compte-select-dropdown {
        position: absolute !important;
        z-index: 10000 !important;
        background: #fff;
        border: 1px solid #dbdade;
        border-radius: 0.375rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        max-height: 300px;
        overflow-y: auto;
        width: 400px !important; /* Plus large pour la lisibilité */
    }

    .compte-select-dropdown .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    /* Style spécifique pour analytique disabled */
    .select2-container--default.select2-container--disabled .select2-selection--single {
        background-color: #f8f9fa !important;
        cursor: not-allowed;
    }
</style>
@endsection
@push('scripts')
<script>
    window.__SAISIE_PAGE__ = @json($page);
    window.__ECRITURE_ID__ = @json($ecritureId);
    window.__DUPLICATE_ID__ = @json($duplicateId);
    window.__SAISIE_PERMISSIONS__ = @json(['unvalidate' => auth()->user()?->can('saisie.unvalidate') ?? false]);
</script>
<script type="module" src="{{ asset('assets/js/scripts/saisie/ecriture.js') }}"></script>
@endpush
