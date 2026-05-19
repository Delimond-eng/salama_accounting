@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('etats._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('etats._filtres')
    <div v-if="data" class="row g-3">
        <div class="col-md-4">
            <div class="card border-0 rounded-0">
                <div class="card-body">
                    <h6>Résumé comparatif</h6>
                    <p class="mb-1">Total actif N : <strong>@{{ fmt(data.resume?.total_actif_n) }}</strong></p>
                    <p class="mb-0">Résultat net N : <strong>@{{ fmt(data.resume?.resultat_net_n) }}</strong></p>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card border-0 rounded-0">
                <div class="card-header"><h5 class="mb-0">Bilan comparatif</h5></div>
                <div class="card-body p-0 overflow-auto" style="max-height:400px">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light"><tr><th>Actif</th><th class="text-end">Net N</th><th class="text-end">Net N-1</th></tr></thead>
                        <tbody>
                            <tr v-for="(l,i) in data.bilan.actif" :key="'a'+i" v-if="!isTitre(l) && l.net_n">
                                <td>@{{ l.libelle }}</td>
                                <td class="text-end">@{{ fmt(l.net_n) }}</td>
                                <td class="text-end">@{{ fmt(l.net_n1) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__ETATS_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/etats/comparatif.js') }}"></script>
@endpush
