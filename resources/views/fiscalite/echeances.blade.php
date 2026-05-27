@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('fiscalite._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('fiscalite._filtres')

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0 text-primary fw-bold">Calendrier fiscal</h4>
                <p class="text-muted mb-0 small"><i class="ti ti-info-circle me-1"></i>Dates limites de dépôt et de paiement</p>
            </div>
            <div class="text-end">
                <button type="button" class="btn btn-sm btn-outline-primary" @click="loadData" :disabled="isLoading">
                    <i class="ti ti-refresh me-1" :class="{'ti-spin': isLoading}"></i>Actualiser
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-fiscalite mb-0">
                    <thead>
                        <tr>
                            <th>Échéance</th>
                            <th>Type</th>
                            <th>Période</th>
                            <th>Date limite</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(e,i) in echeances" :key="i" :class="{'table-danger-soft': isRetard(e)}">
                            <td class="fw-medium">@{{ e.libelle }}</td>
                            <td><span class="badge bg-label-secondary font-monospace">@{{ e.type }}</span></td>
                            <td><span class="text-muted small">@{{ e.periode_debut }} au @{{ e.periode_fin }}</span></td>
                            <td class="fw-bold" :class="isRetard(e) ? 'text-danger' : 'text-dark'">
                                <i class="ti ti-calendar-x me-1" v-if="isRetard(e)"></i>
                                @{{ e.date_limite_depot }}
                            </td>
                            <td>
                                <span class="badge" :class="statutClass(e.statut)">@{{ statutLabel(e.statut) }}</span>
                                <span v-if="isRetard(e)" class="badge bg-danger ms-1">En retard</span>
                            </td>
                        </tr>
                        <tr v-if="!echeances.length && !isLoading">
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="ti ti-calendar-off fs-32 mb-2 d-block"></i>
                                Aucun exercice courant configuré ou aucune échéance trouvée.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('styles')
<style>
    .table-fiscalite thead th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        font-weight: 700;
        padding: 12px 15px;
        border-bottom: 2px solid #dee2e6;
        color: #475569;
    }
    .table-fiscalite tbody td {
        padding: 12px 15px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13.5px;
    }
    .bg-label-secondary { background-color: #f1f3f4 !important; color: #5f6368 !important; }
    .table-danger-soft { background-color: rgba(231, 13, 13, 0.03) !important; }
</style>
@endpush

@push('scripts')
<script>window.__FISCALITE_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/fiscalite/echeances.js') }}"></script>
@endpush
