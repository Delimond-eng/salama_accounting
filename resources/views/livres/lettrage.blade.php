@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('livres._nav', ['active' => 'lettrage', 'title' => 'Lettrage des comptes', 'breadcrumb' => 'Lettrage'])

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-3">
                        <label class="form-label mb-0 fw-bold text-nowrap">Compte à lettrer :</label>
                        <div class="flex-grow-1">
                            @include('components.compte-select', ['compteKey' => 'lettrage_compte', 'placeholder' => 'Ex: 411 - Clients...'])
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-primary w-100" @click="loadData" :disabled="isLoading">
                        <i class="ti ti-search me-1"></i>Rechercher les écritures
                    </button>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top">
                <p class="text-muted fs-12 mb-0 italic">
                    <i class="ti ti-info-circle me-1"></i>Affiche les lignes validées non lettrées pour le compte sélectionné.
                </p>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-primary">Écritures en attente de lettrage</h5>
                <p class="mb-0 text-muted small">Sélectionnez les lignes pour effectuer un lettrage manuel.</p>
            </div>
            <div class="text-end" v-if="lignes.length">
                <span class="badge bg-soft-warning text-warning px-3 py-2">@{{ lignes.length }} Lignes non lettrées</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 130px">Date Écr.</th>
                            <th style="width: 110px">N° Pièce</th>
                            <th style="width: 100px">Compte</th>
                            <th>Libellé / Partenaire</th>
                            <th class="text-end" style="width: 150px">Débit</th>
                            <th class="text-end" style="width: 150px">Crédit</th>
                            <th class="text-center" style="width: 60px">Dev.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="7" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement des écritures...</td></tr>
                        <tr v-else-if="!lignes.length"><td colspan="7" class="text-center py-5 text-muted">Aucune écriture non lettrée trouvée pour ce compte.</td></tr>
                        <tr v-for="l in lignes" :key="l.id">
                            <td class="text-muted fs-12">@{{ l.date_ecriture }}</td>
                            <td><span class="badge bg-label-secondary font-monospace">@{{ l.num_piece }}</span></td>
                            <td class="font-monospace fw-bold text-primary">@{{ l.num_compte }}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-medium">@{{ l.libelle }}</span>
                                    <small class="text-muted" v-if="l.partenaire && l.partenaire !== l.libelle">
                                        <i class="ti ti-user me-1"></i>@{{ l.partenaire }}
                                    </small>
                                </div>
                            </td>
                            <td class="text-end fw-bold">@{{ l.debit > 0 ? fmt(l.debit) : '' }}</td>
                            <td class="text-end fw-bold">@{{ l.credit > 0 ? fmt(l.credit) : '' }}</td>
                            <td class="text-center"><small class="text-muted">@{{ l.devise_saisie }}</small></td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-light fw-bold" v-if="lignes.length">
                        <tr>
                            <td colspan="4" class="text-end px-3">TOTAL NON LETTRÉ</td>
                            <td class="text-end text-primary">@{{ fmt(lignes.reduce((s,l) => s + (Number(l.debit)||0), 0)) }}</td>
                            <td class="text-end text-primary">@{{ fmt(lignes.reduce((s,l) => s + (Number(l.credit)||0), 0)) }}</td>
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
    .bg-label-secondary { background-color: #f1f3f4; color: #5f6368; }
    .italic { font-style: italic; }
</style>
@endpush

@push('scripts')
<script>window.__LIVRES_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/livres/lettrage.js') }}"></script>
@endpush
