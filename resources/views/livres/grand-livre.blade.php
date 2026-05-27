@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('livres._nav', ['active' => 'grand-livre', 'title' => 'Grand Livre', 'breadcrumb' => 'Grand Livre'])
    @include('livres._filtres')

    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-2 bg-light-subtle rounded-3">
                    <ul class="nav nav-pills nav-pills-v2">
                        <li class="nav-item">
                            <button type="button" class="nav-link px-4 py-2" :class="{ active: vueMode === 'general' }" @click="setMode('general')">
                                <i class="ti ti-list-details me-2"></i>Vue d'ensemble (Général)
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link px-4 py-2" :class="{ active: vueMode === 'compte' }" @click="setMode('compte')">
                                <i class="ti ti-file-search me-2"></i>Analyse par compte
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Mode Recherche par compte -->
            <div class="card border-0 shadow-sm mb-4" v-if="vueMode === 'compte'">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center gap-3">
                                <label class="form-label mb-0 fw-bold text-nowrap">Sélectionner un compte :</label>
                                <div class="flex-grow-1">
                                    @include('components.compte-select', ['compteKey' => 'gl_compte', 'placeholder' => 'Saisir un numéro ou un libellé...'])
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-primary px-4" @click="loadData" :disabled="isLoading">
                                <i class="ti ti-search me-1"></i>Consulter le compte
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Grand Livre Général -->
            <div class="card border-0 shadow-sm" v-if="vueMode === 'general' && dataGeneral">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold text-primary">Récapitulatif des mouvements</h5>
                        <p class="mb-0 text-muted small">Synthèse par compte sur la période sélectionnée.</p>
                    </div>
                    <span class="badge bg-soft-info text-info">@{{ dataGeneral.lignes.length }} Comptes mouvementés</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Compte / Intitulé</th>
                                    <th class="text-center" style="width: 100px">Devise</th>
                                    <th class="text-end" style="width: 180px">Débit</th>
                                    <th class="text-end" style="width: 180px">Crédit</th>
                                    <th class="text-end" style="width: 180px">Solde net</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(r, i) in dataGeneral.lignes" :key="'g'+i">
                                    <td class="fw-medium">
                                        <span class="font-monospace text-primary fw-bold me-2">@{{ r.num_compte }}</span>
                                        @{{ r.libelle }}
                                    </td>
                                    <td class="text-center text-muted">@{{ r.devise_libelle }}</td>
                                    <td class="text-end">@{{ fmt(r.debit) }}</td>
                                    <td class="text-end">@{{ fmt(r.credit) }}</td>
                                    <td class="text-end fw-bold" :class="r.solde >= 0 ? 'text-success' : 'text-danger'">
                                        @{{ fmt(Math.abs(r.solde)) }} @{{ r.solde >= 0 ? 'D' : 'C' }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-primary text-white fw-bold">
                                <tr>
                                    <td colspan="2" class="text-end px-4">TOTAL GÉNÉRAL (@{{ dataGeneral.devise_affichage }})</td>
                                    <td class="text-end">@{{ fmt(dataGeneral.totaux.debit) }}</td>
                                    <td class="text-end">@{{ fmt(dataGeneral.totaux.credit) }}</td>
                                    <td class="text-end">@{{ fmt(Math.abs(dataGeneral.totaux.solde)) }} @{{ dataGeneral.totaux.solde >= 0 ? 'D' : 'C' }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Table Grand Livre par Compte -->
            <div class="card border-0 shadow-sm" v-if="vueMode === 'compte' && dataCompte">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h4 class="mb-1 text-primary fw-bold">
                                <span class="font-monospace">@{{ dataCompte.compte?.num_compte }}</span> — @{{ dataCompte.compte?.libelle }}
                            </h4>
                            <div class="d-flex gap-3 text-muted small">
                                <span><i class="ti ti-calendar me-1"></i>Ouverture : <strong>@{{ fmt(dataCompte.solde_ouverture) }}</strong></span>
                                <span><i class="ti ti-flag me-1"></i>Clôture : <strong class="text-primary">@{{ fmt(dataCompte.solde_cloture) }}</strong></span>
                            </div>
                        </div>
                        <span class="badge bg-soft-secondary text-secondary">@{{ dataCompte.devise_affichage }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-custom mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 100px">Date</th>
                                    <th style="width: 110px">N° Pièce</th>
                                    <th>Libellé / Partenaire</th>
                                    <th class="text-end" style="width: 150px">Débit</th>
                                    <th class="text-end" style="width: 150px">Crédit</th>
                                    <th class="text-end" style="width: 150px">Solde progressif</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-light-soft italic">
                                    <td colspan="3" class="ps-4">Report du solde à l'ouverture</td>
                                    <td class="text-end text-muted">—</td>
                                    <td class="text-end text-muted">—</td>
                                    <td class="text-end fw-bold">@{{ fmt(dataCompte.solde_ouverture) }}</td>
                                </tr>
                                <tr v-for="(l, i) in dataCompte.lignes" :key="i">
                                    <td class="text-muted fs-12">@{{ l.date_ecriture }}</td>
                                    <td><span class="badge bg-label-secondary font-monospace">@{{ l.num_piece }}</span></td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium">@{{ l.libelle }}</span>
                                            <small class="text-muted" v-if="l.partenaire && l.partenaire !== l.libelle">
                                                <i class="ti ti-user me-1"></i>@{{ l.partenaire }}
                                            </small>
                                        </div>
                                    </td>
                                    <td class="text-end fw-medium">@{{ l.debit > 0 ? fmt(l.debit) : '' }}</td>
                                    <td class="text-end fw-medium">@{{ l.credit > 0 ? fmt(l.credit) : '' }}</td>
                                    <td class="text-end fw-bold">@{{ fmt(l.solde) }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-primary text-white fw-bold">
                                <tr>
                                    <td colspan="3" class="text-end px-4">SOLDE DE CLÔTURE AU @{{ filtres.date_fin }}</td>
                                    <td class="text-end">@{{ fmt(dataCompte.lignes.reduce((s,l) => s + (Number(l.debit) || 0), 0)) }}</td>
                                    <td class="text-end">@{{ fmt(dataCompte.lignes.reduce((s,l) => s + (Number(l.credit) || 0), 0)) }}</td>
                                    <td class="text-end fs-15">@{{ fmt(dataCompte.solde_cloture) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div v-if="isLoading" class="text-center py-5">
                <div class="spinner-border text-primary mb-3"></div>
                <p class="text-muted">Calcul des soldes et chargement des écritures...</p>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('styles')
<style>
    .nav-pills-v2 .nav-link { color: #64748b; font-weight: 500; border-radius: 6px; }
    .nav-pills-v2 .nav-link.active { background-color: #fff; color: #3f7afd; shadow: 0 2px 4px rgba(0,0,0,0.05); }

    .table-custom thead th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 10px;
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
    .bg-light-soft { background-color: #f8fafc; }
    .italic { font-style: italic; }
</style>
@endpush

@push('scripts')
<script>window.__LIVRES_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/livres/grand-livre.js') }}"></script>
@endpush
