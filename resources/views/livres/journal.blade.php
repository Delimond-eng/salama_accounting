@extends('layouts.app')
@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('livres._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('livres._filtres')

    <div class="card border-0 rounded-0 mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Journal</label>
                    <select class="form-select" v-model="journalId" @change="loadData">
                        <option value="">Tous les journaux</option>
                        <option v-for="j in journaux" :key="j.id" :value="j.id">@{{ j.code }} — @{{ j.libelle }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary w-100" @click="loadData"><i class="ti ti-search me-1"></i>Actualiser</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 rounded-0">
        <div class="card-header"><h5 class="mb-0">Journal général</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date enregistrement</th><th>Pièce</th><th>Jnl</th><th>Compte</th><th>Partenaire</th><th>Libellé</th>
                            <th class="text-end">Débit</th><th class="text-end">Crédit</th><th>Devise</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="9" class="text-center py-4">Chargement…</td></tr>
                        <tr v-for="(l, i) in lignes" :key="i">
                            <td class="text-nowrap">@{{ fmtDateTime(l.date_enregistrement) }}</td>
                            <td>@{{ l.num_piece }}</td>
                            <td>@{{ l.journal_code }}</td>
                            <td>@{{ l.num_compte }}</td>
                            <td>@{{ l.partenaire || '—' }}</td>
                            <td>@{{ l.libelle }}</td>
                            <td class="text-end">@{{ fmtMontantDevise(l.debit, l.devise_saisie) }}</td>
                            <td class="text-end">@{{ fmtMontantDevise(l.credit, l.devise_saisie) }}</td>
                            <td><span class="badge badge-soft-secondary">@{{ l.devise_saisie }}</span></td>
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
<script type="module" src="{{ asset('assets/js/scripts/livres/journal.js') }}"></script>
@endpush
