@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('saisie._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('saisie._filtres')

    <div class="card border-0 rounded-0">
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="mb-0 fw-bold text-uppercase fs-14">
                    <i class="ti {{ $icon ?? 'ti-list' }} me-2 text-primary"></i>
                    @{{ pageTitle }}
                </h5>
                <p class="mb-0 text-muted fs-12 mt-1">@{{ pageSubtitle }}</p>
            </div>
            <a :href="createUrl" class="btn btn-primary btn-sm"><i class="ti ti-square-rounded-plus-filled me-1"></i>Nouvelle écriture</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date enregistrement</th><th>Pièce</th><th>Journal</th><th>Libellé</th><th>Devise</th>
                            <th class="text-end">Débit</th><th class="text-end">Crédit</th><th>Statut</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="9" class="text-center py-4">Chargement...</td></tr>
                        <tr v-else-if="!ecritures.length"><td colspan="9" class="text-center py-4 text-muted">Aucune écriture</td></tr>
                        <tr v-for="e in ecritures" :key="e.id">
                            <td class="text-nowrap">@{{ formatDateTime(e.created_at) }}</td>
                            <td><span class="fw-medium">@{{ e.num_piece }}</span></td>
                            <td><span class="badge badge-soft-primary">@{{ e.journal?.code }}</span></td>
                            <td>@{{ e.libelle }}</td>
                            <td><span class="badge badge-soft-secondary">@{{ e.devise || 'CDF' }}</span></td>
                            <td class="text-end">@{{ formatMontantDevise(e.total_debit, e.devise) }}</td>
                            <td class="text-end">@{{ formatMontantDevise(e.total_credit, e.devise) }}</td>
                            <td><span class="badge" :class="badgeStatut(e.statut)">@{{ e.statut }}</span></td>
                            <td>
                                <div class="d-flex gap-1 justify-content-end">
                                    <a :href="'/accounting/saisie/' + page + '/ecriture/' + e.id" class="btn btn-sm btn-outline-light"><i class="ti ti-edit"></i></a>
                                    <button v-if="e.statut==='brouillon'" type="button" class="btn btn-sm btn-outline-success" @click="valider(e)"><i class="ti ti-check"></i></button>
                                    <button v-if="e.statut==='brouillon'" type="button" class="btn btn-sm btn-outline-danger" @click="supprimer(e)"><i class="ti ti-trash"></i></button>
                                </div>
                            </td>
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
<script>window.__SAISIE_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/saisie/liste.js') }}"></script>
@endpush
