@extends('layouts.app')

@section('content')

<div class="content pb-0" id="App" v-cloak>

    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>

    <template v-else>

        {{-- Header conforme au style Accounting --}}
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-1 text-dark fw-bold">Tableau de bord</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Reporting & Pilotage</li>
                    </ol>
                </nav>

            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div v-if="data && data.exercice" class="d-flex gap-2 flex-wrap">
                    <span class="badge bg-info text-white p-2 shadow-sm">
                        <i class="ti ti-calendar-stats me-1"></i>@{{ data.exercice.libelle || data.exercice.annee }}
                        <span v-if="data.exercice.est_courant" class="ms-1">(courant)</span>
                    </span>
                </div>
                <button type="button" class="btn btn-white btn-sm border shadow-sm" :disabled="isLoading" @click="loadData">
                    <i class="ti ti-refresh me-1" :class="{ 'ti-spin': isLoading }"></i>Actualiser
                </button>
            </div>
        </div>

        {{-- Filtre devise / périmètre --}}
        <div v-if="data && data.exercice" class="card border-0 shadow-sm mb-4">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h6 class="mb-1 fw-semibold"><i class="ti ti-currency-dollar me-1 text-primary"></i>Périmètre d'affichage</h6>
                        <p class="text-muted fs-12 mb-0">KPI, graphiques et contrôles recalculés selon le filtre sélectionné.</p>
                    </div>
                    <div class="btn-group flex-wrap" role="group">
                        <button v-for="p in presetsDevise" :key="p.id" type="button"
                            class="btn btn-sm"
                            :class="presetActif(p) ? 'btn-primary' : 'btn-outline-primary'"
                            :disabled="isLoading"
                            @click="appliquerPreset(p)">
                            @{{ p.label }}
                        </button>
                    </div>
                    <div v-if="filtresDevise.scope_devise === 'consolide'" class="d-flex align-items-center gap-2">
                        <label class="form-label mb-0 fs-12 text-muted text-nowrap">Taux de conversion</label>
                        <select class="form-select form-select-sm" style="min-width:130px" v-model="filtresDevise.mode_conversion" @change="onFiltreChange" :disabled="isLoading">
                            <option value="origine">Taux à l'origine</option>
                            <option value="actuel">Taux actuel</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alertes BS --}}
        <div v-if="data && data.alertes && data.alertes.items.length" class="mb-4">
            <div v-for="(alerte, i) in data.alertes.items" :key="i"
                 class="alert alert-dismissible fade show d-flex align-items-center mb-2 shadow-sm border-start border-4"
                 :class="'alert-' + alerte.niveau + ' border-' + alerte.niveau">
                <i class="ti fs-18 me-2" :class="alerte.niveau === 'danger' ? 'ti-alert-octagon' : 'ti-info-circle'"></i>
                <div class="flex-fill fs-13">
                    <strong class="me-1">@{{ alerte.titre }} :</strong> @{{ alerte.detail }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>

        <div v-if="error && error.length" class="alert alert-danger">
            <div v-for="(msg, i) in error" :key="i">@{{ msg }}</div>
        </div>

        <template v-if="data && data.exercice">

            {{-- SECTION 1 — KPI (WHITE ICONS) --}}
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill shadow-sm border-0">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted fs-13">Banque <span class="fs-11">(521)</span></p>
                            <h4 class="mb-0">@{{ fmt(data.kpis.tresorerie.banque) }}</h4>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg bg-primary-gradient-100 position-absolute top-0 end-0 text-white">
                                    <i class="ti ti-building-bank fs-24 text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill shadow-sm border-0">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted fs-13">Caisse <span class="fs-11">(571)</span></p>
                            <h4 class="mb-0">@{{ fmt(data.kpis.tresorerie.caisse) }}</h4>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg bg-success-gradient-100 position-absolute top-0 end-0 text-white">
                                    <i class="ti ti-cash fs-24 text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill shadow-sm border-0">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted fs-13">Total trésorerie</p>
                            <h4 class="mb-0">@{{ fmt(data.kpis.tresorerie.total) }}</h4>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg bg-info-gradient-100 position-absolute top-0 end-0 text-white">
                                    <i class="ti ti-wallet fs-24 text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill shadow-sm border-0">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted fs-13">Résultat exercice</p>
                            <h4 class="mb-0" :class="data.kpis.resultat_positif ? 'text-success' : 'text-danger'">
                                @{{ fmt(data.kpis.resultat_exercice) }}
                            </h4>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg position-absolute top-0 end-0 text-white"
                                    :class="data.kpis.resultat_positif ? 'bg-success-gradient-100' : 'bg-danger-gradient-100'">
                                    <i class="ti fs-24 text-white" :class="data.kpis.resultat_positif ? 'ti-trending-up' : 'ti-trending-down'"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill shadow-sm border-0">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted fs-13">Créances clients <span class="fs-11">(411)</span></p>
                            <h4 class="mb-0">@{{ fmt(data.kpis.creances_clients) }}</h4>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg bg-warning-gradient-100 position-absolute top-0 end-0 text-white">
                                    <i class="ti ti-users fs-24 text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill shadow-sm border-0">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted fs-13">Dettes fournisseurs <span class="fs-11">(401)</span></p>
                            <h4 class="mb-0">@{{ fmt(data.kpis.dettes_fournisseurs) }}</h4>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg bg-pink-gradient-100 position-absolute top-0 end-0 text-white">
                                    <i class="ti ti-truck-delivery fs-24 text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill shadow-sm border-0">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted fs-13">Écritures validées</p>
                            <h4 class="mb-1">@{{ data.kpis.ecritures.aujourdhui }} <span class="fs-13 fw-normal text-muted">aujourd'hui</span></h4>
                            <p class="mb-0 fs-12 text-muted">
                                Mois : <strong>@{{ data.kpis.ecritures.mois }}</strong>
                                · Exercice : <strong>@{{ data.kpis.ecritures.exercice }}</strong>
                            </p>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg bg-secondary-gradient-100 position-absolute top-0 end-0 text-white">
                                    <i class="ti ti-file-invoice fs-24 text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 d-flex">
                    <div class="card flex-fill shadow-sm border-0" :class="{ 'border-danger': data.kpis.journaux.desequilibrees_count > 0 }">
                        <div class="card-body position-relative">
                            <p class="fw-medium mb-1 text-muted fs-13">Journaux / anomalies</p>
                            <h4 class="mb-1" :class="data.kpis.journaux.desequilibrees_count > 0 ? 'text-danger' : ''">
                                @{{ data.kpis.journaux.desequilibrees_count }}
                                <span class="fs-13 fw-normal text-muted">anomalie(s)</span>
                            </h4>
                            <p class="mb-0 fs-12">
                                <span class="badge badge-soft-warning me-1">@{{ data.kpis.journaux.brouillons_total }} brouillon(s)</span>
                                <span v-if="data.kpis.journaux.brouillons_od > 0" class="badge badge-soft-danger">@{{ data.kpis.journaux.brouillons_od }} OD</span>
                            </p>
                            <div class="custom-card-icon">
                                <div class="avatar avatar-rounded avatar-lg bg-danger-gradient-100 position-absolute top-0 end-0 text-white">
                                    <i class="ti ti-alert-triangle fs-24 text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- SECTION : CONTRÔLES COMPTABLES (STYLE REVENUE SUMMARY COMPARISON) --}}
                <div class="col-lg-6 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-3">
                            <h6 class="card-title mb-0 fs-15 fw-bold text-dark"><i class="ti ti-list-check me-2 text-primary"></i>Contrôles comptables</h6>
                        </div>
                        <div class="card-body">
                            {{-- Balance --}}
                            <div class="p-4 d-flex justify-content-between align-items-center mb-2 rounded shadow-none"
                                 :class="data.controles.balance.ok ? 'bg-success-subtle' : 'bg-danger-subtle'">
                                <div class="row w-100 g-1 align-items-center">
                                    <div class="col-6">
                                        <p class="mb-1 fs-14 text-dark fw-bold">Balance générale</p>
                                        <p class="mb-0 fs-12 text-muted italic">Équilibre total débit / crédit</p>
                                    </div>
                                    <div class="col-4">
                                        <p class="mb-1 fs-14 text-dark">Statut</p>
                                        <div class="fw-bold mb-0 fs-16" :class="data.controles.balance.ok ? 'text-success' : 'text-danger'">@{{ data.controles.balance.message }}</div>
                                    </div>
                                    <div class="col-2 d-flex align-items-center justify-content-end">
                                        <span class="badge rounded-pill bg-white text-dark py-2 fs-12 shadow-sm">
                                            <i class="ti fs-16" :class="data.controles.balance.ok ? 'ti-check text-success' : 'ti-x text-danger'"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            {{-- Bilan --}}
                            <div class="p-4 d-flex justify-content-between align-items-center mb-2 rounded shadow-none"
                                 :class="data.controles.bilan.ok ? 'bg-success-subtle' : 'bg-danger-subtle'">
                                <div class="row w-100 g-1 align-items-center">
                                    <div class="col-6">
                                        <p class="mb-1 fs-14 text-dark fw-bold">Équilibre du Bilan</p>
                                        <p class="mb-0 fs-12 text-muted italic">Égalité Actif = Passif</p>
                                    </div>
                                    <div class="col-4">
                                        <p class="mb-1 fs-14 text-dark">Statut</p>
                                        <div class="fw-bold mb-0 fs-16" :class="data.controles.bilan.ok ? 'text-success' : 'text-danger'">@{{ data.controles.bilan.message }}</div>
                                    </div>
                                    <div class="col-2 d-flex align-items-center justify-content-end">
                                        <span class="badge rounded-pill bg-white text-dark py-2 fs-12 shadow-sm">
                                            <i class="ti fs-16" :class="data.controles.bilan.ok ? 'ti-check text-success' : 'ti-x text-danger'"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            {{-- TFT --}}
                            <div class="p-4 d-flex justify-content-between align-items-center mb-2 rounded shadow-none"
                                 :class="data.controles.tft.ok ? 'bg-success-subtle' : 'bg-warning-subtle'">
                                <div class="row w-100 g-1 align-items-center">
                                    <div class="col-6">
                                        <p class="mb-1 fs-14 text-dark fw-bold">Tableau des flux (TFT)</p>
                                        <p class="mb-0 fs-12 text-muted italic">ZH vs Variation trésorerie</p>
                                    </div>
                                    <div class="col-4">
                                        <p class="mb-1 fs-14 text-dark">Statut</p>
                                        <div class="fw-bold mb-0 fs-16" :class="data.controles.tft.ok ? 'text-success' : 'text-warning text-dark'">@{{ data.controles.tft.message }}</div>
                                    </div>
                                    <div class="col-2 d-flex align-items-center justify-content-end">
                                        <span class="badge rounded-pill bg-white text-dark py-2 fs-12 shadow-sm">
                                            <i class="ti fs-16" :class="data.controles.tft.ok ? 'ti-check text-success' : 'ti-alert-triangle text-warning'"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            {{-- Anomalies --}}
                            <div v-if="data.controles.comptes_anormaux.count > 0"
                                 class="p-4 rounded bg-warning-subtle shadow-none">
                                <div class="row w-100 g-1">
                                    <div class="col-12">
                                        <p class="mb-1 fs-14 text-dark fw-bold">Comptes anormaux (@{{ data.controles.comptes_anormaux.count }})</p>
                                        <div class="bg-white bg-opacity-75 p-3 rounded">
                                            <span v-for="(item, i) in data.controles.comptes_anormaux.items" :key="'ca-'+i" class="d-block fs-12 text-muted mb-1">• @{{ item }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- VENTES (70) & DEVISES --}}
                <div class="col-lg-6 d-flex flex-column gap-3">
                    <div class="card border-0 shadow-sm flex-fill">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="fs-14 mb-0 text-dark fw-bold">Ventes — Chiffre d'Affaires (70)</h6>
                                <span class="badge" :class="data.ventes.evolution_pct >= 0 ? 'badge-soft-success' : 'badge-soft-danger'">
                                    <i class="ti" :class="data.ventes.evolution_pct >= 0 ? 'ti-arrow-up' : 'ti-arrow-down'"></i>
                                    @{{ Math.abs(data.ventes.evolution_pct || 0) }}%
                                </span>
                            </div>
                            <div class="row text-center">
                                <div class="col-4 border-end">
                                    <p class="text-muted mb-1 fs-11 uppercase fw-medium">Aujourd'hui</p>
                                    <h5 class="mb-0 fw-bold">@{{ fmt(data.ventes.ca_jour) }}</h5>
                                </div>
                                <div class="col-4 border-end">
                                    <p class="text-muted mb-1 fs-11 uppercase fw-medium">Ce mois</p>
                                    <h5 class="mb-0 fw-bold text-primary">@{{ fmt(data.ventes.ca_mois) }}</h5>
                                </div>
                                <div class="col-4">
                                    <p class="text-muted mb-1 fs-11 uppercase fw-medium">Mois préc.</p>
                                    <h5 class="mb-0 fw-bold text-muted">@{{ fmt(data.ventes.ca_mois_precedent) }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card border-0 shadow-sm flex-fill">
                        <div class="card-body">
                            <h6 class="fs-14 mb-3 text-dark fw-bold">Contexte & Devises</h6>
                            <div class="d-flex align-items-center justify-content-between mb-3 px-2">
                                <div class="text-center flex-fill">
                                    <p class="text-muted mb-0 fs-12 italic">Devise Pivot</p>
                                    <h5 class="mb-0 fw-bold">@{{ data.devises.devise_principale }}</h5>
                                </div>
                                <div class="vr mx-3"></div>
                                <div class="text-center flex-fill">
                                    <p class="text-muted mb-0 fs-12 italic">Taux USD/CDF</p>
                                    <h5 class="mb-0 text-primary fw-bold">@{{ data.devises.taux_usd_cdf }}</h5>
                                </div>
                            </div>
                            <div class="text-center pt-2 border-top">
                                <span class="fs-12 text-muted fw-medium">@{{ data.devises.ecritures_multi_devise }} écritures gérées en multi-devises</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECTION 3 — GRAPHES --}}
            <div class="row mb-4">
                <div class="col-xl-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-3 pb-0">
                            <h6 class="card-title mb-0 fs-14 fw-bold text-dark">Évolution trésorerie (521 + 571)</h6>
                        </div>
                        <div class="card-body">
                            <div id="chart-treso" class="dashboard-chart"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-3 pb-0"><h6 class="card-title mb-0 fs-14 fw-bold text-dark">Charges (Cl. 6)</h6></div>
                        <div class="card-body"><div id="chart-charges" class="dashboard-chart"></div></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-3 pb-0"><h6 class="card-title mb-0 fs-14 fw-bold text-dark">Produits (Cl. 7)</h6></div>
                        <div class="card-body"><div id="chart-produits" class="dashboard-chart"></div></div>
                    </div>
                </div>
                <div class="col-12 mt-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-3 pb-0"><h6 class="card-title mb-0 fs-14 fw-bold text-dark">Résultat mensuel (classes 6-7)</h6></div>
                        <div class="card-body">
                            <div id="chart-resultat" class="dashboard-chart" style="min-height: 220px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECTION 4 — ACTIVITÉ (STYLE COMPACT AVEC RUBRIQUES HUMANISÉES) & EXERCICES --}}
            <div class="row mb-4">
                <div class="col-xl-8">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header d-flex align-items-center justify-content-between bg-transparent border-0 pt-3 pb-2">
                            <h6 class="card-title mb-0 fs-15 fw-bold text-dark">Activité récente (Validée)</h6>
                            <a :href="routeUrl('accounting.livres.journal')" class="btn btn-outline-light btn-sm shadow-sm border py-0">Tout voir</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive custom-table">
                                <table class="table table-nowrap mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Écriture / Libellé</th>
                                            <th>Journal</th>
                                            <th>Pièce</th>
                                            <th class="text-end pe-3">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-for="(act, i) in data.activite_recente">
                                            <tr v-if="i === 0 || act.date !== data.activite_recente[i-1].date">
                                                <td colspan="4" class="bg-light ps-3 py-1 fs-10 fw-bold text-uppercase text-muted border-top border-bottom">
                                                    <i class="ti ti-calendar me-1"></i> @{{ humanizeDate(act.date) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="ps-3 py-2">
                                                    <span class="text-dark fw-medium fs-13 d-block text-truncate" style="max-width: 450px;">@{{ act.libelle }}</span>
                                                </td>
                                                <td class="py-2"><span class="badge" :class="journalBadgeClass(act.journal_type, act.journal_code)">@{{ act.journal_code }}</span></td>
                                                <td class="py-2"><span class="text-muted fs-11">#@{{ act.num_piece }}</span></td>
                                                <td class="py-2 text-end pe-3 fw-bold text-dark fs-13">@{{ fmt(act.montant) }}</td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-3">
                            <h6 class="card-title mb-0 fs-15 fw-bold text-dark">Historique des Exercices</h6>
                        </div>
                        <div class="card-body">
                            <div class="bg-light rounded p-2 border shadow-none">
                                <ul class="list-group list-group-flush bg-transparent">
                                    <li v-for="ex in data.exercices" :key="ex.id" class="list-group-item d-flex align-items-center justify-content-between py-2 border-0 bg-transparent px-2">
                                        <div>
                                            <span class="fw-medium fs-13 text-dark">@{{ ex.libelle || ex.annee }}</span>
                                            <p class="mb-0 fs-11 text-muted italic">Du @{{ ex.date_debut }} au @{{ ex.date_fin }}</p>
                                        </div>
                                        <span class="badge rounded-pill" :class="exerciceStatutClass(ex.statut)">@{{ ex.statut }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECTION 5 — LIENS RAPIDES (RECLASSÉS) --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h6 class="card-title mb-0 fs-15 fw-bold text-dark">Raccourcis & Navigation</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div v-for="(liens, cat) in data.liens_rapides" :key="cat" class="col-md-4 mb-3 mb-md-0">
                            <p class="text-uppercase fs-10 fw-bold text-primary mb-2 border-bottom pb-1">@{{ cat }}</p>
                            <div class="d-grid gap-2">
                                <a v-for="(lien, i) in liens" :key="i" :href="routeUrl(lien.route)"
                                   class="btn btn-white btn-sm text-start border shadow-none d-flex align-items-center">
                                    <i class="ti me-2 fs-16" :class="[lien.icon, 'text-' + lien.color]"></i>
                                    <span class="fs-13">@{{ lien.label }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </template>

    </template>

</div>

@endsection

@push('styles')
<style>
    .dashboard-chart { min-height: 260px; }
    #App .custom-card-icon .avatar { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; }
    #App .card-body.position-relative { padding-right: 4rem; }
    #App .custom-card-icon .avatar i { color: #fff !important; }
    .bg-primary-gradient-100 { background: linear-gradient(135deg, #3F7AFD 0%, #2a5fdf 100%); }
    .bg-success-gradient-100 { background: linear-gradient(135deg, #03C95A 0%, #02a14a 100%); }
    .bg-info-gradient-100 { background: linear-gradient(135deg, #00BFFF 0%, #0096cc 100%); }
    .bg-danger-gradient-100 { background: linear-gradient(135deg, #E70D0D 0%, #c10b0b 100%); }
    .bg-warning-gradient-100 { background: linear-gradient(135deg, #FFC107 0%, #e0a800 100%); }
    .bg-pink-gradient-100 { background: linear-gradient(135deg, #FF4081 0%, #e91e63 100%); }
    .bg-secondary-gradient-100 { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); }
    .italic { font-style: italic; }
    .uppercase { text-transform: uppercase; }
    .custom-table thead th { font-weight: 700; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; border-bottom: 0; padding: 0.6rem 0.5rem; }
    .custom-table tbody tr:hover { background-color: #f8f9fa; }
</style>
@endpush

@push('scripts')
@php
    $dashboardRoutes = [
        'data' => route('dashboard.data'),
        'named' => [
            'accounting.livres.journal' => route('accounting.livres.journal'),
            'accounting.livres.grand-livre' => route('accounting.livres.grand-livre'),
            'accounting.livres.balance' => route('accounting.livres.balance'),
            'accounting.livres.lettrage' => route('accounting.livres.lettrage'),
            'accounting.etats.bilan' => route('accounting.etats.bilan'),
            'accounting.etats.compte-resultat' => route('accounting.etats.compte-resultat'),
            'accounting.etats.flux-tresorerie' => route('accounting.etats.flux-tresorerie'),
            'accounting.etats.exports' => route('accounting.etats.exports'),
            'accounting.saisie.nouvelle' => route('accounting.saisie.nouvelle'),
            'accounting.saisie.ventes' => route('accounting.saisie.ventes'),
            'accounting.saisie.caisse' => route('accounting.saisie.caisse'),
            'accounting.saisie.import-releve' => route('accounting.saisie.import-releve'),
        ],
    ];
@endphp
<script>window.__DASHBOARD_ROUTES__ = @json($dashboardRoutes);</script>
<script type="module" src="{{ asset('assets/js/scripts/dashboard-comptable.js') }}"></script>
@endpush
