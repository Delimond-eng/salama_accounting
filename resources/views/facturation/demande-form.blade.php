@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => 'demandes', 'title' => $title, 'breadcrumb' => $title])
    <div v-if="!demandeId" class="card border-0 rounded-0 mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Montant</label><input type="number" class="form-control" v-model.number="form.montant"></div>
                <div class="col-md-2">
                    <label class="form-label">Devise</label>
                    <select class="form-select" v-model="form.devise">
                        <option value="CDF">CDF</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
                <div class="col-12"><label class="form-label">Motif</label><textarea class="form-control" rows="3" v-model="form.motif"></textarea></div>
                <div class="col-12"><button type="button" class="btn btn-primary" @click="creer">Soumettre la demande</button></div>
            </div>
        </div>
    </div>
    <div v-else-if="demande" class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 rounded-0">
                <div class="card-header"><h5 class="mb-0">@{{ demande.numero }} — @{{ demande.statut }}</h5></div>
                <div class="card-body">
                    <p><strong>Montant :</strong> @{{ fmt(demande.montant) }} @{{ demande.devise }}</p>
                    <p><strong>Motif :</strong> @{{ demande.motif }}</p>
                    <p v-if="demande.etape_courante"><strong>Étape :</strong> @{{ demande.etape_courante.libelle }}</p>
                    <p v-if="demande.compte_debit" class="small text-muted">
                        Imputation : @{{ demande.compte_debit }} → @{{ demande.compte_credit }}
                    </p>

                    <div v-if="demande.etape_courante?.imputation_comptable && demande.statut==='en_validation'" class="border rounded p-3 mb-3 bg-light">
                        <h6 class="mb-3">Imputation comptable</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Compte débit</label>
                                @include('components.compte-select', ['compteKey' => 'df_debit', 'placeholder' => 'Rechercher compte débit…'])
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Compte crédit</label>
                                @include('components.compte-select', ['compteKey' => 'df_credit', 'placeholder' => 'Rechercher compte crédit…'])
                            </div>
                        </div>
                    </div>

                    <div v-if="demande.etape_courante?.execution_paiement && peutTraiter" class="border rounded p-3 mb-3 bg-light">
                        <h6 class="mb-3">Exécution du paiement</h6>
                        <div class="mb-3">
                            <label class="form-label">Méthode</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" id="df_banque" value="banque" v-model="traitement.methode" @change="loadComptesTreso">
                                    <label class="form-check-label" for="df_banque">Banque (521)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" id="df_caisse" value="caisse" v-model="traitement.methode" @change="loadComptesTreso">
                                    <label class="form-check-label" for="df_caisse">Caisse (571)</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Compte de trésorerie</label>
                            <select class="form-select" v-model="traitement.compte_tresorerie">
                                <option value="">— Sélectionner —</option>
                                <option v-for="c in comptesTreso" :key="c.num_compte" :value="c.num_compte">
                                    @{{ c.num_compte }} — @{{ c.libelle }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div v-if="peutTraiter" class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-success" @click="traiter('approuve')">Approuver</button>
                        <button type="button" class="btn btn-danger" @click="traiter('rejete')">Rejeter</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 rounded-0">
                <div class="card-header">Historique</div>
                <ul class="list-group list-group-flush">
                    <li v-for="h in demande.historiques" :key="h.id" class="list-group-item small">
                        <strong>@{{ h.action }}</strong> — @{{ h.description }}<br>
                        <span class="text-muted">@{{ h.user?.name }} · @{{ fmtDate(h.created_at) }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__DEMANDE_ID__ = @json($demande_id);</script>
<script type="module" src="{{ asset('assets/js/scripts/facturation/demande-form.js') }}"></script>
@endpush
