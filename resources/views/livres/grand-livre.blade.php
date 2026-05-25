@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('livres._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('livres._filtres')

    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <button type="button" class="nav-link" :class="{ active: vueMode === 'general' }" @click="setMode('general')">Grand livre général</button>
        </li>
        <li class="nav-item">
            <button type="button" class="nav-link" :class="{ active: vueMode === 'compte' }" @click="setMode('compte')">Par compte</button>
        </li>
    </ul>

    <div class="card border-0 rounded-0 mb-3" v-if="vueMode === 'compte'">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">N° compte</label>
                    @include('components.compte-select', ['compteKey' => 'gl_compte', 'placeholder' => 'Rechercher un compte…'])
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary w-100" @click="loadData"><i class="ti ti-search me-1"></i>Consulter</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 rounded-0 mb-3" v-if="vueMode === 'general'">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="text-muted fs-13 mb-0">Synthèse des mouvements par compte sur la période (montants en @{{ filtres.devise_affichage }}).</span>
            <button type="button" class="btn btn-primary btn-sm" @click="loadData"><i class="ti ti-refresh me-1"></i>Actualiser</button>
        </div>
    </div>

    <div class="card border-0 rounded-0" v-if="vueMode === 'general' && dataGeneral">
        <div class="card-header"><h5 class="mb-0">Grand livre général</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-nowrap mb-0 table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Compte / Date</th>
                            <th>Partenaire</th>
                            <th>Devise</th>
                            <th class="text-end">Débit</th>
                            <th class="text-end">Crédit</th>
                            <th class="text-end">Solde</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(r, i) in dataGeneral.lignes" :key="'g'+i" class="fw-medium">
                            <td>@{{ r.libelle_complet }}</td>
                            <td></td>
                            <td>@{{ r.devise_libelle }}</td>
                            <td class="text-end">@{{ fmtMontantDevise(r.debit, r.devise_saisie) }}</td>
                            <td class="text-end">@{{ fmtMontantDevise(r.credit, r.devise_saisie) }}</td>
                            <td class="text-end">@{{ fmtMontantDevise(r.solde, r.devise_saisie) }}</td>
                        </tr>
                        <tr class="bg-primary text-white fw-bold" v-if="dataGeneral.totaux">
                            <td colspan="3">Total grand livre</td>
                            <td class="text-end">@{{ fmtMontantDevise(dataGeneral.totaux.debit, dataGeneral.devise_affichage) }}</td>
                            <td class="text-end">@{{ fmtMontantDevise(dataGeneral.totaux.credit, dataGeneral.devise_affichage) }}</td>
                            <td class="text-end">@{{ fmtMontantDevise(dataGeneral.totaux.solde, dataGeneral.devise_affichage) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 rounded-0" v-if="vueMode === 'compte' && dataCompte">
        <div class="card-header">
            <h5 class="mb-0">@{{ dataCompte.compte?.num_compte }} — @{{ dataCompte.compte?.libelle }}</h5>
            <span class="text-muted fs-12">Solde ouverture : @{{ fmtMontantDevise(dataCompte.solde_ouverture, dataCompte.devise_affichage) }} · Clôture : @{{ fmtMontantDevise(dataCompte.solde_cloture, dataCompte.devise_affichage) }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-nowrap table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th><th>Partenaire</th><th>Devise</th>
                            <th class="text-end">Débit</th><th class="text-end">Crédit</th><th class="text-end">Solde</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-secondary fw-medium">
                            <td colspan="3">Solde d'ouverture</td>
                            <td class="text-end">—</td><td class="text-end">—</td>
                            <td class="text-end">@{{ fmtMontantDevise(dataCompte.solde_ouverture, dataCompte.devise_affichage) }}</td>
                        </tr>
                        <tr v-for="(l, i) in dataCompte.lignes" :key="i">
                            <td class="text-nowrap">@{{ fmtDateTime(l.date_enregistrement) }}</td>
                            <td>@{{ l.partenaire || '—' }}</td>
                            <td>@{{ l.devise_saisie }}</td>
                            <td class="text-end">@{{ fmtMontantDevise(l.debit, l.devise_saisie) }}</td>
                            <td class="text-end">@{{ fmtMontantDevise(l.credit, l.devise_saisie) }}</td>
                            <td class="text-end fw-medium">@{{ fmtMontantDevise(l.solde, dataCompte.devise_affichage) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-primary text-white fw-bold" v-if="dataCompte.lignes.length">
                         <tr>
                            <td colspan="3">SOLDE DE CLÔTURE</td>
                            <td class="text-end">—</td><td class="text-end">—</td>
                            <td class="text-end">@{{ fmtMontantDevise(dataCompte.solde_cloture, dataCompte.devise_affichage) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    </template>
</div>
@endsection
@push('scripts')
<script>window.__LIVRES_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/livres/grand-livre.js') }}"></script>
@endpush
