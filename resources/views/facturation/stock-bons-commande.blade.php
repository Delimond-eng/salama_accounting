@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 rounded-0">
                <div class="card-header"><h6 class="mb-0">Nouveau bon de commande</h6></div>
                <div class="card-body">
                    <div v-if="error && error.length" class="alert alert-danger py-2"><div v-for="(e,i) in error" :key="i">@{{ e }}</div></div>
                    <div class="mb-2">
                        <label class="form-label">Fournisseur</label>
                        <select class="form-select" v-model.number="form.tiers_id">
                            <option :value="null">— Sélectionner —</option>
                            <option v-for="t in fournisseurs" :key="t.id" :value="t.id">@{{ t.code }} — @{{ t.nom }}</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label">Date</label><input type="date" class="form-control" v-model="form.date_commande"></div>
                        <div class="col-6">
                            <label class="form-label">Devise</label>
                            <select class="form-select" v-model="form.devise" @change="onDeviseChange">
                                <option value="CDF">CDF</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>
                    <table class="table table-sm table-bordered mb-2">
                        <thead class="table-light"><tr><th>Article</th><th>Qté</th><th>P.U.</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="(l, i) in form.lignes" :key="i">
                                <td>
                                    <select class="form-select form-select-sm mb-1" v-model.number="l.produit_id" @change="appliquerProduit(i)">
                                        <option :value="null">Libre</option>
                                        <option v-for="p in produits" :key="p.id" :value="p.id">@{{ p.libelle }}</option>
                                    </select>
                                    <input class="form-control form-control-sm" v-model="l.libelle">
                                </td>
                                <td><input type="number" step="0.01" class="form-control form-control-sm" v-model.number="l.quantite"></td>
                                <td><input type="number" step="0.01" class="form-control form-control-sm" v-model.number="l.prix_unitaire"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger" @click="form.lignes.splice(i,1)"><i class="ti ti-trash"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-2" @click="form.lignes.push({libelle:'',quantite:1,prix_unitaire:0,produit_id:null})">+ Ligne</button>
                    <button type="button" class="btn btn-primary w-100" @click="save">Enregistrer le BC</button>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 rounded-0">
                <div class="card-body p-0">
                    <table class="table mb-0 table-nowrap">
                        <thead class="table-light"><tr><th>N°</th><th>Date</th><th>Fournisseur</th><th class="text-end">Montant</th><th>Statut</th></tr></thead>
                        <tbody>
                            <tr v-for="b in bons" :key="b.id">
                                <td>@{{ b.numero }}</td>
                                <td>@{{ fmtDate(b.date_commande) }}</td>
                                <td>@{{ b.tiers?.nom }}</td>
                                <td class="text-end">@{{ fmt(b.montant_ht) }} @{{ b.devise }}</td>
                                <td><span class="badge badge-soft-secondary">@{{ b.statut }}</span></td>
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
<script type="module" src="{{ asset('assets/js/scripts/facturation/stock-bons-commande.js') }}"></script>
@endpush
