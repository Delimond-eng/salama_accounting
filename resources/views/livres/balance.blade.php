@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('livres._nav', ['active' => 'balance', 'title' => 'Balance Générale', 'breadcrumb' => 'Balance'])
    @include('livres._filtres')

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="mb-0 fw-bold text-primary">Balance de vérification</h5>
                <p class="mb-0 text-muted small">Synthèse des mouvements et soldes par compte sur la période.</p>
            </div>
            <div class="text-end" v-if="totaux">
                <span class="badge bg-soft-info text-info px-3 py-2">@{{ lignes.length }} Comptes</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0 balance-syscohada">
                    <thead class="bg-light text-center text-uppercase fs-10 fw-bold">
                        <tr>
                            <th rowspan="2" class="text-start align-middle" style="width: 110px">N° Compte</th>
                            <th rowspan="2" class="text-start align-middle">Intitulé du compte</th>
                            <th colspan="2" class="border-bottom-0">Soldes Ouverture</th>
                            <th colspan="2" class="border-bottom-0">Mouvements Période</th>
                            <th colspan="2" class="border-bottom-0">Soldes Clôture</th>
                        </tr>
                        <tr>
                            <th style="width: 130px">Débiteur</th>
                            <th style="width: 130px">Créditeur</th>
                            <th style="width: 130px">Débit</th>
                            <th style="width: 130px">Crédit</th>
                            <th style="width: 130px">Débiteur</th>
                            <th style="width: 130px">Créditeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="8" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement des données...</td></tr>
                        <tr v-else-if="!lignes.length"><td colspan="8" class="text-center py-5 text-muted">Aucun mouvement trouvé pour les critères sélectionnés</td></tr>
                        <tr v-for="r in lignes" :key="r.num_compte">
                            <td class="font-monospace fw-bold text-primary px-3">@{{ r.num_compte }}</td>
                            <td class="fw-medium text-dark">@{{ r.libelle }}</td>
                            <td class="text-end text-muted">@{{ fmt(r.solde_debut_debiteur) }}</td>
                            <td class="text-end text-muted">@{{ fmt(r.solde_debut_crediteur) }}</td>
                            <td class="text-end fw-semibold">@{{ fmt(r.mouvement_debit) }}</td>
                            <td class="text-end fw-semibold">@{{ fmt(r.mouvement_credit) }}</td>
                            <td class="text-end fw-bold bg-light-soft">@{{ fmt(r.solde_fin_debiteur) }}</td>
                            <td class="text-end fw-bold bg-light-soft">@{{ fmt(r.solde_fin_crediteur) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-primary text-white fw-bold" v-if="totaux && lignes.length">
                        <tr>
                            <td colspan="2" class="text-end px-3">TOTAL GÉNÉRAL (@{{ filtres.devise_affichage }})</td>
                            <td class="text-end">@{{ fmt(totaux.solde_debut_debiteur) }}</td>
                            <td class="text-end">@{{ fmt(totaux.solde_debut_crediteur) }}</td>
                            <td class="text-end">@{{ fmt(totaux.mouvement_debit) }}</td>
                            <td class="text-end">@{{ fmt(totaux.mouvement_credit) }}</td>
                            <td class="text-end">@{{ fmt(totaux.solde_fin_debiteur) }}</td>
                            <td class="text-end">@{{ fmt(totaux.solde_fin_crediteur) }}</td>
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
        padding: 8px 10px;
        color: #475569;
        background-color: #f8f9fa;
        font-size: 10px !important;
        letter-spacing: 0.3px;
    }
    .table-custom tbody td {
        padding: 10px 12px;
        vertical-align: middle;
        font-size: 13px;
        border-bottom: 1px solid #f1f5f9;
    }
    .bg-light-soft { background-color: rgba(248, 249, 250, 0.8); }
    .balance-syscohada tfoot td { border: none; padding: 12px; font-size: 14px; }
</style>
@endpush

@push('scripts')
<script>window.__LIVRES_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/livres/balance.js') }}"></script>
@endpush
