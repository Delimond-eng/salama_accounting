@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    <div v-if="message" class="alert alert-success">@{{ message }}</div>
    <div v-if="error && error.length" class="alert alert-danger"><div v-for="(e,i) in error" :key="i">@{{ e }}</div></div>
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 rounded-0">
                <div class="card-header"><h6 class="mb-0">Mouvement manuel</h6></div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label">Produit</label>
                        <select class="form-select" v-model.number="mvt.produit_id">
                            <option :value="null">— Sélectionner —</option>
                            <option v-for="p in produitsStock" :key="p.id" :value="p.id">@{{ p.libelle }} (stock @{{ p.stock_actuel }})</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Type</label>
                        <select class="form-select" v-model="mvt.type_mouvement">
                            <option value="entree">Entrée</option>
                            <option value="sortie">Sortie</option>
                            <option value="ajustement">Ajustement (+)</option>
                            <option value="inventaire">Inventaire (solde)</option>
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label">Quantité</label><input type="number" step="0.01" class="form-control" v-model.number="mvt.quantite"></div>
                    <div class="mb-2"><label class="form-label">Libellé</label><input class="form-control" v-model="mvt.libelle"></div>
                    <div class="mb-2"><label class="form-label">Date</label><input type="date" class="form-control" v-model="mvt.date_mouvement"></div>
                    <button type="button" class="btn btn-primary w-100" @click="enregistrerMouvement" :disabled="isLoading">
                        <span v-if="isLoading">Enregistrement…</span>
                        <span v-else>Enregistrer et générer le PDF</span>
                    </button>
                    <p class="text-muted fs-12 mt-2 mb-0">Un bon d'entrée ou de sortie PDF sera ouvert automatiquement.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 rounded-0">
                <div class="card-body p-0">
                    <table class="table table-nowrap mb-0">
                        <thead class="table-light">
                            <tr><th>Article</th><th class="text-end">Stock</th><th class="text-end">Min.</th><th class="text-end">Prix CDF</th><th class="text-end">Prix USD</th><th></th></tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading"><td colspan="6" class="text-center py-4">Chargement…</td></tr>
                            <tr v-for="p in produits" :key="p.id" :class="alerteStock(p) ? 'table-warning' : ''">
                                <td>@{{ p.code ? p.code + ' — ' : '' }}@{{ p.libelle }}</td>
                                <td class="text-end fw-medium">@{{ fmt(p.stock_actuel) }} @{{ p.unite }}</td>
                                <td class="text-end">@{{ fmt(p.stock_minimum) }}</td>
                                <td class="text-end">@{{ fmt(p.prix_unitaire_cdf) }}</td>
                                <td class="text-end">@{{ fmt(p.prix_unitaire_usd) }}</td>
                                <td><span v-if="!p.gestion_stock" class="badge badge-soft-secondary">Sans stock</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/facturation/stock-inventaire.js') }}"></script>
@endpush
