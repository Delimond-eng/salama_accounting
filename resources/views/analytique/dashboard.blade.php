@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('analytique._nav', ['active' => 'dashboard', 'title' => 'Pilotage analytique', 'breadcrumb' => 'Tableau de bord'])
    @include('analytique._filtres')

    <div v-if="result">
        <h5 class="mb-3 fw-bold text-dark">Top Projets (par charges)</h5>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="border rounded bg-white">
                    <div class="row g-0">
                        <div v-for="(p, idx) in result.top_projets_couteux" :key="p.section_id"
                             class="col-md-3 d-flex border-bottom"
                             :class="{
                                 'border-end-md': (idx + 1) % 4 !== 0,
                                 'border-bottom-0': idx >= result.top_projets_couteux.length - (result.top_projets_couteux.length % 4 || 4)
                             }">
                            <div class="p-3 card-hover text-center mb-0 flex-fill">
                                <p class="mb-1 text-muted fs-11 text-uppercase fw-medium">@{{ p.code }}</p>
                                <h6 class="mb-2 fw-bold text-truncate mx-auto" style="max-width: 180px;" :title="p.libelle">@{{ p.libelle }}</h6>
                                <h5 class="mb-3 text-danger">@{{ fmt(p.charges) }}</h5>
                                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                    <span class="d-inline-flex align-items-center badge rounded-pill border-0"
                                          :class="p.resultat >= 0 ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger'">
                                        @{{ p.resultat >= 0 ? '+' : '' }}@{{ fmt(p.resultat) }}
                                    </span>
                                    <p class="text-dark mb-0 fs-12">Résultat</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-primary">Récapitulatif de rentabilité</h5>
                        <span class="badge bg-soft-info text-info" v-if="result.rentabilite?.projets">@{{ result.rentabilite.projets.length }} Sections</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th>Section / Projet</th>
                                        <th class="text-end" style="width: 180px">Produits (7x)</th>
                                        <th class="text-end" style="width: 180px">Charges (6x)</th>
                                        <th class="text-end" style="width: 180px">Résultat Net</th>
                                        <th class="text-center" style="width: 120px">Marge %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="p in result.rentabilite?.projets" :key="p.section_id">
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold text-dark">@{{ p.libelle }}</span>
                                                <small class="text-muted font-monospace">@{{ p.code }}</small>
                                            </div>
                                        </td>
                                        <td class="text-end text-success fw-medium">@{{ fmt(p.produits) }}</td>
                                        <td class="text-end text-danger fw-medium">@{{ fmt(p.charges) }}</td>
                                        <td class="text-end fw-bold" :class="p.resultat >= 0 ? 'text-success' : 'text-danger'">
                                            @{{ fmt(p.resultat) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge" :class="(Number(p.produits) > 0 ? (Number(p.resultat) / Number(p.produits)) : 0) >= 0 ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger'">
                                                @{{ (Number(p.produits) > 0 ? (Number(p.resultat) / Number(p.produits) * 100) : 0).toFixed(1) }} %
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-primary text-white fw-bold">
                                    <tr>
                                        <td class="text-end px-4">TOTAL GÉNÉRAL</td>
                                        <td class="text-end">@{{ fmt(result.rentabilite?.projets.reduce((s,p) => s + (Number(p.produits)||0), 0)) }}</td>
                                        <td class="text-end">@{{ fmt(result.rentabilite?.projets.reduce((s,p) => s + (Number(p.charges)||0), 0)) }}</td>
                                        <td class="text-end">@{{ fmt(result.rentabilite?.projets.reduce((s,p) => s + (Number(p.resultat)||0), 0)) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
    .bg-soft-primary { background-color: #eef4ff !important; }
    .bg-soft-success { background-color: #e6fffa !important; }
    .bg-soft-info { background-color: #e6faff !important; }
    .bg-soft-warning { background-color: #fff9e6 !important; }
    .bg-soft-danger { background-color: #ffe6e6 !important; }

    .card-hover:hover {
        background-color: #f8f9fa;
        transition: background-color 0.3s ease;
    }

    @media (min-width: 768px) {
        .border-end-md {
            border-right: 1px solid #dee2e6 !important;
        }
    }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/analytique/dashboard.js') }}"></script>
@endpush
