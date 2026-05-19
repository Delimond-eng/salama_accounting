@extends('layouts.app')
@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => 'demandes', 'title' => $title, 'breadcrumb' => $title])
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('accounting.facturation.demandes.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Nouvelle demande</a>
    </div>
    <div class="card border-0 rounded-0">
        <div class="card-body p-0">
            <table class="table table-nowrap mb-0">
                <thead class="table-light"><tr><th>N°</th><th>Demandeur</th><th class="text-end">Montant</th><th>Étape</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                    <tr v-if="isLoading"><td colspan="6" class="text-center py-4">Chargement…</td></tr>
                    <tr v-for="d in demandes" :key="d.id">
                        <td>@{{ d.numero }}</td>
                        <td>@{{ d.demandeur?.name }}</td>
                        <td class="text-end">@{{ fmt(d.montant) }} @{{ d.devise }}</td>
                        <td>@{{ d.etape_courante?.libelle || '—' }}</td>
                        <td><span class="badge" :class="badgeStatut(d.statut)">@{{ d.statut }}</span></td>
                        <td><a :href="'/accounting/facturation/demandes/'+d.id" class="btn btn-sm btn-outline-primary">Voir</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/facturation/demandes-liste.js') }}"></script>
@endpush
