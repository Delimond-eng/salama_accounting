@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('analytique._nav', ['active' => 'grand-livre', 'title' => 'Grand livre analytique', 'breadcrumb' => 'Grand livre'])
    @include('analytique._filtres')

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-primary">Mouvements analytiques</h5>
                <p class="mb-0 text-muted small">Détail chronologique des écritures avec affectation analytique.</p>
            </div>
            <div class="text-end" v-if="result?.lignes">
                <span class="badge bg-soft-info text-info px-3 py-2">@{{ result.lignes.length }} Écritures</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 100px">Date</th>
                            <th style="width: 110px">N° Pièce</th>
                            <th style="width: 80px">Jnl</th>
                            <th style="width: 100px">Compte</th>
                            <th>Analytique (Axe / Section)</th>
                            <th>Libellé écriture</th>
                            <th class="text-end" style="width: 150px">Débit</th>
                            <th class="text-end" style="width: 150px">Crédit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="8" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement…</td></tr>
                        <tr v-else-if="!result?.lignes?.length"><td colspan="8" class="text-center py-5 text-muted">Aucun mouvement trouvé</td></tr>
                        <tr v-for="(l,i) in result.lignes" :key="i">
                            <td class="text-muted fs-12">@{{ l.date_ecriture }}</td>
                            <td><span class="badge bg-label-secondary font-monospace">@{{ l.num_piece }}</span></td>
                            <td><span class="badge" :class="journalBadgeClass(l.journal_type, l.journal_code)">@{{ l.journal_code }}</span></td>
                            <td class="font-monospace fw-bold text-primary">@{{ l.num_compte }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-soft-info text-info me-2">@{{ l.axe_code }}</span>
                                    <span class="fw-medium">@{{ l.section_code }}</span>
                                </div>
                            </td>
                            <td>@{{ l.libelle_ligne }}</td>
                            <td class="text-end fw-medium">@{{ l.debit > 0 ? fmt(l.debit) : '' }}</td>
                            <td class="text-end fw-medium">@{{ l.credit > 0 ? fmt(l.credit) : '' }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-primary text-white fw-bold" v-if="result?.lignes?.length">
                        <tr>
                            <td colspan="6" class="text-end px-4">TOTAUX PÉRIODE</td>
                            <td class="text-end">@{{ fmt(result.lignes.reduce((s,l) => s + (Number(l.debit)||0), 0)) }}</td>
                            <td class="text-end">@{{ fmt(result.lignes.reduce((s,l) => s + (Number(l.credit)||0), 0)) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('styles')
<style>
    .table-custom thead th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 10px;
        letter-spacing: 0.5px;
        font-weight: 700;
        padding: 12px 15px;
        border-bottom: 2px solid #dee2e6;
        color: #475569;
    }
    .table-custom tbody td {
        padding: 10px 15px;
        vertical-align: middle;
        font-size: 13.5px;
        border-bottom: 1px solid #f1f5f9;
    }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/analytique/grand-livre.js') }}"></script>
@endpush
