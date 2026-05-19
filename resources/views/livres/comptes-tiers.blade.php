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
            <h5 class="mb-0">Situation des comptes de tiers</h5>
            <button type="button" class="btn btn-sm btn-outline-light" @click="loadData"><i class="ti ti-refresh"></i></button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th><th>Nom</th><th>Type</th><th>Compte collectif</th>
                            <th class="text-end">Solde débiteur</th><th class="text-end">Solde créditeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="6" class="text-center py-4">Chargement…</td></tr>
                        <tr v-for="t in tiers" :key="t.id">
                            <td>@{{ t.code }}</td>
                            <td>@{{ t.nom }}</td>
                            <td>@{{ labelType(t.type) }}</td>
                            <td>@{{ t.num_compte_collectif }}</td>
                            <td class="text-end">@{{ fmt(t.solde_fin_debiteur) }}</td>
                            <td class="text-end">@{{ fmt(t.solde_fin_crediteur) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    </template>
</div>
@endsection
@push('scripts')
<script>window.__LIVRES_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/livres/comptes-tiers.js') }}"></script>
@endpush
