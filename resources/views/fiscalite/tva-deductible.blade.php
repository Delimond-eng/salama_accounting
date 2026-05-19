@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('fiscalite._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('fiscalite._filtres')

    <div class="card border-0 rounded-0" v-if="data">
        <div class="card-header d-flex justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-0">TVA déductible</h5>
                <span class="text-muted fs-12">@{{ filtres.date_debut }} → @{{ filtres.date_fin }} — @{{ data.devise }}</span>
            </div>
            <span class="badge bg-success fs-14 px-3 py-2">Total : @{{ fmt(data.total) }} @{{ data.devise }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Compte</th><th class="text-end">Montant débit net</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="(l,i) in data.detail" :key="i">
                            <td><code>@{{ l.num_compte }}</code></td>
                            <td class="text-end">@{{ fmt(l.montant) }}</td>
                        </tr>
                        <tr v-if="!data.detail.length"><td colspan="2" class="text-muted text-center py-4">Aucun mouvement TVA déductible sur la période.</td></tr>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr><td>Total</td><td class="text-end">@{{ fmt(data.total) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div v-else-if="!isLoading" class="alert alert-info">Aucune donnée — vérifiez les comptes 445x.</div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__FISCALITE_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/fiscalite/tva-deductible.js') }}"></script>
@endpush
