@extends('layouts.app')
@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('livres._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('livres._filtres')

    <div class="card border-0 rounded-0">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">Balance de vérification</h5>
            <button type="button" class="btn btn-sm btn-outline-light" @click="loadData"><i class="ti ti-refresh"></i></button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-nowrap mb-0 balance-syscohada">
                    <thead class="table-light text-center">
                        <tr>
                            <th rowspan="2" class="text-start">N° comptes</th>
                            <th rowspan="2" class="text-start">Intitulés</th>
                            <th colspan="2">Soldes début</th>
                            <th colspan="2">Mouvements</th>
                            <th colspan="2">Soldes fin</th>
                        </tr>
                        <tr>
                            <th>Débiteurs</th><th>Créditeurs</th>
                            <th>Débit</th><th>Crédit</th>
                            <th>Débiteurs</th><th>Créditeurs</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="8" class="text-center py-4">Chargement…</td></tr>
                        <tr v-for="r in lignes" :key="r.num_compte">
                            <td>@{{ r.num_compte }}</td>
                            <td>@{{ r.libelle }}</td>
                            <td class="text-end">@{{ fmt(r.solde_debut_debiteur) }}</td>
                            <td class="text-end">@{{ fmt(r.solde_debut_crediteur) }}</td>
                            <td class="text-end">@{{ fmt(r.mouvement_debit) }}</td>
                            <td class="text-end">@{{ fmt(r.mouvement_credit) }}</td>
                            <td class="text-end">@{{ fmt(r.solde_fin_debiteur) }}</td>
                            <td class="text-end">@{{ fmt(r.solde_fin_crediteur) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-semibold" v-if="totaux">
                        <tr>
                            <td colspan="2" class="text-end">TOTAUX</td>
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
@push('scripts')
<script>window.__LIVRES_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/livres/balance.js') }}"></script>
@endpush
