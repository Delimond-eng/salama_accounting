@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('etats._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('etats._filtres')

    <div v-if="data" class="row g-4 mb-4">
        <!-- Card: Total Actif (Style Sales Dashboard MTD) -->
        <div class="col-md-6 col-xxl-4">
            <div class="bg-secondary rounded-4 rounded-end-5 d-flex h-100 shadow-sm border-0">
                <div class="ps-3 d-flex align-items-center justify-content-center position-relative pe-2 z-1">
                    <p class="fs-16 fw-medium text-white mb-0 z-2">ACTIF</p>
                    <span class="arrow-icon arrow-warning d-block position-absolute"></span>
                </div>
                <div class="bg-white rounded-4 w-100 p-3">
                    <p class="text-dark mb-2 fw-medium">Total Actif (N)</p>
                    <h3 class="mb-4 fw-bold">@{{ fmt(data.resume?.total_actif_n) }} <small class="text-muted fs-6">@{{ filtres.devise_affichage }}</small></h3>
                    <div class="d-flex align-items-center justify-content-between gap-1 flex-wrap">
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <span v-if="data.resume?.total_actif_n1" class="badge badge-pill rounded-pill border-0" :class="data.resume.total_actif_n >= data.resume.total_actif_n1 ? 'badge-soft-success' : 'badge-soft-danger'">
                                @{{ data.resume.total_actif_n >= data.resume.total_actif_n1 ? '+' : '' }}@{{ (((data.resume.total_actif_n / data.resume.total_actif_n1) - 1) * 100).toFixed(1) }}%
                            </span>
                            <p class="mb-0 text-muted small">vs N-1 (@{{ fmt(data.resume?.total_actif_n1) }})</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Résultat Net (Style Sales Dashboard YTD - using primary/blue for variety) -->
        <div class="col-md-6 col-xxl-4">
            <div class="bg-primary rounded-4 rounded-end-5 d-flex h-100 shadow-sm border-0">
                <div class="ps-3 d-flex align-items-center justify-content-center position-relative pe-2 z-1">
                    <p class="fs-16 fw-medium text-white mb-0 z-2">NET</p>
                    <span class="arrow-icon arrow-primary d-block position-absolute"></span>
                </div>
                <div class="bg-white rounded-4 w-100 p-3">
                    <p class="text-dark mb-2 fw-medium">Résultat Net (N)</p>
                    <h3 class="mb-4 fw-bold" :class="data.resume?.resultat_net_n < 0 ? 'text-danger' : 'text-dark'">@{{ fmt(data.resume?.resultat_net_n) }}</h3>
                    <div class="d-flex align-items-center justify-content-between gap-1 flex-wrap">
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <span v-if="data.resume?.resultat_net_n1" class="badge badge-pill rounded-pill border-0" :class="data.resume.resultat_net_n >= data.resume.resultat_net_n1 ? 'badge-soft-success' : 'badge-soft-danger'">
                                @{{ data.resume.resultat_net_n >= data.resume.resultat_net_n1 ? '+' : '' }}@{{ (((data.resume.resultat_net_n / data.resume.resultat_net_n1) - 1) * 100).toFixed(1) }}%
                            </span>
                            <p class="mb-0 text-muted small">vs N-1 (@{{ fmt(data.resume?.resultat_net_n1) }})</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Ratio Rentabilité (Style custom based on template) -->
        <div class="col-md-6 col-xxl-4">
            <div class="bg-danger rounded-4 rounded-end-5 d-flex h-100 shadow-sm border-0">
                <div class="ps-3 d-flex align-items-center justify-content-center position-relative pe-2 z-1">
                    <p class="fs-16 fw-medium text-white mb-0 z-2">RATIO</p>
                    <span class="arrow-icon arrow-danger d-block position-absolute"></span>
                </div>
                <div class="bg-white rounded-4 w-100 p-3">
                    <p class="text-dark mb-2 fw-medium">Rentabilité Actif</p>
                    <h3 class="mb-4 fw-bold">@{{ data.resume?.resultat_net_n && data.resume?.total_actif_n ? ((data.resume.resultat_net_n / data.resume.total_actif_n) * 100).toFixed(2) + '%' : '—' }}</h3>
                    <div class="d-flex align-items-center justify-content-between gap-1 flex-wrap">
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <i class="ti ti-info-circle text-muted"></i>
                            <p class="mb-0 text-muted small">Performance de l'exercice</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="data" class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold">Détail des rubriques</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3" style="width: 45%">Poste</th>
                                    <th class="text-end py-3">Net N (@{{ filtres.devise_affichage }})</th>
                                    <th class="text-end py-3">Net N-1 (@{{ filtres.devise_affichage }})</th>
                                    <th class="text-end py-3 pe-4">Variation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(l, i) in data.bilan.actif" :key="'a'+i">
                                    <tr v-if="!isTitre(l) && (l.net_n || l.net_n1)" :class="rowClass(l)">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <span v-if="l.ref" class="badge bg-label-info fs-11">@{{ l.ref }}</span>
                                                <span :class="{'ps-3': !isTotal(l) && !l.ref}">@{{ l.libelle }}</span>
                                            </div>
                                        </td>
                                        <td class="text-end font-monospace">@{{ fmt(l.net_n) }}</td>
                                        <td class="text-end font-monospace text-muted">@{{ fmt(l.net_n1) }}</td>
                                        <td class="text-end pe-4">
                                            <span v-if="l.net_n1" :class="l.net_n >= l.net_n1 ? 'text-success' : 'text-danger'" class="fw-medium">
                                                <i :class="l.net_n >= l.net_n1 ? 'ti ti-trending-up' : 'ti ti-trending-down'" class="me-1"></i>
                                                @{{ Math.abs(((l.net_n / l.net_n1) - 1) * 100).toFixed(1) }}%
                                            </span>
                                            <span v-else class="text-muted small">—</span>
                                        </td>
                                    </tr>
                                    <tr v-else-if="isTitre(l)" class="bg-light-soft">
                                        <td colspan="4" class="ps-4 py-2 fw-bold text-uppercase fs-12 text-secondary">
                                            <div class="d-flex align-items-center gap-2">
                                                <span v-if="l.ref" class="badge bg-info text-white fs-10">@{{ l.ref }}</span>
                                                @{{ l.libelle }}
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </template>
</div>

<style>
    /* Reproducing Sales Dashboard Card styles */
    .rounded-4 { border-radius: 1rem !important; }
    .rounded-end-5 { border-top-right-radius: 2.5rem !important; border-bottom-right-radius: 2.5rem !important; }
    .arrow-icon {
        right: -10px;
        top: 50%;
        transform: translateY(-50%);
        border-top: 10px solid transparent;
        border-bottom: 10px solid transparent;
        border-left: 10px solid #6c757d; /* secondary color */
    }
    .arrow-primary { border-left-color: #696cff; }
    .arrow-warning { border-left-color: #fea306; }
    .arrow-danger { border-left-color: #ee2222; }
    .badge-soft-success { background-color: #e8fadf; color: #71dd37; }
    .badge-soft-danger { background-color: #ffe5e5; color: #ff3e1d; }
    .bg-light-soft { background-color: #f8f9fa; }
    .row-formule { background-color: rgba(105, 108, 255, 0.05) !important; color: #696cff !important; }
    .bg-label-info { background-color: #d7f5fc !important; color: #03c3ec !important; }
    .fs-11 { font-size: 11px !important; }
    .fs-10 { font-size: 10px !important; }
</style>
@endsection

@push('scripts')
<script>window.__ETATS_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/etats/comparatif.js') }}"></script>
@endpush
