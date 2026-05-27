@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <div>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="search-box">
                        <div class="input-group input-group-sm border rounded-2 px-2 bg-light">
                            <span class="input-group-text bg-transparent border-0 p-0 me-2"><i class="ti ti-search text-muted"></i></span>
                            <input type="text" class="form-control bg-transparent border-0 ps-0" placeholder="Rechercher par n° de facture, tiers..." v-model="search" @input="debounceLoad">
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
<<<<<<< HEAD
                    <select class="form-select form-select-sm border-2" v-model="filtreDevise" @change="loadList">
=======
                    <select class="form-select" v-model="filtreDevise" @change="loadList">
>>>>>>> 356d4919f7208489f8fadf9a5b1244abeb82c9b0
                        <option value="">Toutes devises</option>
                        <option value="CDF">CDF</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
                <div class="col-md-2">
<<<<<<< HEAD
                    <select class="form-select form-select-sm border-2" v-model="filtreStatut" @change="loadList">
                        <option value="">Tous les statuts</option>
=======
                    <select class="form-select" v-model="filtreStatut" @change="loadList">
                        <option value="">Tous statuts</option>
>>>>>>> 356d4919f7208489f8fadf9a5b1244abeb82c9b0
                        <option value="brouillon">Brouillon</option>
                        <option value="validee">Validée</option>
                        <option value="payee">Payée</option>
                        <option value="annulee">Annulée</option>
                    </select>
                </div>
                <div class="col-auto ms-auto">
                    <a :href="createUrl" class="btn btn-primary btn-sm px-3">
                        <i class="ti ti-plus me-1"></i>Nouvelle Facture
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-primary">Registre des Factures</h5>
                <p class="mb-0 text-muted small">Consultation et gestion des pièces de facturation.</p>
            </div>
            <div class="text-end" v-if="factures.length">
                <span class="badge bg-soft-info text-info px-3 py-2">@{{ factures.length }} Factures</span>
            </div>
        </div>
        <div class="card-body p-0">
<<<<<<< HEAD
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 120px">Numéro</th>
                            <th style="width: 100px">Date</th>
                            <th>Tiers / Client / Fournisseur</th>
                            <th class="text-end" style="width: 150px">Montant TTC</th>
                            <th class="text-center" style="width: 100px">Statut</th>
                            <th class="text-end" style="width: 150px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="6" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement…</td></tr>
                        <tr v-else-if="!factures.length"><td colspan="6" class="text-center py-5 text-muted">Aucune facture trouvée</td></tr>
                        <tr v-for="f in factures" :key="f.id">
                            <td class="font-monospace fw-bold text-primary">@{{ f.numero }}</td>
                            <td class="text-muted">@{{ fmtDate(f.date_facture) }}</td>
                            <td class="fw-medium text-dark">@{{ f.tiers?.nom }}</td>
                            <td class="text-end fw-bold">@{{ fmt(f.montant_ttc) }} <small class="text-muted">@{{ f.devise }}</small></td>
                            <td class="text-center">
                                <span class="badge rounded-pill" :class="badgeStatut(f.statut)">@{{ f.statut }}</span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a :href="editUrl(f.id)" class="btn btn-icon btn-sm btn-label-primary" title="Modifier/Voir">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    <a :href="pdfUrl(f.id)" class="btn btn-icon btn-sm btn-label-danger" target="_blank" title="PDF">
                                        <i class="ti ti-file-type-pdf"></i>
                                    </a>
                                    <button v-if="f.statut==='brouillon'" type="button" class="btn btn-icon btn-sm btn-label-success" @click="valider(f)" title="Valider">
                                        <i class="ti ti-check"></i>
                                    </button>
                                    <button v-if="f.statut==='validee'" type="button" class="btn btn-icon btn-sm btn-label-info" @click="preparePaiement(f)" title="Enregistrer paiement">
                                        <i class="ti ti-cash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
=======
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
>>>>>>> 356d4919f7208489f8fadf9a5b1244abeb82c9b0
        </div>
    </div>
    </template>

    <div class="modal fade" id="modal_paiement" tabindex="-1" aria-labelledby="modal_paiement_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
<<<<<<< HEAD
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modal_paiement_label">Enregistrer un paiement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-4">
                    <div v-if="paiementForm.facture" class="alert alert-info border-0 mb-4">
                        Facture <strong class="text-primary">@{{ paiementForm.facture.numero }}</strong> —
                        solde TTC <strong class="text-primary">@{{ fmt(paiementForm.montant) }} @{{ paiementForm.devise }}</strong>
                    </div>
                    <div v-if="error && error.length" class="alert alert-danger py-2 border-0 mb-3">
                        <div v-for="(e, i) in error" :key="i"><i class="ti ti-alert-triangle me-2"></i>@{{ e }}</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Méthode de paiement</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="meth_banque" value="banque" v-model="paiementForm.methode" @change="loadComptesTreso">
                                <label class="form-check-label fw-medium" for="meth_banque">Banque (521)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="meth_caisse" value="caisse" v-model="paiementForm.methode" @change="loadComptesTreso">
                                <label class="form-check-label fw-medium" for="meth_caisse">Caisse (571)</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Compte de trésorerie</label>
                        <select class="form-select border-2" v-model="paiementForm.compte_tresorerie" :disabled="!comptesTreso.length">
=======
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
>>>>>>> 356d4919f7208489f8fadf9a5b1244abeb82c9b0
                            <option value="">— Sélectionner un compte —</option>
                            <option v-for="c in comptesTreso" :key="c.num_compte" :value="c.num_compte">
                                @{{ c.num_compte }} — @{{ c.libelle }}
                            </option>
                        </select>
<<<<<<< HEAD
                        <small v-if="!comptesTreso.length && paiementForm.methode" class="text-danger mt-1 d-block">
                            <i class="ti ti-alert-circle me-1"></i>Aucun compte classe 5 trouvé pour cette méthode.
                        </small>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase text-muted">Montant</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0.01" class="form-control border-2 fw-bold" v-model.number="paiementForm.montant">
                                <span class="input-group-text bg-light border-2 border-start-0">@{{ paiementForm.devise }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase text-muted">Date</label>
                            <input type="date" class="form-control border-2" v-model="paiementForm.date_paiement">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Notes (optionnel)</label>
                        <textarea class="form-control border-2" rows="2" v-model="paiementForm.notes" placeholder="Référence virement, n° chèque..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-white border px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary px-4 fw-bold" @click="confirmerPaiement" :disabled="isLoading || !paiementForm.compte_tresorerie">
                        <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                        <span v-else><i class="ti ti-check me-1"></i>Valider le paiement</span>
=======
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
>>>>>>> 356d4919f7208489f8fadf9a5b1244abeb82c9b0
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>
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
    .btn-label-primary { background: #e7e7ff; color: #696cff; border: none; }
    .btn-label-primary:hover { background: #696cff; color: #fff; }
    .btn-label-danger { background: #ffe5e5; color: #ff3e1d; border: none; }
    .btn-label-danger:hover { background: #ff3e1d; color: #fff; }
    .btn-label-success { background: #e8fadf; color: #71dd37; border: none; }
    .btn-label-success:hover { background: #71dd37; color: #fff; }
    .btn-label-info { background: #d7f5fc; color: #03c3ec; border: none; }
    .btn-label-info:hover { background: #03c3ec; color: #fff; }
</style>
@endpush

@push('scripts')
<script>window.__FACTURATION_PAGE__ = @json($page); window.__FACTURATION_TYPE__ = @json($type);</script>
<script type="module" src="{{ asset('assets/js/scripts/facturation/factures-liste.js') }}"></script>
@endpush
