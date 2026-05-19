@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    <div class="card border-0 rounded-0 mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Recherche n°, tiers…" v-model="search" @input="debounceLoad">
                </div>
                <div class="col-md-2">
                    <select class="form-select" v-model="filtreStatut" @change="loadList">
                        <option value="">Tous statuts</option>
                        <option value="brouillon">Brouillon</option>
                        <option value="validee">Validée</option>
                        <option value="payee">Payée</option>
                        <option value="annulee">Annulée</option>
                    </select>
                </div>
                <div class="col-auto ms-auto">
                    <a :href="createUrl" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Nouvelle</a>
                </div>
            </div>
        </div>
    </div>
    <div class="card border-0 rounded-0">
        <div class="card-body p-0">
            <table class="table table-nowrap mb-0">
                <thead class="table-light">
                    <tr>
                        <th>N°</th><th>Date</th><th>Tiers</th><th class="text-end">TTC</th><th>Statut</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="isLoading"><td colspan="6" class="text-center py-4">Chargement…</td></tr>
                    <tr v-else-if="!factures.length"><td colspan="6" class="text-center py-4 text-muted">Aucune facture</td></tr>
                    <tr v-for="f in factures" :key="f.id">
                        <td><span class="fw-medium">@{{ f.numero }}</span></td>
                        <td>@{{ f.date_facture }}</td>
                        <td>@{{ f.tiers?.nom }}</td>
                        <td class="text-end">@{{ fmt(f.montant_ttc) }} @{{ f.devise }}</td>
                        <td><span class="badge" :class="badgeStatut(f.statut)">@{{ f.statut }}</span></td>
                        <td class="text-end">
                            <a :href="editUrl(f.id)" class="btn btn-sm btn-outline-light"><i class="ti ti-edit"></i></a>
                            <a :href="pdfUrl(f.id)" class="btn btn-sm btn-outline-primary" target="_blank"><i class="ti ti-file-type-pdf"></i></a>
                            <button v-if="f.statut==='brouillon'" type="button" class="btn btn-sm btn-outline-success" @click="valider(f)"><i class="ti ti-check"></i></button>
                            <button v-if="f.statut==='validee'" type="button" class="btn btn-sm btn-outline-info" @click="payer(f)"><i class="ti ti-cash"></i></button>
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
<script>window.__FACTURATION_PAGE__ = @json($page); window.__FACTURATION_TYPE__ = @json($type);</script>
<script type="module" src="{{ asset('assets/js/scripts/facturation/factures-liste.js') }}"></script>
@endpush
