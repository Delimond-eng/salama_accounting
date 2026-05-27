@extends('layouts.app')

@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('etats._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('etats._filtres')

    <div v-if="error && error.length" class="alert alert-danger shadow-sm border-0">
        <div v-for="(msg, i) in error" :key="i"><i class="ti ti-alert-circle me-2"></i>@{{ msg }}</div>
    </div>

    <div v-if="data && data.validation && !data.equilibre" class="alert alert-warning shadow-sm border-0 d-flex align-items-center">
        <i class="ti ti-alert-triangle fs-3 me-3"></i>
        <div>
            <strong>@{{ data.validation.message }}</strong>
            <span v-if="data.ecart != null" class="ms-2">(écart : @{{ fmt(data.ecart) }})</span>
        </div>
    </div>
    <div v-else-if="data && data.equilibre" class="alert alert-success border border-success shadow-sm py-2 d-flex align-items-center">
        <i class="ti ti-checks fs-3 me-3"></i>
        <span>Bilan équilibré : Actif = Passif (capitaux propres et dettes).</span>
    </div>

    <div class="card border-0 shadow-sm mb-4" v-if="data">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="mb-0 text-primary fw-bold">@{{ data.titre }}</h4>
                <p class="text-muted mb-0"><i class="ti ti-calendar me-1"></i>Au @{{ filtres.date_arrete }} — @{{ data.devise }}</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="stats-badge bg-soft-info text-info">
                    <small>Total Actif</small>
                    <strong>@{{ fmt(data.total_actif) }}</strong>
                </div>
                <div class="stats-badge bg-soft-danger text-danger">
                    <small>Total Passif</small>
                    <strong>@{{ fmt(data.total_passif) }}</strong>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="row g-0">
                <!-- ACTIF -->
                <div class="col-lg-6 border-end">
                    <div class="p-3 border-bottom bg-light-subtle d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-uppercase letter-spacing-1">Actif</h6>
                        <span class="badge bg-primary rounded-pill">@{{ data.actif.length }} lignes</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-etats mb-0">
                            <thead>
                                <tr>
                                    <th>Libellé</th>
                                    <th class="text-end" style="width: 150px">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(ligne, idx) in data.actif" :key="'a'+idx" :class="rowClass(ligne)">
                                    <td>
                                        <span v-if="ligne.num_compte" class="text-muted fs-11 d-block">@{{ ligne.num_compte }}</span>
                                        <span :class="{'ps-3': !isTitre(ligne) && !isTotal(ligne)}">@{{ ligne.libelle }}</span>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        @{{ isTitre(ligne) ? '' : fmt(ligne.net_n) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- PASSIF -->
                <div class="col-lg-6">
                    <div class="p-3 border-bottom bg-light-subtle d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-uppercase letter-spacing-1">Passif (CP & Dettes)</h6>
                        <span class="badge bg-success rounded-pill">@{{ data.passif.length }} lignes</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-etats mb-0">
                            <thead>
                                <tr>
                                    <th>Libellé</th>
                                    <th class="text-end" style="width: 150px">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(ligne, idx) in data.passif" :key="'p'+idx" :class="rowClass(ligne)">
                                    <td>
                                        <span v-if="ligne.num_compte" class="text-muted fs-11 d-block">@{{ ligne.num_compte }}</span>
                                        <span :class="{'ps-3': !isTitre(ligne) && !isTotal(ligne)}">@{{ ligne.libelle }}</span>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        @{{ isTitre(ligne) ? '' : fmt(ligne.net_n) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-else-if="!isLoading && !data" class="alert alert-info border-0 shadow-sm">
        <i class="ti ti-info-circle me-2"></i>Aucune donnée disponible pour cette période.
    </div>

    </template>
</div>
@endsection

@push('styles')
<style>
    .letter-spacing-1 { letter-spacing: 1px; }
    .stats-badge {
        padding: 8px 15px;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        line-height: 1.2;
        min-width: 140px;
    }
    .stats-badge small { font-size: 10px; text-transform: uppercase; opacity: 0.8; margin-bottom: 2px; }
    .stats-badge strong { font-size: 15px; }

    .table-etats thead th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 10px;
        letter-spacing: 0.5px;
        font-weight: 700;
        padding: 10px 15px;
        border-bottom: 2px solid #dee2e6;
        color: #64748b;
    }
    .table-etats tbody td {
        padding: 8px 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
    }
    .row-titre td {
        background-color: #f8fafc !important;
        color: #334155;
        font-weight: 700 !important;
        font-size: 12px !important;
    }
    .row-formule td {
        background-color: #f0f7ff !important;
        color: #0f172a !important;
        border-top: 2px solid #3b82f6 !important;
        border-bottom: 2px solid #3b82f6 !important;
        font-weight: 700 !important;
    }
</style>
@endpush

@push('scripts')
<script>window.__ETATS_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/etats/bilan.js') }}"></script>
@endpush
