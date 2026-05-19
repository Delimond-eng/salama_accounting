@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => 'produits', 'title' => $title, 'breadcrumb' => $title])
    <div class="row g-3">
        <div class="col-md-5">
            <div class="card border-0 rounded-0">
                <div class="card-header"><h5 class="mb-0">Nouveau produit</h5></div>
                <div class="card-body">
                    <div class="mb-2"><label class="form-label">Libelle</label><input class="form-control" v-model="form.libelle"></div>
                    <div class="mb-2"><label class="form-label">Prix unitaire</label><input type="number" class="form-control" v-model.number="form.prix_unitaire"></div>
                    <div class="mb-2"><label class="form-label">Compte vente</label><input class="form-control" v-model="form.compte_vente"></div>
                    <div class="mb-2"><label class="form-label">Compte achat</label><input class="form-control" v-model="form.compte_achat"></div>
                    <button type="button" class="btn btn-primary" @click="save">Enregistrer</button>
                    <a href="{{ route('accounting.facturation.workflow') }}" class="btn btn-outline-secondary ms-2">Configurer workflow</a>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card border-0 rounded-0">
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="table-light"><tr><th>Libelle</th><th class="text-end">Prix</th><th>Comptes</th></tr></thead>
                        <tbody>
                            <tr v-for="p in produits" :key="p.id">
                                <td>@{{ p.libelle }}</td>
                                <td class="text-end">@{{ fmt(p.prix_unitaire) }}</td>
                                <td class="small text-muted">@{{ p.compte_vente }} / @{{ p.compte_achat }}</td>
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
