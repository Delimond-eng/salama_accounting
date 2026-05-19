@extends('layouts.app')
@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('etats._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    <div class="card border-0 rounded-0" v-if="data">
        <div class="card-header"><h5 class="mb-0">@{{ data.titre }}</h5></div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-12"><h6 class="text-muted">Référentiel des notes aux états</h6></div>
                <div class="col-md-6" v-for="(lib, num) in data.notes" :key="num">
                    <span class="badge badge-soft-primary me-1">Note @{{ num }}</span> @{{ lib }}
                </div>
            </div>
            <div v-for="s in data.sections" :key="s.num" class="mb-4 border-bottom pb-3">
                <h6 class="fw-semibold">Note @{{ s.num }} — @{{ s.titre }}</h6>
                <p class="text-muted mb-0">@{{ s.contenu }}</p>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__ETATS_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/etats/annexes.js') }}"></script>
@endpush
