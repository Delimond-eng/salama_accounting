@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('analytique._nav', ['active' => 'balance', 'title' => 'Balance analytique'])
    @include('analytique._filtres')
    <div v-if="error" class="alert alert-danger">@{{ error }}</div>
    <div class="card border-0">
        <div class="card-body p-0">
            <table class="table table-nowrap mb-0">
                <thead class="table-light">
                    <tr><th>Axe</th><th>Compte analytique</th><th class="text-end">Débit</th><th class="text-end">Crédit</th><th class="text-end">Solde</th></tr>
                </thead>
                <tbody>
                    <tr v-if="isLoading"><td colspan="5" class="text-center py-4">Chargement…</td></tr>
                    <tr v-else-if="!result?.items?.length"><td colspan="5" class="text-center py-4 text-muted">Aucune donnée</td></tr>
                    <tr v-for="r in result.items" :key="r.section_id">
                        <td><span class="badge badge-soft-info">@{{ r.axe_code }}</span> @{{ r.axe_libelle }}</td>
                        <td>@{{ r.section_code }} — @{{ r.section_libelle }}</td>
                        <td class="text-end">@{{ fmt(r.debit) }}</td>
                        <td class="text-end">@{{ fmt(r.credit) }}</td>
                        <td class="text-end fw-medium">@{{ fmt(r.solde) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/analytique/balance.js') }}"></script>
@endpush
