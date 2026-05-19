@extends('layouts.app')
@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => 'paiements', 'title' => $title, 'breadcrumb' => $title])
    <div class="card border-0 rounded-0">
        <div class="card-body p-0">
            <table class="table table-nowrap mb-0">
                <thead class="table-light"><tr><th>N°</th><th>Date</th><th>Facture</th><th class="text-end">Montant</th><th>Méthode</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="p in paiements" :key="p.id">
                        <td>@{{ p.numero }}</td>
                        <td>@{{ p.date_paiement }}</td>
                        <td>@{{ p.facture?.numero }}</td>
                        <td class="text-end">@{{ fmt(p.montant) }} @{{ p.devise }}</td>
                        <td>@{{ p.methode }}</td>
                        <td><a :href="'/accounting/facturation/paiements/'+p.id+'/pdf'" class="btn btn-sm btn-outline-primary" target="_blank"><i class="ti ti-file-type-pdf"></i> Reçu</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/facturation/paiements.js') }}"></script>
@endpush
