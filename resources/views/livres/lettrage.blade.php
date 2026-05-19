@extends('layouts.app')
@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('livres._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])

    <div class="card border-0 rounded-0 mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Compte (préfixe)</label>
                    @include('components.compte-select', ['compteKey' => 'lettrage_compte', 'placeholder' => 'Ex. 41 — Clients…'])
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary w-100" @click="loadData">Rechercher</button>
                </div>
                <div class="col-auto">
                    @include('components.export-buttons')
                </div>
            </div>
            <p class="text-muted fs-12 mb-0 mt-2">Lignes validées non lettrées. Le lettrage manuel sera disponible prochainement.</p>
        </div>
    </div>

    <div class="card border-0 rounded-0">
        <div class="card-header"><h5 class="mb-0">Écritures à lettrer</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date enregistrement</th><th>Pièce</th><th>Compte</th><th>Partenaire</th><th>Libellé</th>
                            <th class="text-end">Débit</th><th class="text-end">Crédit</th><th>Devise</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="8" class="text-center py-4">Chargement…</td></tr>
                        <tr v-for="l in lignes" :key="l.id">
                            <td class="text-nowrap">@{{ fmtDateTime(l.date_enregistrement) }}</td>
                            <td>@{{ l.num_piece }}</td>
                            <td>@{{ l.num_compte }}</td>
                            <td>@{{ l.partenaire || '—' }}</td>
                            <td>@{{ l.libelle }}</td>
                            <td class="text-end">@{{ fmtMontantDevise(l.debit, l.devise_saisie) }}</td>
                            <td class="text-end">@{{ fmtMontantDevise(l.credit, l.devise_saisie) }}</td>
                            <td>@{{ l.devise_saisie }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    </template>
</div>
@endsection
@push('scripts')
<script>window.__LIVRES_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/livres/lettrage.js') }}"></script>
@endpush
