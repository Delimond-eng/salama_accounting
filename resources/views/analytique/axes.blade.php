@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('analytique._nav', ['active' => 'axes', 'title' => 'Configuration analytique', 'breadcrumb' => 'Axes & comptes'])

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3 py-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" v-model="axesRestreints" id="chk_rest" @change="saveConfig">
                <label class="form-check-label fw-medium text-dark" for="chk_rest">Restreindre les axes par compte du plan comptable</label>
            </div>
            <button type="button" class="btn btn-primary shadow-sm" @click="openAxeForm">
                <i class="ti ti-plus me-1"></i>Nouvel axe analytique
            </button>
        </div>
    </div>

    <div v-for="axe in axes" :key="axe.id" class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <span class="badge bg-primary text-white fw-bold me-3 px-3">@{{ axe.code }}</span>
                <h5 class="mb-0 fw-bold text-dark">@{{ axe.libelle }}</h5>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-soft-primary" @click="openSectionForm(axe)">
                    <i class="ti ti-plus me-1"></i>Ajouter un compte
                </button>
                <button type="button" class="btn btn-sm btn-icon btn-outline-light border" @click="editAxe(axe)">
                    <i class="ti ti-edit"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 120px">Code</th>
                            <th>Libellé du compte analytique</th>
                            <th class="text-end" style="width: 180px">Budget Alloué</th>
                            <th class="text-center" style="width: 100px">Statut</th>
                            <th class="text-center" style="width: 80px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!axe.sections?.length">
                            <td colspan="5" class="text-muted text-center py-5 italic">
                                <i class="ti ti-info-circle me-1"></i>Aucun compte configuré pour cet axe.
                            </td>
                        </tr>
                        <tr v-for="s in axe.sections" :key="s.id">
                            <td class="font-monospace fw-bold text-primary">@{{ s.code }}</td>
                            <td class="fw-medium">@{{ s.libelle }}</td>
                            <td class="text-end fw-bold">@{{ s.budget ? fmt(s.budget) : '—' }}</td>
                            <td class="text-center">
                                <span class="badge rounded-pill" :class="s.actif ? 'bg-soft-success text-success' : 'bg-soft-secondary text-secondary'">
                                    @{{ s.actif ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-icon btn-outline-light border" @click="editSection(axe, s)">
                                    <i class="ti ti-edit text-muted"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_axe" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow">
        <form @submit.prevent="saveAxe">
            <div class="modal-header bg-light border-0"><h5 class="modal-title fw-bold">@{{ formAxe.id ? 'Modifier' : 'Nouvel' }} axe analytique</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div v-if="errorList.length" class="alert alert-danger p-2 small mb-3">
                    <ul class="mb-0"><li v-for="err in errorList">@{{ err }}</li></ul>
                </div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label text-muted fs-12 mb-1">Code</label><input class="form-control" v-model="formAxe.code" required placeholder="ex: PRJ"></div>
                    <div class="col-md-8"><label class="form-label text-muted fs-12 mb-1">Libellé</label><input class="form-control" v-model="formAxe.libelle" required placeholder="ex: Projets"></div>
                    <div class="col-12"><label class="form-label text-muted fs-12 mb-1">Description</label><textarea class="form-control" rows="2" v-model="formAxe.description"></textarea></div>
                    <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" v-model="formAxe.actif" id="axe_act"><label class="form-check-label" for="axe_act">Axe actif</label></div></div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-white border px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary px-4" :disabled="isSaving">
                    <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                    Enregistrer
                </button>
            </div>
        </form>
    </div></div></div>

    <div class="modal fade" id="modal_section" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow">
        <form @submit.prevent="saveSection">
            <div class="modal-header bg-light border-0"><h5 class="modal-title fw-bold">Compte analytique</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div v-if="errorList.length" class="alert alert-danger p-2 small mb-3">
                    <ul class="mb-0"><li v-for="err in errorList">@{{ err }}</li></ul>
                </div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label text-muted fs-12 mb-1">Code</label><input class="form-control" v-model="formSection.code" required></div>
                    <div class="col-md-8"><label class="form-label text-muted fs-12 mb-1">Libellé</label><input class="form-control" v-model="formSection.libelle" required></div>
                    <div class="col-12"><label class="form-label text-muted fs-12 mb-1">Budget prévisionnel</label><input type="number" step="0.01" class="form-control" v-model.number="formSection.budget"></div>
                    <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" v-model="formSection.actif" id="sec_act"><label class="form-check-label" for="sec_act">Compte actif</label></div></div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-white border px-4" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary px-4" :disabled="isSaving">
                    <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                    Enregistrer
                </button>
            </div>
        </form>
    </div></div></div>
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
    .btn-soft-primary { background-color: #eef4ff; color: #3f7afd; }
    .btn-soft-primary:hover { background-color: #3f7afd; color: #fff; }
    .italic { font-style: italic; }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/analytique/axes.js') }}"></script>
@endpush
