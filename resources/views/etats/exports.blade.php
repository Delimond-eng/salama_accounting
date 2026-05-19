@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('etats._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('etats._filtres')
    <div class="row g-3">
        <div class="col-md-4" v-for="e in exports" :key="e.type">
            <div class="card border-0 rounded-0 h-100">
                <div class="card-body d-flex flex-column">
                    <i :class="'ti '+e.icon+' fs-1 text-'+e.color+' mb-2'"></i>
                    <h5>@{{ e.label }}</h5>
                    <p class="text-muted flex-grow-1">@{{ e.desc }}</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a :href="exportUrlFor(e.type, 'pdf')" class="btn btn-outline-danger flex-fill" target="_blank">
                            <i class="ti ti-file-type-pdf me-1"></i>PDF
                        </a>
                        <a :href="exportUrlFor(e.type, 'excel')" class="btn btn-outline-success flex-fill" target="_blank">
                            <i class="ti ti-file-type-xls me-1"></i>Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__ETATS_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/etats/exports.js') }}"></script>
@endpush
