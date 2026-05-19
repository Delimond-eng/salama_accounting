@extends('layouts.app')

@section('content')

<div class="content pb-0" id="App" v-cloak>

    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>

    <template v-else>

        {{-- En-tête --}}
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-1">Tableau de bord comptable</h4>
                <p class="text-muted mb-0 fs-13" v-if="data && data.societe">
                    <i class="ti ti-building me-1"></i>@{{ data.societe.raison_sociale || data.societe.sigle }}
                    <span v-if="data.date_reference" class="ms-2">— au @{{ data.date_reference }}</span>
                </p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span v-if="data && data.exercice" class="badge bg-primary-subtle text-primary border">
                    <i class="ti ti-calendar-stats me-1"></i>@{{ data.exercice.libelle || data.exercice.annee }}
                    <span v-if="data.exercice.est_courant" class="ms-1">(courant)</span>
                </span>
                <span v-else-if="data && data.message" class="badge bg-warning-subtle text-warning border">@{{ data.message }}</span>
                <span v-if="data && data.devise" class="badge bg-secondary-subtle text-secondary border">@{{ data.devise }}</span>
                <button type="button" class="btn btn-outline-primary btn-sm" :disabled="isLoading" @click="loadData">
                    <i class="ti ti-refresh me-1" :class="{ 'ti-spin': isLoading }"></i>Actualiser
                </button>
            </div>
        </div>

        <div v-if="error && error.length" class="alert alert-danger">
            <div v-for="(msg, i) in error" :key="i">@{{ msg }}</div>
        </div>

        <div v-if="data && data.message && !data.exercice" class="alert alert-warning">
            @{{ data.message }}
        </div>

        <template v-if="data && data.exercice">

            {{-- Section 1 — KPI --}}
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted">Banque <span class="fs-12">(521)</span></p>
                            <h4 class="mb-0">@{{ fmt(data.kpis.tresorerie.banque) }}</h4>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg bg-primary-gradient-100 position-absolute top-0 end-0">
                                    <i class="ti ti-building-bank fs-24 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted">Caisse <span class="fs-12">(571)</span></p>
                            <h4 class="mb-0">@{{ fmt(data.kpis.tresorerie.caisse) }}</h4>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg bg-success-gradient-100 position-absolute top-0 end-0">
                                    <i class="ti ti-cash fs-24 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted">Total trésorerie</p>
                            <h4 class="mb-0">@{{ fmt(data.kpis.tresorerie.total) }}</h4>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg bg-info-gradient-100 position-absolute top-0 end-0">
                                    <i class="ti ti-wallet fs-24 text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted">Résultat exercice</p>
                            <h4 class="mb-0" :class="data.kpis.resultat_positif ? 'text-success' : 'text-danger'">
                                @{{ fmt(data.kpis.resultat_exercice) }}
                            </h4>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg position-absolute top-0 end-0"
                                    :class="data.kpis.resultat_positif ? 'bg-success-gradient-100' : 'bg-danger-gradient-100'">
                                    <i class="ti fs-24" :class="data.kpis.resultat_positif ? 'ti-trending-up text-success' : 'ti-trending-down text-danger'"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted">Créances clients <span class="fs-12">(411)</span></p>
                            <h4 class="mb-0">@{{ fmt(data.kpis.creances_clients) }}</h4>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg bg-warning-gradient-100 position-absolute top-0 end-0">
                                    <i class="ti ti-users fs-24 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted">Dettes fournisseurs <span class="fs-12">(401)</span></p>
                            <h4 class="mb-0">@{{ fmt(data.kpis.dettes_fournisseurs) }}</h4>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg bg-pink-gradient-100 position-absolute top-0 end-0">
                                    <i class="ti ti-truck-delivery fs-24 text-pink"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted">Écritures validées</p>
                            <h4 class="mb-1">@{{ data.kpis.ecritures.aujourdhui }} <span class="fs-13 fw-normal text-muted">aujourd'hui</span></h4>
                            <p class="mb-0 fs-13 text-muted">
                                Mois : <strong>@{{ data.kpis.ecritures.mois }}</strong>
                                · Exercice : <strong>@{{ data.kpis.ecritures.exercice }}</strong>
                            </p>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg bg-secondary-gradient-100 position-absolute top-0 end-0">
                                    <i class="ti ti-file-invoice fs-24 text-secondary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill" :class="{ 'border-danger': data.kpis.journaux.desequilibrees_count > 0 }">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted">Journaux / anomalies</p>
                            <h4 class="mb-1" :class="data.kpis.journaux.desequilibrees_count > 0 ? 'text-danger' : ''">
                                @{{ data.kpis.journaux.desequilibrees_count }}
                                <span class="fs-13 fw-normal text-muted">déséquilibrée(s)</span>
                            </h4>
                            <p class="mb-0 fs-13">
                                <span class="badge badge-soft-warning me-1">@{{ data.kpis.journaux.brouillons_total }} brouillon(s)</span>
                                <span v-if="data.kpis.journaux.brouillons_od > 0" class="badge badge-soft-danger">@{{ data.kpis.journaux.brouillons_od }} OD</span>
                            </p>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg bg-danger-gradient-100 position-absolute top-0 end-0">
                                    <i class="ti ti-alert-triangle fs-24 text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2 — Contrôles + Alertes --}}
            <div class="row mb-4">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="ti ti-list-check me-2"></i>Contrôles comptables</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-start gap-2">
                                    <i class="ti fs-20 mt-1" :class="data.controles.balance.ok ? 'ti-circle-check text-success' : 'ti-alert-circle text-danger'"></i>
                                    <div>
                                        <span class="fw-medium">Balance générale</span>
                                        <p class="mb-0 fs-13 text-muted">@{{ data.controles.balance.message }}</p>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex align-items-start gap-2">
                                    <i class="ti fs-20 mt-1" :class="data.controles.bilan.ok ? 'ti-circle-check text-success' : 'ti-alert-circle text-danger'"></i>
                                    <div>
                                        <span class="fw-medium">Bilan</span>
                                        <p class="mb-0 fs-13 text-muted">@{{ data.controles.bilan.message }}</p>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex align-items-start gap-2">
                                    <i class="ti fs-20 mt-1" :class="data.controles.tft.ok ? 'ti-circle-check text-success' : 'ti-alert-circle text-warning'"></i>
                                    <div>
                                        <span class="fw-medium">Tableau de flux de trésorerie</span>
                                        <p class="mb-0 fs-13 text-muted">@{{ data.controles.tft.message }}</p>
                                    </div>
                                </li>
                                <li v-if="data.controles.comptes_anormaux.items && data.controles.comptes_anormaux.items.length"
                                    class="list-group-item d-flex align-items-start gap-2">
                                    <i class="ti ti-alert-triangle text-warning fs-20 mt-1"></i>
                                    <div>
                                        <span class="fw-medium">Comptes anormaux (@{{ data.controles.comptes_anormaux.count }})</span>
                                        <ul class="mb-0 ps-3 fs-13 text-muted">
                                            <li v-for="(item, i) in data.controles.comptes_anormaux.items" :key="'ca-'+i">@{{ item }}</li>
                                        </ul>
                                    </div>
                                </li>
                                <li v-if="data.controles.journal.items && data.controles.journal.items.length"
                                    class="list-group-item d-flex align-items-start gap-2">
                                    <i class="ti ti-alert-triangle text-warning fs-20 mt-1"></i>
                                    <div>
                                        <span class="fw-medium">Contrôle journal (@{{ data.controles.journal.count }})</span>
                                        <ul class="mb-0 ps-3 fs-13 text-muted">
                                            <li v-for="(item, i) in data.controles.journal.items" :key="'cj-'+i">@{{ item }}</li>
                                        </ul>
                                    </div>
                                </li>
                                <li v-if="data.controles.tous_ok" class="list-group-item text-success fs-13">
                                    <i class="ti ti-circle-check me-1"></i>Tous les contrôles sont satisfaisants.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="ti ti-bell me-2"></i>Alertes</h5>
                            <span v-if="data.alertes.count" class="badge bg-danger">@{{ data.alertes.count }}</span>
                        </div>
                        <div class="card-body p-0">
                            <ul v-if="data.alertes.items && data.alertes.items.length" class="list-group list-group-flush">
                                <li v-for="(alerte, i) in data.alertes.items" :key="i" class="list-group-item">
                                    <div class="d-flex align-items-start gap-2">
                                        <span class="badge flex-shrink-0" :class="alerteBadgeClass(alerte.niveau)">@{{ alerte.niveau }}</span>
                                        <div>
                                            <span class="fw-medium d-block">@{{ alerte.titre }}</span>
                                            <span class="fs-13 text-muted">@{{ alerte.detail }}</span>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="text-muted text-center py-4 mb-0">
                                <i class="ti ti-circle-check text-success me-1"></i>Aucune alerte active.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 3 — Graphiques --}}
            <div class="row mb-4">
                <div class="col-xl-6 col-lg-12 mb-3 mb-xl-0">
                    <div class="card h-100">
                        <div class="card-header"><h6 class="mb-0">Évolution trésorerie (521 + 571)</h6></div>
                        <div class="card-body">
                            <div id="chart-treso" class="dashboard-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100">
                        <div class="card-header"><h6 class="mb-0">Charges (classe 6)</h6></div>
                        <div class="card-body">
                            <div id="chart-charges" class="dashboard-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100">
                        <div class="card-header"><h6 class="mb-0">Produits (classe 7)</h6></div>
                        <div class="card-body">
                            <div id="chart-produits" class="dashboard-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 mt-3">
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0">Résultat mensuel (classes 6–7)</h6></div>
                        <div class="card-body">
                            <div id="chart-resultat" class="dashboard-chart dashboard-chart-wide"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 4 — Activité, exercices, devises, ventes --}}
            <div class="row mb-4">
                <div class="col-xl-6 col-lg-12 mb-3 mb-xl-0">
                    <div class="card h-100">
                        <div class="card-header"><h6 class="mb-0">Activité récente</h6></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Jnl.</th>
                                            <th>Pièce</th>
                                            <th>Libellé</th>
                                            <th class="text-end">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="!data.activite_recente || !data.activite_recente.length">
                                            <td colspan="5" class="text-center text-muted py-4">Aucune écriture récente</td>
                                        </tr>
                                        <tr v-for="(act, i) in data.activite_recente" :key="i">
                                            <td class="text-nowrap">@{{ act.date }}</td>
                                            <td>
                                                <span class="badge" :class="journalBadgeClass(act.journal_type, act.journal_code)">@{{ act.journal_code }}</span>
                                            </td>
                                            <td><code class="fs-12">@{{ act.num_piece }}</code></td>
                                            <td class="text-truncate" style="max-width: 200px;">@{{ act.libelle }}</td>
                                            <td class="text-end text-nowrap">@{{ fmt(act.montant) }} @{{ act.devise }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 mb-3">
                        <div class="card-header"><h6 class="mb-0">Exercices</h6></div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li v-for="ex in data.exercices" :key="ex.id" class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start gap-1">
                                        <div>
                                            <span class="fw-medium">@{{ ex.libelle || ex.annee }}</span>
                                            <p class="mb-0 fs-12 text-muted">@{{ ex.date_debut }} → @{{ ex.date_fin }}</p>
                                        </div>
                                        <span class="badge flex-shrink-0" :class="exerciceStatutClass(ex.statut)">@{{ ex.statut }}</span>
                                    </div>
                                    <span v-if="ex.est_courant" class="badge badge-soft-primary mt-1">Courant</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0">Devises</h6></div>
                        <div class="card-body">
                            <p class="mb-2">
                                <span class="text-muted">Devise principale :</span>
                                <strong>@{{ data.devises.devise_principale }}</strong>
                            </p>
                            <p class="mb-2">
                                <span class="text-muted">Taux USD/CDF :</span>
                                <strong>@{{ data.devises.taux_usd_cdf }}</strong>
                                <span v-if="data.devises.taux_manquant" class="badge badge-soft-warning ms-1">non renseigné</span>
                            </p>
                            <p class="mb-0 fs-13 text-muted">
                                @{{ data.devises.ecritures_multi_devise }} écriture(s) en devise étrangère sur l'exercice.
                            </p>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0">Ventes — chiffre d'affaires (70)</h6></div>
                        <div class="card-body">
                            <p class="mb-1">
                                <span class="text-muted">CA du jour :</span>
                                <strong class="fs-18">@{{ fmt(data.ventes.ca_jour) }}</strong>
                            </p>
                            <p class="mb-1">
                                <span class="text-muted">CA du mois :</span>
                                <strong>@{{ fmt(data.ventes.ca_mois) }}</strong>
                            </p>
                            <p class="mb-0 fs-13">
                                <span class="text-muted">Mois précédent :</span> @{{ fmt(data.ventes.ca_mois_precedent) }}
                                <span v-if="data.ventes.evolution_pct != null" class="ms-1 badge"
                                    :class="data.ventes.evolution_pct >= 0 ? 'badge-soft-success' : 'badge-soft-danger'">
                                    @{{ data.ventes.evolution_pct >= 0 ? '+' : '' }}@{{ data.ventes.evolution_pct }}%
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 5 — Liens rapides --}}
            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0">Liens rapides</h6></div>
                <div class="card-body">
                    <div class="row g-2">
                        <div v-for="(lien, i) in data.liens_rapides" :key="i" class="col-xl-3 col-md-4 col-sm-6">
                            <a :href="routeUrl(lien.route)" class="btn w-100 text-start" :class="'btn-outline-' + lien.color">
                                <i class="ti me-2" :class="lien.icon"></i>@{{ lien.label }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </template>

        <div v-else-if="!isLoading && !data" class="alert alert-info">
            Aucune donnée disponible — configurez une société et un exercice courant.
        </div>

    </template>

</div>

@endsection

@push('styles')
<style>
    .dashboard-chart { min-height: 260px; }
    .dashboard-chart-wide { min-height: 220px; }
    #App .custom-card-icon .avatar { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; }
    #App .card-body.position-relative { padding-right: 4rem; }
</style>
@endpush

@push('scripts')
@php
    $dashboardRoutes = [
        'data' => route('dashboard.data'),
        'livres' => [
            'journal' => route('accounting.livres.journal'),
            'grand_livre' => route('accounting.livres.grand-livre'),
            'balance' => route('accounting.livres.balance'),
            'auxiliaire' => route('accounting.livres.auxiliaire'),
            'lettrage' => route('accounting.livres.lettrage'),
            'comptes_tiers' => route('accounting.livres.comptes-tiers'),
            'banque' => route('accounting.livres.banque'),
            'caisse' => route('accounting.livres.caisse'),
            'metadata' => route('accounting.livres.metadata'),
            'tresorerie_comptes' => route('accounting.livres.tresorerie.comptes'),
            'tresorerie_data' => route('accounting.livres.tresorerie.data'),
            'preferences' => route('accounting.livres.preferences'),
            'balance_data' => route('accounting.livres.balance.data'),
            'journal_data' => route('accounting.livres.journal.data'),
            'grand_livre_data' => route('accounting.livres.grand-livre.data'),
            'grand_livre_general_data' => route('accounting.livres.grand-livre.general.data'),
            'auxiliaire_data' => route('accounting.livres.auxiliaire.data'),
            'lettrage_data' => route('accounting.livres.lettrage.data'),
            'comptes_tiers_data' => route('accounting.livres.comptes-tiers.data'),
        ],
        'etats' => [
            'bilan' => route('accounting.etats.bilan'),
            'compte_resultat' => route('accounting.etats.compte-resultat'),
            'flux_tresorerie' => route('accounting.etats.flux-tresorerie'),
            'variation_kp' => route('accounting.etats.variation-kp'),
            'annexes' => route('accounting.etats.annexes'),
            'comparatif' => route('accounting.etats.comparatif'),
            'exports' => route('accounting.etats.exports'),
            'metadata' => route('accounting.etats.metadata'),
            'bilan_data' => route('accounting.etats.bilan.data'),
            'compte_resultat_data' => route('accounting.etats.compte-resultat.data'),
            'flux_tresorerie_data' => route('accounting.etats.flux-tresorerie.data'),
            'variation_kp_data' => route('accounting.etats.variation-kp.data'),
            'annexes_data' => route('accounting.etats.annexes.data'),
            'comparatif_data' => route('accounting.etats.comparatif.data'),
            'export' => route('accounting.etats.export', ['type' => '__TYPE__']),
        ],
        'named' => [
            'accounting.livres.journal' => route('accounting.livres.journal'),
            'accounting.livres.grand-livre' => route('accounting.livres.grand-livre'),
            'accounting.livres.balance' => route('accounting.livres.balance'),
            'accounting.livres.auxiliaire' => route('accounting.livres.auxiliaire'),
            'accounting.livres.lettrage' => route('accounting.livres.lettrage'),
            'accounting.livres.comptes-tiers' => route('accounting.livres.comptes-tiers'),
            'accounting.livres.banque' => route('accounting.livres.banque'),
            'accounting.livres.caisse' => route('accounting.livres.caisse'),
            'accounting.etats.bilan' => route('accounting.etats.bilan'),
            'accounting.etats.compte-resultat' => route('accounting.etats.compte-resultat'),
            'accounting.etats.flux-tresorerie' => route('accounting.etats.flux-tresorerie'),
            'accounting.etats.variation-kp' => route('accounting.etats.variation-kp'),
            'accounting.etats.annexes' => route('accounting.etats.annexes'),
            'accounting.etats.comparatif' => route('accounting.etats.comparatif'),
            'accounting.etats.exports' => route('accounting.etats.exports'),
        ],
    ];
@endphp
<script>window.__DASHBOARD_ROUTES__ = @json($dashboardRoutes);</script>
<script type="module" src="{{ asset('assets/js/scripts/dashboard-comptable.js') }}"></script>
@endpush
