@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('analytique._nav', ['active' => 'grand-livre', 'title' => 'Grand livre analytique'])
    @include('analytique._filtres')
    <div class="card border-0">
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-nowrap mb-0">
                <thead class="table-light">
                    <tr><th>Date</th><th>Pièce</th><th>Journal</th><th>Compte</th><th>Analytique</th><th>Libellé</th><th class="text-end">Débit</th><th class="text-end">Crédit</th></tr>
                </thead>
                <tbody>
                    <tr v-if="isLoading"><td colspan="8" class="text-center py-4">Chargement…</td></tr>
                    <tr v-else-if="!result?.lignes?.length"><td colspan="8" class="text-center py-4 text-muted">Aucun mouvement</td></tr>
                    <tr v-for="(l,i) in result.lignes" :key="i">
                        <td>@{{ l.date_ecriture }}</td>
                        <td>@{{ l.num_piece }}</td>
                        <td>@{{ l.journal_code }}</td>
                        <td>@{{ l.num_compte }}</td>
                        <td><span class="badge badge-soft-info">@{{ l.axe_code }}</span> @{{ l.section_code }}</td>
                        <td>@{{ l.libelle_ligne }}</td>
                        <td class="text-end">@{{ fmt(l.debit) }}</td>
                        <td class="text-end">@{{ fmt(l.credit) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/analytique/grand-livre.js') }}"></script>
@endpush
