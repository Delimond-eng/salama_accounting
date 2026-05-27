@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('analytique._nav', ['active' => 'centres-cout', 'title' => 'Centres de coût'])
    @include('analytique._filtres')
    <div class="card border-0">
        <div class="card-body p-0">
            <table class="table table-nowrap mb-0">
                <thead class="table-light">
                    <tr><th>Axe</th><th>Centre</th><th class="text-end">Dépenses (cl. 6)</th></tr>
                </thead>
                <tbody>
                    <tr v-if="isLoading"><td colspan="3" class="text-center py-4">Chargement…</td></tr>
                    <tr v-else-if="!result?.items?.length"><td colspan="3" class="text-center py-4 text-muted">Aucune donnée</td></tr>
                    <tr v-for="r in result.items" :key="r.section_id">
                        <td>@{{ r.axe_libelle }}</td>
                        <td>@{{ r.section_code }} — @{{ r.section_libelle }}</td>
                        <td class="text-end fw-medium text-danger">@{{ fmt(r.depenses) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/analytique/centres-cout.js') }}"></script>
@endpush
