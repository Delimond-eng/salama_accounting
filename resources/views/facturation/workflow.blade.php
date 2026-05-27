@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => 'produits', 'title' => 'Circuits de Validation', 'breadcrumb' => 'Workflow'])

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold text-primary">Workflows de Demandes de Fonds</h5>
                <p class="mb-0 text-muted small">Définition des étapes et rôles requis pour l'approbation des dépenses.</p>
            </div>
            <button class="btn btn-outline-primary btn-sm" @click="loadList" :disabled="isLoading">
                <i class="ti ti-refresh me-1" :class="{'ti-spin': isLoading}"></i>Actualiser
            </button>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-6 col-xl-4" v-for="w in workflows" :key="w.id">
                    <div class="card border-2 h-100 workflow-card" :class="w.est_defaut ? 'border-primary' : 'border-light'">
                        <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-0 pb-0">
                            <h6 class="fw-bold mb-0">@{{ w.libelle }}</h6>
                            <span v-if="w.est_defaut" class="badge bg-primary fs-10">PAR DÉFAUT</span>
                        </div>
                        <div class="card-body">
                            <div class="timeline-workflow mt-2">
                                <div v-for="(e, idx) in w.etapes" :key="e.id" class="timeline-item d-flex gap-3 mb-3">
                                    <div class="timeline-step">
                                        <span class="step-num">@{{ e.ordre }}</span>
                                        <div class="step-line" v-if="idx < w.etapes.length - 1"></div>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="fw-bold text-dark fs-13">@{{ e.libelle }}</div>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="badge bg-label-secondary fs-10 text-uppercase">@{{ e.type_etape }}</span>
                                            <span class="text-muted small" v-if="e.role_requis">
                                                <i class="ti ti-user-shield me-1"></i>@{{ e.role_requis }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5 p-4 bg-light rounded-4 border border-dashed text-center">
                <i class="ti ti-info-circle text-primary fs-32 mb-2 d-block"></i>
                <h6 class="fw-bold">Notes sur le circuit de validation</h6>
                <p class="text-muted small mb-0 mx-auto" style="max-width: 600px;">
                    Le circuit standard suit généralement cet ordre : <strong>Initiateur</strong> (Saisie) → <strong>Comptable</strong> (Imputation) → <strong>Direction/Manager</strong> (Approbation) → <strong>Caissier/Banquier</strong> (Exécution).
                </p>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('styles')
<style>
    .workflow-card { transition: transform 0.2s; }
    .workflow-card:hover { transform: translateY(-3px); }
    .timeline-workflow { position: relative; }
    .timeline-step { display: flex; flex-direction: column; align-items: center; width: 24px; }
    .step-num { width: 24px; height: 24px; background: #3f7afd; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; z-index: 2; }
    .step-line { width: 2px; flex-grow: 1; background: #e2e8f0; margin: 4px 0; }
    .bg-label-secondary { background-color: #f1f3f4; color: #5f6368; }
    .rounded-4 { border-radius: 1rem !important; }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/facturation/workflow.js') }}"></script>
@endpush
