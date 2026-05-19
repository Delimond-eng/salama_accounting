@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('fiscalite._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('fiscalite._filtres')

    <div class="card border-0 rounded-0">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">Calendrier fiscal</h5>
            <button type="button" class="btn btn-sm btn-outline-primary" @click="loadData"><i class="ti ti-refresh"></i></button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Échéance</th><th>Type</th><th>Période</th><th>Date limite</th><th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(e,i) in echeances" :key="i" :class="{'table-danger': isRetard(e)}">
                            <td>@{{ e.libelle }}</td>
                            <td><code>@{{ e.type }}</code></td>
                            <td>@{{ e.periode_debut }} → @{{ e.periode_fin }}</td>
                            <td>@{{ e.date_limite_depot }}</td>
                            <td>
                                <span class="badge" :class="statutClass(e.statut)">@{{ statutLabel(e.statut) }}</span>
                                <span v-if="isRetard(e)" class="badge bg-danger ms-1">En retard</span>
                            </td>
                        </tr>
                        <tr v-if="!echeances.length && !isLoading"><td colspan="5" class="text-center text-muted py-4">Aucun exercice courant configuré.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__FISCALITE_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/fiscalite/echeances.js') }}"></script>
@endpush
