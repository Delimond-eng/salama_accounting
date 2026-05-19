@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => 'produits', 'title' => 'Workflow demandes de fonds', 'breadcrumb' => 'Workflow'])
    <div class="card border-0 rounded-0 mb-3" v-for="w in workflows" :key="w.id">
        <div class="card-header d-flex justify-content-between">
            <strong>@{{ w.libelle }}</strong>
            <span v-if="w.est_defaut" class="badge bg-primary">Par défaut</span>
        </div>
        <ul class="list-group list-group-flush">
            <li v-for="e in w.etapes" :key="e.id" class="list-group-item d-flex justify-content-between">
                <span>@{{ e.ordre }}. @{{ e.libelle }} <small class="text-muted">(@{{ e.type_etape }})</small></span>
                <span class="text-muted small">@{{ e.role_requis || '—' }}</span>
            </li>
        </ul>
    </div>
    <p class="text-muted small">Le circuit par défaut est créé automatiquement : Initiateur → Comptable → Manager → Caissier.</p>
    </template>
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/facturation/workflow.js') }}"></script>
@endpush
