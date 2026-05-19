@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('etats._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('etats._filtres')
    <div class="card border-0 rounded-0" v-if="data">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">@{{ data.titre }}</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Libellé</th><th class="text-end">Ouverture</th><th class="text-end">Variation</th><th class="text-end">Clôture</th></tr>
                </thead>
                <tbody>
                    <tr v-for="(l,i) in data.lignes" :key="i">
                        <td>@{{ l.libelle }}</td>
                        <td class="text-end">@{{ fmt(l.ouverture) }}</td>
                        <td class="text-end">@{{ fmt(l.variation) }}</td>
                        <td class="text-end fw-medium">@{{ fmt(l.cloture) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__ETATS_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/etats/variation-kp.js') }}"></script>
@endpush
