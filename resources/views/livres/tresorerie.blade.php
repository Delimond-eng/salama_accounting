@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
        @include('livres._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
        @include('livres._filtres')

        <div class="card border-0 rounded-0 mb-3" v-if="synthese.length">
            <div class="card-header py-2"><h6 class="mb-0">Soldes actuels — comptes de trésorerie</h6></div>
            <div class="card-body py-2">
                <div class="d-flex flex-wrap gap-2">
                    <button
                        v-for="c in synthese"
                        :key="c.num_compte"
                        type="button"
                        class="btn btn-sm"
                        :class="numCompte === c.num_compte ? 'btn-primary' : 'btn-outline-secondary'"
                        @click="selectCompte(c.num_compte)"
                    >
                        <span class="fw-medium">@{{ c.num_compte }}</span>
                        <span class="ms-1 opacity-75">@{{ fmtMontantDevise(c.solde_actuel, filtres.devise_affichage) }}</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="card border-0 rounded-0 mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Compte de trésorerie</label>
                        <select v-model="numCompte" class="form-select" @change="loadData">
                            <option value="">— Sélectionner un compte —</option>
                            <option v-for="c in comptesListe" :key="c.num_compte" :value="c.num_compte">
                                @{{ c.num_compte }} — @{{ c.libelle }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary w-100" :disabled="!numCompte || isLoading" @click="loadData">
                            <i class="ti ti-search me-1"></i>Consulter
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3" v-if="data">
            <div class="col-md-4">
                <div class="card border-0 rounded-0 bg-light h-100">
                    <div class="card-body">
                        <p class="text-muted fs-12 mb-1">Solde d'ouverture du jour</p>
                        <h4 class="mb-0">@{{ fmtMontantDevise(data.soldes.ouverture_jour, data.devise_affichage) }}</h4>
                        <span class="fs-12 text-muted">Au @{{ filtres.date_debut }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 rounded-0 bg-light h-100">
                    <div class="card-body">
                        <p class="text-muted fs-12 mb-1">Solde final (période)</p>
                        <h4 class="mb-0">@{{ fmtMontantDevise(data.soldes.final_periode, data.devise_affichage) }}</h4>
                        <span class="fs-12 text-muted">Au @{{ filtres.date_fin }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 rounded-0 bg-primary text-white h-100">
                    <div class="card-body">
                        <p class="opacity-75 fs-12 mb-1">Solde actuel du compte</p>
                        <h4 class="mb-0 text-white">@{{ fmtMontantDevise(data.soldes.actuel, data.devise_affichage) }}</h4>
                        <span class="fs-12 opacity-75">Au @{{ data.soldes.date_actuel }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 rounded-0" v-if="data">
            <div class="card-header d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">@{{ data.compte?.num_compte }} — @{{ data.compte?.libelle }}</h5>
                    <span class="text-muted fs-12">@{{ data.lignes.length }} mouvement(s) sur la période</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-nowrap table-hover table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Pièce</th>
                                <th>Journal</th>
                                <th>Libellé</th>
                                <th>Partenaire</th>
                                <th>Devise</th>
                                <th class="text-end">Débit</th>
                                <th class="text-end">Crédit</th>
                                <th class="text-end">Solde</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-secondary fw-medium">
                                <td colspan="6">Solde d'ouverture</td>
                                <td class="text-end">—</td>
                                <td class="text-end">—</td>
                                <td class="text-end">@{{ fmtMontantDevise(data.soldes.ouverture_jour, data.devise_affichage) }}</td>
                            </tr>
                            <tr v-for="(l, i) in data.lignes" :key="i">
                                <td class="text-nowrap">@{{ l.date_ecriture }}</td>
                                <td><code class="fs-12">@{{ l.num_piece }}</code></td>
                                <td>@{{ l.journal_code }}</td>
                                <td>@{{ l.libelle }}</td>
                                <td>@{{ l.partenaire || '—' }}</td>
                                <td>@{{ l.devise_saisie }}</td>
                                <td class="text-end">@{{ l.debit ? fmtMontantDevise(l.debit, l.devise_saisie) : '—' }}</td>
                                <td class="text-end">@{{ l.credit ? fmtMontantDevise(l.credit, l.devise_saisie) : '—' }}</td>
                                <td class="text-end fw-medium">@{{ fmtMontantDevise(l.solde, data.devise_affichage) }}</td>
                            </tr>
                            <tr v-if="!data.lignes.length">
                                <td colspan="9" class="text-center text-muted py-4">Aucun mouvement sur la période sélectionnée.</td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light text-dark fw-bold" v-if="data.lignes.length">
                            <tr>
                                <td colspan="6">Totaux période</td>
                                <td class="text-end">@{{ fmtMontantDevise(totaux.debit, data.devise_affichage) }}</td>
                                <td class="text-end">@{{ fmtMontantDevise(totaux.credit, data.devise_affichage) }}</td>
                                <td class="text-end">@{{ fmtMontantDevise(data.soldes.final_periode, data.devise_affichage) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div v-else-if="numCompte && !isLoading" class="alert alert-info">Sélectionnez un compte et cliquez sur Consulter.</div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__LIVRES_PAGE__ = @json($page); window.__LIVRES_TRESORERIE_TYPE__ = @json($type);</script>
<script type="module" src="{{ asset('assets/js/scripts/livres/tresorerie.js') }}"></script>
@endpush
