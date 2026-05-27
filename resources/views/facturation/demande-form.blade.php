@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => 'demandes', 'title' => $title, 'breadcrumb' => $title])

    <!-- Écran de Création -->
    <div v-if="!demandeId" class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0 fw-bold text-primary text-uppercase fs-14">
                <i class="ti ti-git-pull-request me-2"></i>Nouvelle demande de fonds
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-uppercase text-muted">Montant sollicité <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control border-2 fw-bold" v-model.number="form.montant" placeholder="0.00">
                        <span class="input-group-text bg-light border-2 border-start-0">@{{ form.devise }}</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-uppercase text-muted">Devise</label>
                    <input class="form-control border-2 text-center font-monospace fw-bold" v-model="form.devise" maxlength="3" placeholder="CDF">
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold small text-uppercase text-muted">Motif / Justification du retrait <span class="text-danger">*</span></label>
                    <textarea class="form-control border-2" rows="3" v-model="form.motif" placeholder="Précisez la nature de la dépense..."></textarea>
                </div>
                <div class="col-12 mt-4">
                    <button type="button" class="btn btn-primary px-4 py-2 fw-bold" :disabled="!form.montant || !form.motif || isLoading" @click="creer">
                        <i class="ti ti-send me-1"></i>Soumettre la demande
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Écran de Consultation / Traitement -->
    <div v-else-if="demande" class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary text-uppercase fs-14">Fiche de Demande n° @{{ demande.numero }}</h5>
                    <span class="badge rounded-pill" :class="badgeStatut(demande.statut)">@{{ demande.statut }}</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4 mb-4">
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase d-block mb-1">Montant de la demande</label>
                            <h3 class="fw-bold text-dark mb-0">@{{ fmt(demande.montant) }} <small class="text-muted">@{{ demande.devise }}</small></h3>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <label class="text-muted small text-uppercase d-block mb-1">Étape actuelle</label>
                            <span v-if="demande.etape_courante" class="text-primary fw-bold">
                                <i class="ti ti-arrow-right-circle me-1"></i>@{{ demande.etape_courante.libelle }}
                            </span>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small text-uppercase d-block mb-1">Motif de la dépense</label>
                            <div class="p-3 bg-light rounded-3 border-start border-primary border-4">
                                <p class="mb-0 text-dark">@{{ demande.motif }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Zone de traitement (Imputation comptable) -->
                    <div v-if="peutTraiter" class="mt-5 pt-4 border-top">
                        <h6 class="fw-bold text-dark mb-4"><i class="ti ti-edit me-2 text-primary"></i>Traitement de la demande</h6>

                        <div v-if="demande.etape_courante?.imputation_comptable && demande.statut==='en_validation'" class="row g-3 mb-4 bg-light p-3 rounded-3 border">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Compte de Débit (Charge)</label>
                                <input class="form-control border-2 font-monospace" v-model="traitement.compte_debit" placeholder="ex: 601100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Compte de Crédit (Trésorerie/Tiers)</label>
                                <input class="form-control border-2 font-monospace" v-model="traitement.compte_credit" placeholder="ex: 571000">
                            </div>
                            <div class="col-12 mt-2">
                                <p class="text-muted small mb-0"><i class="ti ti-info-circle me-1"></i>L'imputation comptable sera générée lors de la validation finale.</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small">Observations / Commentaire</label>
                            <textarea class="form-control border-2" rows="2" v-model="traitement.commentaire" placeholder="Ajouter une note au dossier..."></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success px-4 py-2 fw-bold" @click="traiter('approuve')" :disabled="isLoading">
                                <i class="ti ti-check me-1"></i>Approuver
                            </button>
                            <button type="button" class="btn btn-danger px-4 py-2 fw-bold" @click="traiter('rejete')" :disabled="isLoading">
                                <i class="ti ti-x me-1"></i>Rejeter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="ti ti-history me-2"></i>Fil d'activité</h6>
                </div>
                <div class="card-body p-0">
                    <div class="timeline-container p-4">
                        <div v-for="(h, idx) in demande.historiques" :key="h.id" class="timeline-item-lite" :class="{'last': idx === demande.historiques.length - 1}">
                            <div class="timeline-dot bg-primary"></div>
                            <div class="timeline-content-lite">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold text-dark fs-13">@{{ h.action }}</span>
                                    <small class="text-muted">@{{ h.created_at }}</small>
                                </div>
                                <p class="mb-1 text-muted fs-12">@{{ h.description }}</p>
                                <div class="d-flex align-items-center mt-1">
                                    <div class="avatar avatar-xs bg-label-secondary rounded-circle me-2">
                                        <i class="ti ti-user fs-10"></i>
                                    </div>
                                    <span class="fs-11 text-dark fw-medium">@{{ h.user?.name }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="!demande.historiques.length" class="text-center py-3 text-muted small">
                            Aucun historique disponible
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('styles')
<style>
    .bg-light-soft { background-color: #f8fafc; }
    .timeline-container { position: relative; }
    .timeline-item-lite { position: relative; padding-left: 25px; padding-bottom: 25px; border-left: 2px solid #e2e8f0; }
    .timeline-item-lite.last { border-left-color: transparent; }
    .timeline-dot { position: absolute; left: -7px; top: 0; width: 12px; height: 12px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 2px #3f7afd; }
    .timeline-content-lite { position: relative; top: -4px; }
    .bg-label-secondary { background-color: #f1f3f4; color: #5f6368; }
</style>
@endpush

@push('scripts')
<script>window.__DEMANDE_ID__ = @json($demande_id);</script>
<script type="module" src="{{ asset('assets/js/scripts/facturation/demande-form.js') }}"></script>
@endpush
