@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('etats._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('etats._filtres')

    <div class="row g-4">
        <div class="col-md-6 col-xl-4" v-for="e in exports" :key="e.type">
            <div class="card border-0 shadow-sm h-100 export-card">
                <div class="card-body d-flex flex-column p-4">
                    <div class="d-flex align-items-center mb-4">
                        <div :class="'avatar avatar-lg bg-label-' + e.color + ' rounded-3 me-3'">
                            <i :class="'ti ' + e.icon + ' fs-2'"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h5 class="mb-0 fw-bold">@{{ e.label }}</h5>
                                <span v-if="e.ref" class="badge bg-label-info">@{{ e.ref }}</span>
                            </div>
                            <span class="badge bg-light text-muted border">@{{ e.type }}</span>
                        </div>
                    </div>

                    <p class="text-muted flex-grow-1 mb-4 lh-base">
                        @{{ e.desc }}
                    </p>

                    <div class="d-flex gap-2 mt-auto pt-3 border-top">
                        <a :href="exportUrlFor(e.type, 'pdf')" class="btn btn-label-danger flex-fill" target="_blank">
                            <i class="ti ti-file-type-pdf me-2"></i>PDF
                        </a>
                        <a :href="exportUrlFor(e.type, 'excel')" class="btn btn-label-success flex-fill" target="_blank">
                            <i class="ti ti-file-type-xls me-2"></i>Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Summary / Help -->
    <div class="mt-5 p-4 bg-label-secondary rounded-3 border-dashed border-2">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h6 class="fw-bold mb-1"><i class="ti ti-info-circle me-2"></i>Notes sur les exports</h6>
                <p class="mb-0 text-muted small">Les exports générés respectent les normes SYSCOHADA révisées. Pour toute modification personnalisée de la mise en page, veuillez contacter l'administrateur système.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <button class="btn btn-sm btn-outline-primary"><i class="ti ti-help-circle me-1"></i>Guide d'exportation</button>
            </div>
        </div>
    </div>
    </template>
</div>

<style>
    .export-card { transition: transform 0.2s ease, shadow 0.2s ease; }
    .export-card:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important; }
    .bg-label-primary { background-color: #e7e7ff !important; color: #696cff !important; }
    .bg-label-success { background-color: #e8fadf !important; color: #71dd37 !important; }
    .bg-label-info { background-color: #d7f5fc !important; color: #03c3ec !important; }
    .bg-label-warning { background-color: #fff2d6 !important; color: #ffab00 !important; }
    .bg-label-danger { background-color: #ffe5e5 !important; color: #ff3e1d !important; }
    .btn-label-danger { color: #ff3e1d; background: #ffe5e5; border-color: transparent; }
    .btn-label-danger:hover { color: #fff; background: #ff3e1d; }
    .btn-label-success { color: #71dd37; background: #e8fadf; border-color: transparent; }
    .btn-label-success:hover { color: #fff; background: #71dd37; }
</style>
@endsection

@push('scripts')
<script>window.__ETATS_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/etats/exports.js') }}"></script>
@endpush
