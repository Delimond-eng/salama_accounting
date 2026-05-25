@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('etats._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('etats._filtres')

    <div v-if="error && error.length" class="alert alert-danger">
        <div v-for="(msg, i) in error" :key="i">@{{ msg }}</div>
    </div>

    <div v-if="data && data.validation && !data.equilibre" class="alert alert-danger">
        <strong>@{{ data.validation.message }}</strong>
        <span v-if="data.ecart != null" class="ms-2">(écart : @{{ fmt(data.ecart) }})</span>
    </div>
    <div v-else-if="data && data.equilibre" class="alert alert-success py-2">
        Bilan équilibré : Actif = Passif (capitaux propres et dettes).
    </div>

    <div class="card border-0 rounded-0 mb-3" v-if="data && data.debug && data.debug.comptes_non_affectes?.length">
        <div class="card-header"><h6 class="mb-0">Comptes non affectés</h6></div>
        <div class="card-body p-0">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light"><tr><th>Compte</th><th>Libellé</th><th class="text-end">Solde</th></tr></thead>
                <tbody>
                    <tr v-for="(c,i) in data.debug.comptes_non_affectes" :key="i" class="table-warning">
                        <td><code>@{{ c.num_compte }}</code></td>
                        <td>@{{ c.libelle }}</td>
                        <td class="text-end">@{{ fmt(c.balance) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 rounded-0" v-if="data">
        <div class="card-header d-flex justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-0">@{{ data.titre }}</h5>
                <span class="text-muted fs-12">Au @{{ filtres.date_arrete }} — @{{ data.devise }}</span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-primary">Actif : @{{ fmt(data.total_actif) }}</span>
                <span class="badge bg-success">Passif : @{{ fmt(data.total_passif) }}</span>
                <span class="badge bg-secondary" v-if="data.total_capitaux_propres != null">dont CP : @{{ fmt(data.total_capitaux_propres) }}</span>
                <span v-if="data.resultat_exercice != null" class="badge bg-dark">Résultat 6–7 : @{{ fmt(data.resultat_exercice) }}</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="row g-0">
                <div class="col-lg-6 border-end">
                    <div class="p-3 border-bottom bg-light"><h6 class="mb-0 text-uppercase">Actif</h6></div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr><th>Libellé</th><th class="text-end">Montant</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="(ligne, idx) in data.actif" :key="'a'+idx" :class="rowClass(ligne)">
                                    <td>
                                        <span v-if="ligne.num_compte" :class="isTotal(ligne) ? 'text-white-50' : 'text-muted'" class="fs-12 d-block">@{{ ligne.num_compte }}</span>
                                        @{{ ligne.libelle }}
                                    </td>
                                    <td class="text-end" :class="{'fw-bold': isTotal(ligne)}">@{{ isTitre(ligne) ? '' : fmt(ligne.net_n) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="p-3 border-bottom bg-light"><h6 class="mb-0 text-uppercase">Passif (capitaux propres et dettes)</h6></div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr><th>Libellé</th><th class="text-end">Montant</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="(ligne, idx) in data.passif" :key="'p'+idx" :class="rowClass(ligne)">
                                    <td>
                                        <span v-if="ligne.num_compte" :class="isTotal(ligne) ? 'text-white-50' : 'text-muted'" class="fs-12 d-block">@{{ ligne.num_compte }}</span>
                                        @{{ ligne.libelle }}
                                    </td>
                                    <td class="text-end" :class="{'fw-bold': isTotal(ligne)}">@{{ isTitre(ligne) ? '' : fmt(ligne.net_n) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-else-if="!isLoading && !data" class="alert alert-info">Aucune donnée — vérifiez l'exercice et les écritures validées.</div>

    </template>
</div>
@endsection
@push('scripts')
<script>window.__ETATS_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/etats/bilan.js') }}"></script>
@endpush
