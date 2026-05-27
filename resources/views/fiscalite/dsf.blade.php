@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('fiscalite._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('fiscalite._filtres')

    <div class="row g-4" v-if="data">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary">Synthèse Bilan & Résultat</h5>
                    <span class="badge bg-soft-info text-info">@{{ data.exercice }}</span>
                </div>
                <div class="card-body px-4 pb-4">
                    <p class="text-muted small mb-4"><i class="ti ti-calendar-event me-1"></i>Arrêté au @{{ data.date_arrete }}</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-fiscalite-summary">
                            <tbody>
                                <tr>
                                    <td class="text-muted">Total Actif (TA)</td>
                                    <td class="text-end fw-medium">@{{ fmt(data.bilan_total_actif) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Total Passif (TP)</td>
                                    <td class="text-end fw-medium">@{{ fmt(data.bilan_total_passif) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Total Capitaux Propres (TPE)</td>
                                    <td class="text-end fw-medium">@{{ fmt(data.bilan_total_capitaux_propres) }}</td>
                                </tr>
                                <tr class="bg-light-soft fw-bold border-top border-bottom">
                                    <td class="text-dark">Passif + Capitaux Propres</td>
                                    <td class="text-end text-dark">@{{ fmt(data.bilan_total_passif_et_equity) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Chiffre d'Affaires (XB)</td>
                                    <td class="text-end fw-medium">@{{ fmt(data.chiffre_affaires) }}</td>
                                </tr>
                                <tr class="table-primary-soft fw-bold border-top">
                                    <td class="text-primary">Résultat Net (XI)</td>
                                    <td class="text-end text-primary fs-15">@{{ fmt(data.resultat_net) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="mb-0 fw-bold text-success">Synthèse TVA Exercice</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <p class="text-muted small mb-4">Montants exprimés en <strong>@{{ data.devise }}</strong></p>
                    <div class="table-responsive">
                        <table class="table table-sm table-fiscalite-summary">
                            <tbody>
                                <tr>
                                    <td class="text-muted">TVA Collectée</td>
                                    <td class="text-end fw-medium">@{{ fmt(data.tva.tva_collectee) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">TVA Déductible</td>
                                    <td class="text-end fw-medium">@{{ fmt(data.tva.tva_deductible) }}</td>
                                </tr>
                                <tr class="bg-light-soft fw-bold border-top">
                                    <td class="text-dark">TVA Nette</td>
                                    <td class="text-end text-dark">@{{ fmt(data.tva.tva_nette) }}</td>
                                </tr>
                                <tr v-if="data.tva.tva_a_payer > 0" class="table-danger-soft">
                                    <td class="text-danger fw-bold">TVA à payer</td>
                                    <td class="text-end text-danger fw-bold">@{{ fmt(data.tva.tva_a_payer) }}</td>
                                </tr>
                                <tr v-if="data.tva.credit_tva > 0" class="table-success-soft">
                                    <td class="text-success fw-bold">Crédit TVA</td>
                                    <td class="text-end text-success fw-bold">@{{ fmt(data.tva.credit_tva) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded-3 border">
                        <div class="d-flex align-items-center gap-2 small text-muted">
                            <i class="ti ti-info-circle fs-16"></i>
                            <span>Les données de cette synthèse sont extraites automatiquement des journaux validés.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-else-if="!isLoading" class="alert alert-info shadow-sm border-0">
        <div class="d-flex align-items-center">
            <i class="ti ti-info-circle fs-24 me-2"></i>
            <div>Chargez les états financiers et écritures validées pour générer la synthèse DSF.</div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('styles')
<style>
    .table-fiscalite-summary tbody td {
        padding: 12px 10px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13.5px;
    }
    .bg-light-soft { background-color: #f8f9fa; }
    .table-primary-soft { background-color: rgba(63, 122, 253, 0.05); }
    .table-danger-soft { background-color: rgba(231, 13, 13, 0.05); }
    .table-success-soft { background-color: rgba(3, 201, 90, 0.05); }
    .fs-15 { font-size: 15px !important; }
</style>
@endpush

@push('scripts')
<script>window.__FISCALITE_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/fiscalite/dsf.js') }}"></script>
@endpush
