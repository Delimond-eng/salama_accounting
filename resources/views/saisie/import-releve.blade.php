@extends('layouts.app')
@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('saisie._nav', ['active' => 'import', 'title' => 'Import relevé bancaire', 'breadcrumb' => 'Import CSV'])

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 rounded-0">
                <div class="card-header"><h5 class="mb-0">Paramètres import</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Journal banque</label>
                        <select class="form-select" v-model.number="journalId">
                            <option v-for="j in journauxBanque" :key="j.id" :value="j.id">@{{ j.code }} — @{{ j.libelle }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fichier CSV</label>
                        <input type="file" class="form-control" accept=".csv,.txt" @change="onFile">
                        <p class="text-muted fs-12 mt-1">Colonnes : date;libelle;montant ou date;libelle;debit;credit</p>
                    </div>
                    <button type="button" class="btn btn-primary w-100" :disabled="isLoading || !csvContent" @click="importer">
                        <i class="ti ti-upload me-1"></i>Importer en brouillon
                    </button>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 rounded-0">
                <div class="card-header"><h5 class="mb-0">Aperçu</h5></div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded fs-12 mb-0" style="max-height:400px;overflow:auto">@{{ csvContent || 'Aucun fichier chargé' }}</pre>
                </div>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/saisie/import-releve.js') }}"></script>
@endpush
