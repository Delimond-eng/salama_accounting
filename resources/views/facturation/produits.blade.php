@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => 'produits', 'title' => $title, 'breadcrumb' => $title])
    <div class="row g-3">
        <div class="col-md-5">
            <div class="card border-0 rounded-0">
                <div class="card-header"><h5 class="mb-0">@{{ form.id ? 'Modifier' : 'Nouveau' }} article</h5></div>
                <div class="card-body">
                    <div class="mb-2"><label class="form-label">Code</label><input class="form-control" v-model="form.code"></div>
                    <div class="mb-2"><label class="form-label">Libellé</label><input class="form-control" v-model="form.libelle"></div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label">Type</label>
                            <select class="form-select" v-model="form.type_article">
                                <option value="produit">Produit (stockable)</option>
                                <option value="service">Service</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Unité</label><input class="form-control" v-model="form.unite" placeholder="U, kg, L…"></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label">Prix CDF</label><input type="number" step="0.01" class="form-control" v-model.number="form.prix_unitaire_cdf"></div>
                        <div class="col-6"><label class="form-label">Prix USD</label><input type="number" step="0.01" class="form-control" v-model.number="form.prix_unitaire_usd"></div>
                    </div>
                    <div v-if="form.type_article==='produit'" class="border rounded p-2 mb-2 bg-light">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="gs" v-model="form.gestion_stock">
                            <label class="form-check-label" for="gs">Gestion de stock</label>
                        </div>
                        <div v-if="form.gestion_stock" class="row g-2">
                            <div class="col-6"><label class="form-label">Stock actuel</label><input type="number" step="0.01" class="form-control" v-model.number="form.stock_actuel" :disabled="!!form.id"></div>
                            <div class="col-6"><label class="form-label">Stock minimum</label><input type="number" step="0.01" class="form-control" v-model.number="form.stock_minimum"></div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" @click="save">Enregistrer</button>
                    <button v-if="form.id" type="button" class="btn btn-outline-light ms-2" @click="resetForm">Nouveau</button>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card border-0 rounded-0">
                <div class="card-body p-0">
                    <table class="table mb-0 table-nowrap">
                        <thead class="table-light">
                            <tr><th>Code</th><th>Libellé</th><th class="text-end">Prix CDF</th><th class="text-end">Prix USD</th><th class="text-end">Stock</th><th></th></tr>
                        </thead>
                        <tbody>
                            <tr v-if="!produits.length"><td colspan="6" class="text-center py-4 text-muted">Aucun article</td></tr>
                            <tr v-for="p in produits" :key="p.id">
                                <td>@{{ p.code || '—' }}</td>
                                <td>@{{ p.libelle }} <span class="badge badge-soft-secondary">@{{ p.type_article }}</span></td>
                                <td class="text-end">@{{ fmt(p.prix_unitaire_cdf) }}</td>
                                <td class="text-end">@{{ fmt(p.prix_unitaire_usd) }}</td>
                                <td class="text-end">@{{ p.gestion_stock ? fmt(p.stock_actuel) : '—' }}</td>
                                <td><button type="button" class="btn btn-sm btn-outline-primary" @click="edit(p)"><i class="ti ti-edit"></i></button></td>
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
<script type="module" src="{{ asset('assets/js/scripts/facturation/produits.js') }}"></script>
@endpush
