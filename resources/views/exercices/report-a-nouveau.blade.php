@extends('layouts.app')
@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
        @include('exercices._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])

        <div class="card border-0 rounded-0">
            <div class="card-header"><h5 class="mb-0">Report à nouveau (131/139 → 121/129)</h5></div>
            <div class="card-body">
                <p class="text-muted">Affectation du résultat de l'exercice précédent vers les comptes de report à nouveau, en journal AN.</p>
                <label class="form-label">Exercice courant (ouvert)</label>
                <select v-model="exerciceId" class="form-select mb-3 w-auto">
                    <option v-for="ex in exercicesOuverts" :key="ex.id" :value="ex.id">@{{ ex.libelle }}</option>
                </select>
                <button type="button" class="btn btn-primary" :disabled="!exerciceId || isLoading" @click="genererRan">
                    <i class="ti ti-arrow-forward-up me-1"></i>Générer le report à nouveau
                </button>
                <p v-if="selection && selection.report_a_nouveau_genere" class="text-success mt-3 mb-0">
                    <i class="ti ti-check"></i> Report à nouveau déjà généré pour cet exercice.
                </p>
            </div>
        </div>
    </template>
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/exercices/report-a-nouveau.js') }}"></script>
@endpush
