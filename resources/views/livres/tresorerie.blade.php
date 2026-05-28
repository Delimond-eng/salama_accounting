@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
        @include('livres._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
        @include('livres._filtres')

        <!-- Synthèse des soldes actuels -->
        <div class="card border-0 shadow-sm mb-4" v-if="synthese.length">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-primary"><i class="ti ti-wallet me-2"></i>Soldes actuels des comptes</h6>
            </div>
            <div class="card-body p-3">
                <div class="d-flex flex-wrap gap-2">
                    <button
                        v-for="c in synthese"
                        :key="c.num_compte"
                        type="button"
                        class="btn btn-sm d-flex align-items-center gap-2 px-3 py-2"
                        :class="numCompte === c.num_compte ? 'btn-primary shadow-sm' : 'btn-label-secondary'"
                        @click="selectCompte(c.num_compte)"
                    >
                        <span class="font-monospace fw-bold">@{{ c.num_compte }}</span>
                        <span class="border-start ps-2" :class="numCompte === c.num_compte ? 'text-white' : 'text-primary'">@{{ fmt(c.solde_actuel) }}</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row g-3 align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center gap-3">
                            <label class="form-label mb-0 fw-bold text-nowrap">Compte à consulter :</label>
                            <select v-model="numCompte" class="form-select border-2" @change="loadData">
                                <option value="">— Sélectionner un compte —</option>
                                <option v-for="c in comptesListe" :key="c.num_compte" :value="c.num_compte">
                                    @{{ c.num_compte }} — @{{ c.libelle }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary w-100 px-4" :disabled="!numCompte || isLoading" @click="loadData">
                            <i class="ti ti-search me-1"></i>Charger les mouvements
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Soldes -->
        <div class="row g-4 mb-4" v-if="data">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-white border-start border-primary border-3">
                    <div class="card-body p-3">
                        <p class="text-muted fs-11 fw-bold text-uppercase mb-1">Ouverture au @{{ filtres.date_debut }}</p>
                        <h4 class="mb-0 fw-bold">@{{ fmt(data.soldes.ouverture_jour) }} <small class="fs-12">@{{ data.devise_affichage }}</small></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow bg-info-subtle border-start border-info border-3">
                    <div class="card-body p-3">
                        <p class="text-muted fs-11 fw-bold text-uppercase mb-1">Clôture au @{{ filtres.date_fin }}</p>
                        <h4 class="mb-0 fw-bold">@{{ fmt(data.soldes.final_periode) }} <small class="fs-12">@{{ data.devise_affichage }}</small></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-info-gradient border-start border-info border-3">
                    <div class="card-body p-3">
                        <p class="text-white-50 fs-11 fw-bold text-uppercase mb-1">Solde Actuel (@{{ data.soldes.date_actuel }})</p>
                        <h4 class="mb-0 fw-bold text-white">@{{ fmt(data.soldes.actuel) }} <small class="fs-12">@{{ data.devise_affichage }}</small></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des mouvements -->
        <div class="card border-0 shadow-sm" v-if="data">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold text-primary">@{{ data.compte?.num_compte }} — @{{ data.compte?.libelle }}</h5>
                    <p class="mb-0 text-muted small"><i class="ti ti-list me-1"></i>Journal détaillé des flux de trésorerie</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-soft-info text-info">@{{ data.lignes.length }} opérations</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-custom mb-0">
                        <thead>
                            <tr>
                                <th style="width: 100px">Date</th>
                                <th style="width: 110px">N° Pièce</th>
                                <th style="width: 60px">Jnl</th>
                                <th>Libellé / Partenaire</th>
                                <th class="text-end" style="width: 140px">Débit</th>
                                <th class="text-end" style="width: 140px">Crédit</th>
                                <th class="text-end" style="width: 140px">Solde progressif</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-light-soft italic">
                                <td colspan="4" class="ps-4">Report du solde à l'ouverture</td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-end fw-bold">@{{ fmt(data.soldes.ouverture_jour) }}</td>
                            </tr>
                            <tr v-for="(l, i) in data.lignes" :key="i">
                                <td class="text-muted fs-12">@{{ l.date_ecriture }}</td>
                                <td><span class="badge bg-label-secondary font-monospace">@{{ l.num_piece }}</span></td>
                                <td class="text-center"><span class="badge" :class="journalBadgeClass(null, l.journal_code)">@{{ l.journal_code }}</span></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium">@{{ l.libelle }}</span>
                                        <small class="text-info fs-10" v-if="l.partenaire && l.partenaire !== l.libelle">
                                            <i class="ti ti-user me-1"></i>@{{ l.partenaire }}
                                        </small>
                                    </div>
                                </td>
                                <td class="text-end fw-medium text-success">@{{ l.debit > 0 ? fmt(l.debit) : '' }}</td>
                                <td class="text-end fw-medium text-danger">@{{ l.credit > 0 ? fmt(l.credit) : '' }}</td>
                                <td class="text-end fw-bold">@{{ fmt(l.solde) }}</td>
                            </tr>
                            <tr v-if="!data.lignes.length">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="ti ti-mood-empty fs-32 mb-2 d-block"></i>
                                    Aucun mouvement sur la période sélectionnée.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class=" text-info fw-bold" v-if="data.lignes.length">
                            <tr>
                                <td colspan="4" class="text-end px-4 text-uppercase">Totaux Période</td>
                                <td class="text-end">@{{ fmt(totaux.debit) }}</td>
                                <td class="text-end">@{{ fmt(totaux.credit) }}</td>
                                <td class="text-end fs-15">@{{ fmt(data.soldes.final_periode) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div v-else-if="numCompte && !isLoading" class="alert alert-info shadow-sm border-0 mt-4">
            <i class="ti ti-info-circle me-2"></i>Veuillez cliquer sur <strong>Consulter</strong> pour charger les données du compte.
        </div>

        <div v-if="isLoading" class="text-center py-5">
            <div class="spinner-border text-primary mb-3"></div>
            <p class="text-muted fw-medium">Calcul des soldes et chargement des flux de trésorerie...</p>
        </div>
    </template>
</div>
@endsection

@push('styles')
<style>
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
    .btn-label-secondary { background: #f1f3f4; color: #5f6368; border: none; }
    .btn-label-secondary:hover { background: #e8eaed; color: #3c4043; }
    .bg-light-soft { background-color: #f8fafc; }
    .bg-label-info { background-color: #e0f7fa; color: #00acc1; }
    .bg-label-secondary { background-color: #f1f3f4; color: #5f6368; }
    .italic { font-style: italic; }
</style>
@endpush

@push('scripts')
<script>window.__LIVRES_PAGE__ = @json($page); window.__LIVRES_TRESORERIE_TYPE__ = @json($type);</script>
<script type="module" src="{{ asset('assets/js/scripts/livres/tresorerie.js') }}"></script>
@endpush
