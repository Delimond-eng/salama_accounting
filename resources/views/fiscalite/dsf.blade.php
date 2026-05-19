@extends('layouts.app')
@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('fiscalite._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('fiscalite._filtres')

    <div class="row g-3" v-if="data">
        <div class="col-md-6">
            <div class="card border-0 h-100">
                <div class="card-header"><h6 class="mb-0">Synthèse bilan & résultat</h6></div>
                <div class="card-body">
                    <p class="mb-2"><strong>Exercice :</strong> @{{ data.exercice }}</p>
                    <p class="mb-2"><strong>Date d'arrêté :</strong> @{{ data.date_arrete }}</p>
                    <table class="table table-sm mb-0">
                        <tr><td>Total actif (TA)</td><td class="text-end">@{{ fmt(data.bilan_total_actif) }}</td></tr>
                        <tr><td>Total passif (TP)</td><td class="text-end">@{{ fmt(data.bilan_total_passif) }}</td></tr>
                        <tr><td>Total capitaux propres (TPE)</td><td class="text-end">@{{ fmt(data.bilan_total_capitaux_propres) }}</td></tr>
                        <tr class="fw-bold"><td>Passif + capitaux propres</td><td class="text-end">@{{ fmt(data.bilan_total_passif_et_equity) }}</td></tr>
                        <tr><td>Chiffre d'affaires (XB)</td><td class="text-end">@{{ fmt(data.chiffre_affaires) }}</td></tr>
                        <tr class="fw-bold"><td>Résultat net (XI)</td><td class="text-end">@{{ fmt(data.resultat_net) }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 h-100">
                <div class="card-header"><h6 class="mb-0">TVA exercice (@{{ data.devise }})</h6></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td>TVA collectée</td><td class="text-end">@{{ fmt(data.tva.tva_collectee) }}</td></tr>
                        <tr><td>TVA déductible</td><td class="text-end">@{{ fmt(data.tva.tva_deductible) }}</td></tr>
                        <tr class="fw-bold"><td>TVA nette</td><td class="text-end">@{{ fmt(data.tva.tva_nette) }}</td></tr>
                        <tr v-if="data.tva.tva_a_payer > 0"><td class="text-danger">TVA à payer</td><td class="text-end text-danger">@{{ fmt(data.tva.tva_a_payer) }}</td></tr>
                        <tr v-if="data.tva.credit_tva > 0"><td class="text-success">Crédit TVA</td><td class="text-end text-success">@{{ fmt(data.tva.credit_tva) }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div v-else-if="!isLoading" class="alert alert-info">Chargez les états financiers et écritures validées pour générer la synthèse DSF.</div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__FISCALITE_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/fiscalite/dsf.js') }}"></script>
@endpush
