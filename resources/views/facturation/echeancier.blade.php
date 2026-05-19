@extends('layouts.app')
@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    <div class="card border-0 rounded-0">
        <div class="card-body p-0">
            <table class="table table-nowrap mb-0">
                <thead class="table-light"><tr><th>Facture</th><th>Tiers</th><th>Échéance</th><th class="text-end">Montant</th><th>Retard</th></tr></thead>
                <tbody>
                    <tr v-for="i in items" :key="i.id" :class="i.en_retard ? 'table-warning' : ''">
                        <td>@{{ i.numero }}</td>
                        <td>@{{ i.tiers }}</td>
                        <td>@{{ i.date_echeance }}</td>
                        <td class="text-end">@{{ fmt(i.montant_ttc) }} @{{ i.devise }}</td>
                        <td><span v-if="i.en_retard" class="badge bg-danger">@{{ i.retard_jours }} j</span><span v-else class="text-muted">—</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__ECHEANCIER_CIBLE__ = @json($cible); window.__FACTURATION_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/facturation/echeancier.js') }}"></script>
@endpush
