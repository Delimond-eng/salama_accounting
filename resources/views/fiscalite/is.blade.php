@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('fiscalite._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('fiscalite._filtres')

    <div class="card border-0 shadow-sm" v-if="data">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0 text-primary fw-bold">Impôt sur les Sociétés (IS)</h4>
                <p class="text-muted mb-0 small"><i class="ti ti-info-circle me-1"></i>Estimation basée sur le résultat comptable</p>
            </div>
            <div class="text-end">
                <span class="badge bg-soft-primary text-primary fs-14 px-3 py-2">Taux : @{{ data.taux_is }}%</span>
            </div>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <div class="table-responsive">
                        <table class="table table-sm table-fiscalite-summary mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted py-3">Résultat comptable (XG)</td>
                                    <td class="text-end fw-medium py-3">@{{ fmt(data.resultat_comptable) }} @{{ data.devise }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted py-3">Base imposable</td>
                                    <td class="text-end fw-medium py-3">@{{ fmt(data.base_imposable) }}</td>
                                </tr>
                                <tr class="bg-primary-soft border-top border-primary border-opacity-10">
                                    <td class="text-primary fw-bold py-3">MONTANT IS ESTIMÉ</td>
                                    <td class="text-end text-primary fw-bold py-3 fs-18">@{{ fmt(data.montant_is) }} @{{ data.devise }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="p-4 bg-light rounded-4 border border-dashed h-100 d-flex flex-column justify-content-center">
                        <h6 class="fw-bold mb-3"><i class="ti ti-help-circle text-primary me-2"></i>Note de calcul</h6>
                        <ul class="list-unstyled mb-0 d-grid gap-2">
                            <li class="d-flex align-items-start gap-2 fs-13">
                                <i class="ti ti-check text-success mt-1"></i>
                                <span>Le calcul s'appuie sur la ligne <strong>XG</strong> du compte de résultat SYSCOHADA.</span>
                            </li>
                            <li class="d-flex align-items-start gap-2 fs-13">
                                <i class="ti ti-check text-success mt-1"></i>
                                <span>Seul un résultat bénéficiaire est imposé (base ≥ 0).</span>
                            </li>
                            <li class="d-flex align-items-start gap-2 fs-13">
                                <i class="ti ti-alert-circle text-warning mt-1"></i>
                                <span>Cette estimation ne prend pas en compte les réintégrations et déductions fiscales extracomptables.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-else-if="!isLoading" class="alert alert-info shadow-sm border-0">
        <div class="d-flex align-items-center">
            <i class="ti ti-info-circle fs-24 me-2"></i>
            <div>Aucune donnée — validez les écritures et générez le compte de résultat pour estimer l'IS.</div>
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
        font-size: 14px;
    }
    .bg-primary-soft { background-color: rgba(63, 122, 253, 0.05); }
    .rounded-4 { border-radius: 1rem !important; }
</style>
@endpush

@push('scripts')
<script>window.__FISCALITE_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/fiscalite/is.js') }}"></script>
@endpush
