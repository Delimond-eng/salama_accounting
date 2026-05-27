@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => 'stock', 'title' => 'Fiche de stock', 'breadcrumb' => 'Fiche de stock'])
    <div v-if="error" class="alert alert-danger">@{{ error }}</div>

    <div v-if="produit" class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card border-0 rounded-0">
                <div class="card-body">
                    <h4 class="mb-1">@{{ produit.code ? produit.code + ' — ' : '' }}@{{ produit.libelle }}</h4>
                    <p class="text-muted mb-2">@{{ produit.type_article }} — Unité : @{{ produit.unite }}</p>
                    <div class="row g-2 fs-14">
                        <div class="col-sm-4"><span class="text-muted">Stock actuel</span><br><strong class="fs-18" :class="stats.alerte ? 'text-danger' : 'text-success'">@{{ fmt(stats.stock_actuel) }}</strong></div>
                        <div class="col-sm-4"><span class="text-muted">Stock minimum</span><br><strong>@{{ fmt(produit.stock_minimum) }}</strong></div>
                        <div class="col-sm-4"><span class="text-muted">Gestion stock</span><br><strong>@{{ produit.gestion_stock ? 'Oui' : 'Non' }}</strong></div>
                        <div class="col-sm-4"><span class="text-muted">Prix CDF</span><br>@{{ fmt(produit.prix_unitaire_cdf) }}</div>
                        <div class="col-sm-4"><span class="text-muted">Prix USD</span><br>@{{ fmt(produit.prix_unitaire_usd) }}</div>
                        <div class="col-sm-4"><span class="text-muted">Total entrées</span><br>@{{ fmt(stats.total_entrees) }}</div>
                        <div class="col-sm-4"><span class="text-muted">Total sorties</span><br>@{{ fmt(stats.total_sorties) }}</div>
                    </div>
                    <span v-if="stats.alerte" class="badge badge-soft-danger mt-2">Stock sous le minimum</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <a href="{{ route('accounting.facturation.stock') }}" class="btn btn-outline-secondary w-100 mb-2"><i class="ti ti-arrow-left me-1"></i>Retour inventaire</a>
            <a :href="'/accounting/facturation/produits'" class="btn btn-outline-primary w-100"><i class="ti ti-package me-1"></i>Catalogue produits</a>
        </div>
    </div>

    <div class="card border-0 rounded-0">
        <div class="card-header"><h6 class="mb-0">Historique des mouvements</h6></div>
        <div class="card-body p-0">
            <table class="table table-nowrap mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th><th>N°</th><th>Type</th><th class="text-end">Qté</th>
                        <th class="text-end">Avant</th><th class="text-end">Après</th><th>Libellé</th><th>Utilisateur</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="isLoading"><td colspan="9" class="text-center py-4">Chargement…</td></tr>
                    <tr v-else-if="!mouvements.length"><td colspan="9" class="text-center py-4 text-muted">Aucun mouvement</td></tr>
                    <tr v-for="m in mouvements" :key="m.id">
                        <td>@{{ fmtDate(m.date_mouvement) }}</td>
                        <td>@{{ m.numero || '—' }}</td>
                        <td><span class="badge" :class="typeBadge(m.type_mouvement)">@{{ m.type_mouvement }}</span></td>
                        <td class="text-end">@{{ fmt(m.quantite) }}</td>
                        <td class="text-end">@{{ fmt(m.stock_avant) }}</td>
                        <td class="text-end fw-medium">@{{ fmt(m.stock_apres) }}</td>
                        <td>@{{ m.libelle || '—' }}</td>
                        <td>@{{ m.user?.name || '—' }}</td>
                        <td>
                            <a v-if="m.id" :href="'/accounting/facturation/stock/mouvements/'+m.id+'/pdf'" target="_blank" class="btn btn-sm btn-outline-light"><i class="ti ti-file-type-pdf"></i></a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__PRODUIT_ID__ = @json($produit_id);</script>
<script type="module" src="{{ asset('assets/js/scripts/facturation/stock-fiche.js') }}"></script>
@endpush
