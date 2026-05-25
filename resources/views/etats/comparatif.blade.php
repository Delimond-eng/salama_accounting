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
                    <p class="mb-1">Total actif N : <strong class="text-primary">@{{ fmt(data.resume?.total_actif_n) }}</strong></p>
                    <p class="mb-0">Résultat net N : <strong class="text-primary">@{{ fmt(data.resume?.resultat_net_n) }}</strong></p>
                </div>
            </div>
        </div>

        <!-- Section ACTIF -->
        <div class="col-12">
            <div class="card border-0 rounded-0">
                <div class="card-header bg-primary text-white"><h5 class="mb-0 text-white">Bilan Actif — Comparatif N / N-1</h5></div>
                <div class="card-body p-0 overflow-auto" style="max-height:600px">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Rubriques Actif</th>
                                <th class="text-end" style="width:150px">Net N</th>
                                <th class="text-end" style="width:150px">Net N-1</th>
                                <th class="text-end" style="width:150px">Variation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(l,i) in data.bilan.actif" :key="'a'+i" :class="rowClass(l)">
                                <td>
                                    <span v-if="l.ref && !isTitre(l)" class="badge bg-light text-muted me-2">@{{ l.ref }}</span>
                                    @{{ l.libelle }}
                                </td>
                                <td class="text-end">@{{ isTitre(l) ? '' : fmt(l.net_n) }}</td>
                                <td class="text-end">@{{ isTitre(l) ? '' : fmt(l.net_n1) }}</td>
                                <td class="text-end" :class="isTitre(l) ? '' : ((l.net_n - l.net_n1) < 0 ? 'text-danger' : (l.net_n - l.net_n1 > 0 ? 'text-success' : ''))">
                                    @{{ isTitre(l) ? '' : ( (l.net_n == 0 && l.net_n1 == 0) ? '0,00' : fmt(l.net_n - l.net_n1) ) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section PASSIF -->
        <div class="col-12">
            <div class="card border-0 rounded-0">
                <div class="card-header bg-success text-white"><h5 class="mb-0 text-white">Bilan Passif — Comparatif N / N-1</h5></div>
                <div class="card-body p-0 overflow-auto" style="max-height:600px">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Rubriques Passif</th>
                                <th class="text-end" style="width:150px">Net N</th>
                                <th class="text-end" style="width:150px">Net N-1</th>
                                <th class="text-end" style="width:150px">Variation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(l,i) in data.bilan.passif" :key="'p'+i" :class="rowClass(l)">
                                <td>
                                    <span v-if="l.ref && !isTitre(l)" class="badge bg-light text-muted me-2">@{{ l.ref }}</span>
                                    @{{ l.libelle }}
                                </td>
                                <td class="text-end">@{{ isTitre(l) ? '' : fmt(l.net_n) }}</td>
                                <td class="text-end">@{{ isTitre(l) ? '' : fmt(l.net_n1) }}</td>
                                <td class="text-end" :class="isTitre(l) ? '' : ((l.net_n - l.net_n1) < 0 ? 'text-danger' : (l.net_n - l.net_n1 > 0 ? 'text-success' : ''))">
                                    @{{ isTitre(l) ? '' : ( (l.net_n == 0 && l.net_n1 == 0) ? '0,00' : fmt(l.net_n - l.net_n1) ) }}
                                </td>
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
