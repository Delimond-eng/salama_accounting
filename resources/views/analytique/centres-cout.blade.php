@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('analytique._nav', ['active' => 'centres-cout', 'title' => 'Analyse des centres de coût', 'breadcrumb' => 'Centres de coût'])
    @include('analytique._filtres')

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-primary">Dépenses par centre de coût</h5>
                <p class="mb-0 text-muted small">Consommation des charges (classe 6) par section analytique.</p>
            </div>
            <div class="text-end" v-if="result?.items">
                <span class="badge bg-soft-danger text-danger px-3 py-2">@{{ result.items.length }} Centres mouvementés</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Axe analytique</th>
                            <th>Centre / Section</th>
                            <th class="text-end" style="width: 250px">Total Dépenses (6x)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="3" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement…</td></tr>
                        <tr v-else-if="!result?.items?.length"><td colspan="3" class="text-center py-5 text-muted">Aucun mouvement trouvé</td></tr>
                        <tr v-for="r in result.items" :key="r.section_id">
                            <td>
                                <span class="badge bg-soft-info text-info fw-bold me-2">@{{ r.axe_code }}</span>
                                <span class="fw-medium">@{{ r.axe_libelle }}</span>
                            </td>
                            <td>
                                <span class="font-monospace text-primary fw-bold me-2">@{{ r.section_code }}</span>
                                @{{ r.section_libelle }}
                            </td>
                            <td class="text-end fw-bold text-danger">@{{ fmt(r.depenses) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-primary text-white fw-bold" v-if="result?.items?.length">
                        <tr>
                            <td colspan="2" class="text-end px-4">TOTAL DÉPENSES ANALYTIQUES (@{{ result.devise }})</td>
                            <td class="text-end">@{{ fmt(result.items.reduce((s,i) => s + (Number(i.depenses)||0), 0)) }}</td>
                        </tr>
                    </tfoot>
                </table>
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
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/analytique/centres-cout.js') }}"></script>
@endpush
