@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <div>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    <div class="card border-0 rounded-0 mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Recherche n°, tiers…" v-model="search" @input="debounceLoad">
                </div>
                <div class="col-md-2">
                    <select class="form-select" v-model="filtreDevise" @change="loadList">
                        <option value="">Toutes devises</option>
                        <option value="CDF">CDF</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" v-model="filtreStatut" @change="loadList">
                        <option value="">Tous statuts</option>
                        <option value="brouillon">Brouillon</option>
                        <option value="validee">Validée</option>
                        <option value="payee">Payée</option>
                        <option value="annulee">Annulée</option>
                    </select>
                </div>
                <div class="col-auto ms-auto">
                    <a :href="createUrl" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Nouvelle</a>
                </div>
            </div>
        </div>
    </div>
    <div class="card border-0 rounded-0">
        <div class="card-body p-0">
            <table class="table table-nowrap mb-0">
                <thead class="table-light">
                    <tr>
                        <th>N°</th><th>Date</th><th>Tiers</th><th class="text-end">TTC</th><th>Statut</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="isLoading"><td colspan="6" class="text-center py-4">Chargement…</td></tr>
                    <tr v-else-if="!factures.length"><td colspan="6" class="text-center py-4 text-muted">Aucune facture</td></tr>
                    <tr v-for="f in factures" :key="f.id">
                        <td><span class="fw-medium">@{{ f.numero }}</span></td>
                        <td>@{{ fmtDate(f.date_facture) }}</td>
                        <td>@{{ f.tiers?.nom }}</td>
                        <td class="text-end">@{{ fmt(f.montant_ttc) }} @{{ f.devise }}</td>
                        <td><span class="badge" :class="badgeStatut(f.statut)">@{{ f.statut }}</span></td>
                        <td class="text-end">
                            <a :href="editUrl(f.id)" class="btn btn-sm btn-outline-light"><i class="ti ti-edit"></i></a>
                            <a :href="pdfUrl(f.id)" class="btn btn-sm btn-outline-primary" target="_blank"><i class="ti ti-file-type-pdf"></i></a>
                            <button v-if="f.statut==='brouillon'" type="button" class="btn btn-sm btn-outline-success" @click="valider(f)"><i class="ti ti-check"></i></button>
                            <button v-if="f.statut==='validee'" type="button" class="btn btn-sm btn-outline-info" @click="preparePaiement(f)">
                                <i class="ti ti-cash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </template>

    <div class="modal fade" id="modal_paiement" tabindex="-1" aria-labelledby="modal_paiement_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal_paiement_label">Enregistrer un paiement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p v-if="paiementForm.facture" class="mb-3">
                        Facture <strong>@{{ paiementForm.facture.numero }}</strong> —
                        solde TTC <strong>@{{ fmt(paiementForm.montant) }} @{{ paiementForm.devise }}</strong>
                    </p>
                    <div v-if="error && error.length" class="alert alert-danger py-2">
                        <div v-for="(e, i) in error" :key="i">@{{ e }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Méthode de paiement</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="meth_banque" value="banque" v-model="paiementForm.methode" @change="loadComptesTreso">
                                <label class="form-check-label" for="meth_banque">Banque (521)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="meth_caisse" value="caisse" v-model="paiementForm.methode" @change="loadComptesTreso">
                                <label class="form-check-label" for="meth_caisse">Caisse (571)</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Compte de trésorerie</label>
                        <select class="form-select" v-model="paiementForm.compte_tresorerie" :disabled="!comptesTreso.length">
                            <option value="">— Sélectionner un compte —</option>
                            <option v-for="c in comptesTreso" :key="c.num_compte" :value="c.num_compte">
                                @{{ c.num_compte }} — @{{ c.libelle }}
                            </option>
                        </select>
                        <small v-if="!comptesTreso.length" class="text-muted">Aucun compte classe 5 trouvé pour cette méthode.</small>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Montant</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" v-model.number="paiementForm.montant">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" v-model="paiementForm.date_paiement">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Notes (optionnel)</label>
                        <textarea class="form-control" rows="2" v-model="paiementForm.notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" @click="confirmerPaiement" :disabled="isLoading || !paiementForm.compte_tresorerie">
                        <span v-if="isLoading">Traitement…</span>
                        <span v-else>Valider le paiement</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
@endsection
@push('scripts')
<script>window.__FACTURATION_PAGE__ = @json($page); window.__FACTURATION_TYPE__ = @json($type);</script>
<script type="module" src="{{ asset('assets/js/scripts/facturation/factures-liste.js') }}"></script>
@endpush
