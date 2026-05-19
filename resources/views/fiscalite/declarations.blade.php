@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('fiscalite._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('fiscalite._filtres')

    <div class="card border-0 rounded-0 mb-3">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <p class="mb-0 text-muted">Génère les déclarations TVA (période) et IS (exercice) en brouillon.</p>
            <button type="button" class="btn btn-primary" :disabled="generating" @click="generer">
                <i class="ti ti-wand me-1"></i>
                <span v-if="generating">Génération…</span>
                <span v-else>Générer pour la période</span>
            </button>
        </div>
    </div>

    <div class="card border-0 rounded-0 mb-3" v-if="resultat">
        <div class="card-header"><h6 class="mb-0">Dernier calcul</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <strong>TVA</strong> — Collectée @{{ fmt(resultat.synthese.tva_collectee) }} / Déductible @{{ fmt(resultat.synthese.tva_deductible) }} / Nette @{{ fmt(resultat.synthese.tva_nette) }}
                </div>
                <div class="col-md-6">
                    <strong>IS</strong> — Base @{{ fmt(resultat.is_calcul.base_imposable) }} / Montant @{{ fmt(resultat.is_calcul.montant_is) }}
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 rounded-0">
        <div class="card-header"><h6 class="mb-0">Déclarations enregistrées</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th><th>Période</th><th>TVA coll.</th><th>TVA déd.</th><th>Impôt</th><th>Statut</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in declarations" :key="d.id">
                            <td>@{{ d.type }}</td>
                            <td>@{{ d.periode_debut }} → @{{ d.periode_fin }}</td>
                            <td class="text-end">@{{ fmt(d.tva_collectee) }}</td>
                            <td class="text-end">@{{ fmt(d.tva_deductible) }}</td>
                            <td class="text-end">@{{ fmt(d.montant_impot) }}</td>
                            <td><span class="badge" :class="statutClass(d.statut)">@{{ statutLabel(d.statut) }}</span></td>
                            <td>
                                <button v-if="d.statut !== 'deposee'" type="button" class="btn btn-sm btn-outline-success" @click="marquerDeposee(d)">Marquer déposée</button>
                            </td>
                        </tr>
                        <tr v-if="!declarations.length"><td colspan="7" class="text-center text-muted py-4">Aucune déclaration — générez une première période.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__FISCALITE_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/fiscalite/declarations.js') }}"></script>
@endpush
