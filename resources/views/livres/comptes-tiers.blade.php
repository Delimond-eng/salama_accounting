@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('livres._nav', ['active' => 'comptes-tiers', 'title' => 'Comptes de Tiers', 'breadcrumb' => 'Comptes de Tiers'])
    @include('livres._filtres')

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-primary">Situation des comptes individuels</h5>
                <p class="mb-0 text-muted small">Synthèse des soldes par client, fournisseur et autre tiers.</p>
            </div>
            <div class="text-end" v-if="tiers.length">
                <span class="badge bg-soft-info text-info px-3 py-2">@{{ tiers.length }} Fiches Tiers</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 120px">Code Tiers</th>
                            <th>Nom / Raison Sociale</th>
                            <th>Type</th>
                            <th>Compte Collectif</th>
                            <th class="text-end" style="width: 180px">Solde Débiteur</th>
                            <th class="text-end" style="width: 180px">Solde Créditeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="6" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement des situations...</td></tr>
                        <tr v-else-if="!tiers.length"><td colspan="6" class="text-center py-5 text-muted">Aucun tiers mouvementé trouvé</td></tr>
                        <tr v-for="t in tiers" :key="t.id">
                            <td class="font-monospace fw-bold text-primary">@{{ t.code }}</td>
                            <td class="fw-medium text-dark">@{{ t.nom }}</td>
                            <td><span class="badge bg-label-secondary text-uppercase fs-10">@{{ labelType(t.type) }}</span></td>
                            <td class="font-monospace text-muted">@{{ t.num_compte_collectif }}</td>
                            <td class="text-end fw-bold" :class="t.solde_fin_debiteur > 0 ? 'text-dark' : 'text-light-soft'">@{{ t.solde_fin_debiteur > 0 ? fmt(t.solde_fin_debiteur) : '0,00' }}</td>
                            <td class="text-end fw-bold" :class="t.solde_fin_crediteur > 0 ? 'text-dark' : 'text-light-soft'">@{{ t.solde_fin_crediteur > 0 ? fmt(t.solde_fin_crediteur) : '0,00' }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light text-dark fw-bold" v-if="tiers.length">
                        <tr>
                            <td colspan="4" class="text-end px-4 text-uppercase">Total Général des Tiers</td>
                            <td class="text-end">@{{ fmt(tiers.reduce((s,t) => s + (Number(t.solde_fin_debiteur)||0), 0)) }}</td>
                            <td class="text-end">@{{ fmt(tiers.reduce((s,t) => s + (Number(t.solde_fin_crediteur)||0), 0)) }}</td>
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
    .text-light-soft { color: #cbd5e1; }
    .bg-label-secondary { background-color: #ebeef0; color: #8592a3; }
</style>
@endpush

@push('scripts')
<script>window.__LIVRES_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/livres/comptes-tiers.js') }}"></script>
@endpush
