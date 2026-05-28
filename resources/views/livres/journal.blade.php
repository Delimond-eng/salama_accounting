@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('livres._nav', ['active' => 'journal', 'title' => 'Journal Général', 'breadcrumb' => 'Journal'])
    @include('livres._filtres')

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
            <div>
                <h5 class="mb-0 fw-bold text-primary">Registre du Journal</h5>
                <p class="mb-0 text-muted small">Liste chronologique des écritures validées.</p>
            </div>
            <div class="text-end" v-if="lignes.length">
                <span class="badge bg-soft-info text-info px-3 py-2">@{{ lignes.length }} Lignes</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 140px">Enregistrement</th>
                            <th style="width: 100px">Date Écr.</th>
                            <th style="width: 110px">N° Pièce</th>
                            <th style="width: 60px">Jnl</th>
                            <th style="width: 110px">Compte</th>
                            <th>Libellé / Partenaire</th>
                            <th class="text-end" style="width: 130px">Débit</th>
                            <th class="text-end" style="width: 130px">Crédit</th>
                            <th class="text-center" style="width: 60px">Dev.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="9" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement des écritures...</td></tr>
                        <tr v-else-if="!lignes.length"><td colspan="9" class="text-center py-5 text-muted">Aucune écriture trouvée pour cette période</td></tr>
                        <template v-else v-for="(l, i) in lignes">
                            <tr :key="i">
                                <td class="text-muted fs-11">@{{ fmtDateTime(l.date_enregistrement) }}</td>
                                <td class="fw-medium">@{{ l.date_ecriture }}</td>
                                <td><span class="badge bg-label-secondary font-monospace">@{{ l.num_piece }}</span></td>
                                <td class="text-center"><span class="badge" :class="journalBadgeClass(null, l.journal_code)">@{{ l.journal_code }}</span></td>
                                <td class="font-monospace fw-bold text-primary">@{{ l.num_compte }}</td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium">@{{ l.libelle }}</span>
                                        <small class="text-info fs-10" v-if="l.partenaire && l.partenaire !== l.libelle">
                                            <i class="ti ti-user me-1"></i>@{{ l.partenaire }}
                                        </small>
                                    </div>
                                </td>
                                <td class="text-end fw-bold">@{{ l.debit > 0 ? fmt(l.debit) : '' }}</td>
                                <td class="text-end fw-bold">@{{ l.credit > 0 ? fmt(l.credit) : '' }}</td>
                                <td class="text-center"><small class="text-muted">@{{ l.devise_saisie }}</small></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="table-light text-dark fw-bold" v-if="lignes.length">
                        <tr>
                            <td colspan="6" class="text-end px-3">TOTAL GÉNÉRAL (@{{ filtres.devise_affichage }})</td>
                            <td class="text-end">@{{ fmt(lignes.reduce((s, l) => s + (Number(l.debit) || 0), 0)) }}</td>
                            <td class="text-end">@{{ fmt(lignes.reduce((s, l) => s + (Number(l.credit) || 0), 0)) }}</td>
                            <td></td>
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
        padding: 10px 12px;
        border-bottom: 2px solid #dee2e6;
        color: #475569;
    }
    .table-custom tbody td {
        padding: 10px 12px;
        vertical-align: middle;
        font-size: 13px;
        border-bottom: 1px solid #f1f5f9;
    }
    .bg-label-primary { background: #e7e7ff; color: #696cff; }
    .bg-label-secondary { background-color: #f1f3f4; color: #5f6368; }
</style>
@endpush

@push('scripts')
<script>window.__LIVRES_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/livres/journal.js') }}"></script>
@endpush
