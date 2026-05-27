@extends('layouts.app')

@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('parametres._nav', ['active' => 'plan-comptable', 'title' => 'Plan comptable SYSCOHADA', 'breadcrumb' => 'Plan comptable'])

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="ti ti-filter me-2"></i>Classes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush classes-sidebar">
                        <a href="javascript:void(0);" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3"
                           :class="{ active: !filtreClasse }" @click="filtreClasse = null; loadData()">
                            <span class="fw-medium">Toutes les classes</span>
                            <i class="ti ti-chevron-right fs-14"></i>
                        </a>
                        <a v-for="(total, cls) in classes" :key="cls" href="javascript:void(0);"
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3"
                           :class="{ active: filtreClasse == cls }" @click="filtreClasse = cls; loadData()">
                            <div>
                                <span class="badge bg-soft-primary text-primary me-2">@{{ cls }}</span>
                                <span class="fw-medium">Classe @{{ cls }}</span>
                            </div>
                            <span class="badge rounded-pill bg-light text-dark border">@{{ total }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3 flex-wrap gap-3">
                    <div class="search-box">
                        <div class="input-group input-group-sm border rounded-2 px-2 bg-light">
                            <span class="input-group-text bg-transparent border-0 p-0 me-2"><i class="ti ti-search text-muted"></i></span>
                            <input type="text" class="form-control bg-transparent border-0 ps-0" placeholder="Rechercher un compte..." v-model="search" @input="debounceSearch">
                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        @include('components.export-buttons')
                        <button type="button" class="btn btn-primary btn-sm px-3" @click="openForm()">
                            <i class="ti ti-plus me-1"></i>Nouveau compte
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-custom mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 120px">N° Compte</th>
                                    <th>Intitulé du compte</th>
                                    <th style="width: 80px" class="text-center">Classe</th>
                                    <th>Nature / Type</th>
                                    <th class="text-center" style="width: 60px">Tiers</th>
                                    <th class="text-center" style="width: 60px">Rapp.</th>
                                    <th class="text-end" style="width: 80px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="isLoading"><td colspan="7" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement…</td></tr>
                                <tr v-else-if="!comptes.length"><td colspan="7" class="text-center py-5 text-muted">Aucun compte trouvé</td></tr>
                                <tr v-for="c in comptes" :key="c.id">
                                    <td class="font-monospace fw-bold text-primary">@{{ c.num_compte }}</td>
                                    <td class="fw-medium">@{{ c.libelle }}</td>
                                    <td class="text-center"><span class="badge bg-light text-dark border">@{{ c.classe }}</span></td>
                                    <td><span class="text-muted small">@{{ c.type_compte_detail || c.type_compte || 'Standard' }}</span></td>
                                    <td class="text-center">
                                        <i v-if="c.est_compte_tiers" class="ti ti-circle-check-filled text-success fs-18"></i>
                                        <span v-else class="text-light-soft">—</span>
                                    </td>
                                    <td class="text-center">
                                        <i v-if="c.est_rapprochable" class="ti ti-circle-check-filled text-info fs-18"></i>
                                        <span v-else class="text-light-soft">—</span>
                                    </td>
                                    <td class="text-end">
                                        <button v-if="!c.est_systeme || c.societe_id" type="button" class="btn btn-icon btn-sm btn-label-primary" @click="editCompte(c)" title="Modifier">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <span v-else class="badge bg-label-secondary"><i class="ti ti-lock"></i></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de saisie compte -->
    <div class="modal fade" id="modal_compte" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary py-3">
                    <h5 class="modal-title text-white fw-bold">@{{ form.id ? 'Modifier le compte' : 'Nouveau compte comptable' }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="saveCompte">
                    <div class="modal-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Numéro de compte <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-2" v-model="form.num_compte" required placeholder="ex: 601100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Classe <span class="text-danger">*</span></label>
                                <select class="form-select border-2" v-model.number="form.classe" required>
                                    <option v-for="n in 9" :key="n" :value="n">Classe @{{ n }}</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Intitulé du compte <span class="text-danger">*</span></label>
                                <input type="text" class="form-control border-2" v-model="form.libelle" required placeholder="Libellé complet du compte">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Type / Description</label>
                                <input type="text" class="form-control border-2" v-model="form.type_compte_detail" placeholder="Précisions sur la nature du compte">
                            </div>
                            <div class="col-12">
                                <div class="bg-light p-3 rounded-3 border">
                                    <h6 class="fs-12 fw-bold text-uppercase mb-3 text-muted">Options de gestion</h6>
                                    <div class="d-flex gap-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" v-model="form.est_compte_tiers" id="chk_tiers">
                                            <label class="form-check-label fw-medium" for="chk_tiers">Suivi tiers</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" v-model="form.est_rapprochable" id="chk_rapp">
                                            <label class="form-check-label fw-medium" for="chk_rapp">Rapprochable</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top-0 p-3">
                        <button type="button" class="btn btn-white px-4 border" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary px-4" :disabled="isLoading">
                            <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                            Enregistrer le compte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('styles')
<style>
    .classes-sidebar .list-group-item { border: none; border-radius: 8px !important; margin-bottom: 2px; transition: all 0.2s; }
    .classes-sidebar .list-group-item:hover { background-color: #f8f9fa; }
    .classes-sidebar .list-group-item.active { background-color: rgba(63, 122, 253, 0.1); color: #3f7afd; border-left: 4px solid #3f7afd; }

    .table-custom thead th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 11px;
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
    .search-box { min-width: 300px; }
    .btn-label-primary { background: #e7e7ff; color: #696cff; border: none; }
    .btn-label-primary:hover { background: #696cff; color: #fff; }
    .text-light-soft { color: #cbd5e1; }
    .bg-label-secondary { background-color: #ebeef0; color: #8592a3; }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/parametres/plan-comptable.js') }}"></script>
@endpush
