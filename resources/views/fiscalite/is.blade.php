@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('fiscalite._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('fiscalite._filtres')

    <div class="card border-0 rounded-0" v-if="data">
        <div class="card-header"><h5 class="mb-0">Impôt sur les sociétés — @{{ config.taux_is }}%</h5></div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <table class="table table-borderless mb-0">
                        <tr><td class="text-muted">Résultat comptable (XG)</td><td class="text-end fw-medium">@{{ fmt(data.resultat_comptable) }} @{{ data.devise }}</td></tr>
                        <tr><td class="text-muted">Base imposable</td><td class="text-end">@{{ fmt(data.base_imposable) }}</td></tr>
                        <tr><td class="text-muted">Taux IS</td><td class="text-end">@{{ data.taux_is }} %</td></tr>
                        <tr class="border-top"><td class="fw-bold">Montant IS estimé</td><td class="text-end fw-bold text-primary fs-18">@{{ fmt(data.montant_is) }} @{{ data.devise }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-light border mb-0">
                        <p class="mb-1 small text-muted">Le calcul s'appuie sur la ligne <strong>XG</strong> du compte de résultat SYSCOHADA.</p>
                        <p class="mb-0 small text-muted">Seul un résultat bénéficiaire est imposé (base ≥ 0).</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-else-if="!isLoading" class="alert alert-info">Aucune donnée — validez les écritures et le compte de résultat.</div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__FISCALITE_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/fiscalite/is.js') }}"></script>
@endpush
