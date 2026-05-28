@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('analytique._nav', ['active' => 'balance', 'title' => 'Balance analytique', 'breadcrumb' => 'Balance'])
    @include('analytique._filtres')

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="mb-0 fw-bold text-primary">Balance analytique</h5>
                <p class="mb-0 text-muted small">Synthèse des mouvements par axe et compte analytique.</p>
            </div>
            <div class="text-end" v-if="result?.items">
                <span class="badge bg-soft-info text-info px-3 py-2">@{{ result.items.length }} Lignes</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Axe analytique</th>
                            <th>Section / Compte</th>
                            <th class="text-end" style="width: 180px">Débit</th>
                            <th class="text-end" style="width: 180px">Crédit</th>
                            <th class="text-end" style="width: 180px">Solde net</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="5" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement…</td></tr>
                        <tr v-else-if="!result?.items?.length"><td colspan="5" class="text-center py-5 text-muted">Aucune donnée trouvée</td></tr>
                        <tr v-for="r in result.items" :key="r.section_id">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-soft-info text-info fw-bold me-2">@{{ r.axe_code }}</span>
                                    <span class="fw-medium">@{{ r.axe_libelle }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="font-monospace text-primary fw-bold me-2">@{{ r.section_code }}</span>
                                @{{ r.section_libelle }}
                            </td>
                            <td class="text-end">@{{ fmt(r.debit) }}</td>
                            <td class="text-end">@{{ fmt(r.credit) }}</td>
                            <td class="text-end fw-bold" :class="r.solde >= 0 ? 'text-success' : 'text-danger'">
                                @{{ fmt(Math.abs(r.solde)) }} @{{ r.solde >= 0 ? 'D' : 'C' }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-primary text-white fw-bold" v-if="result?.items?.length">
                        @php
                            $sumDebit = 'result.items.reduce((s,i) => s + (Number(i.debit)||0), 0)';
                            $sumCredit = 'result.items.reduce((s,i) => s + (Number(i.credit)||0), 0)';
                            $sumSolde = 'result.items.reduce((s,i) => s + (Number(i.solde)||0), 0)';
                        @endphp
                        <tr>
                            <td colspan="2" class="text-end px-4">TOTAL GÉNÉRAL (@{{ result.devise }})</td>
                            <td class="text-end">@{{ fmt({!! $sumDebit !!}) }}</td>
                            <td class="text-end">@{{ fmt({!! $sumCredit !!}) }}</td>
                            <td class="text-end">
                                @{{ fmt(Math.abs({!! $sumSolde !!})) }}
                                @{{ ({!! $sumSolde !!}) >= 0 ? 'D' : 'C' }}
                            </td>
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
<script type="module" src="{{ asset('assets/js/scripts/analytique/balance.js') }}"></script>
@endpush
