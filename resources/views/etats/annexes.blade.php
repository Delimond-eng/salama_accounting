@extends('layouts.app')
@section('content')

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('etats._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])

    <div v-if="data" class="row g-4">
        <!-- Sidebar : Liste des notes -->
        <div class="col-md-4 col-xl-3">
            <div class="card border-0 shadow-sm sticky-top" style="top: 100px;">
                <div class="card-header bg-transparent border-bottom-0 pt-4">
                    <h6 class="text-uppercase fw-bold text-muted small mb-0">Sommaire des notes</h6>
                </div>
                <div class="card-body p-0 pb-3">
                    <div class="list-group list-group-flush">
                        <a v-for="(lib, num) in data.notes" :key="num" :href="'#note-' + num"
                           class="list-group-item list-group-item-action border-0 px-4 py-2 d-flex align-items-center">
                            <span class="badge badge-pill  bg-primary text-white me-2" style="min-width: 35px;">@{{ num }}</span>
                            <span class="text-truncate small">@{{ lib }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu des notes -->
        <div class="col-md-8 col-xl-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom py-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm bg-label-primary me-3">
                            <i class="ti ti-notebook fs-4"></i>
                        </div>
                        <h5 class="mb-0">@{{ data.titre }}</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div v-for="s in data.sections" :key="s.num" :id="'note-' + s.num" class="mb-5">
                        <div class="d-flex align-items-center mb-3">
                            <div class="h4 fw-bold text-primary mb-0 me-3">Note @{{ s.num }}</div>
                            <div class="flex-grow-1 border-bottom"></div>
                        </div>
                        <h6 class="fw-semibold text-dark mb-3">@{{ s.titre }}</h6>
                        <div class="bg-light p-3 rounded-3 border-start border-primary border-4">
                            <p class="text-muted mb-0 lh-lg" style="white-space: pre-line;">@{{ s.contenu }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div v-if="!data && !isLoading" class="text-center py-5">
        <div class="mb-3"><i class="ti ti-database-off fs-1 text-muted"></i></div>
        <h5 class="text-muted">Aucune donnée disponible pour les annexes</h5>
    </div>
    </template>
</div>
@endsection

@push('scripts')
<script>window.__ETATS_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/etats/annexes.js') }}"></script>
@endpush
