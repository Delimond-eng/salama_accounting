@extends('layouts.app')

@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('parametres._nav', ['active' => 'plan-comptable', 'title' => 'Plan comptable SYSCOHADA', 'breadcrumb' => 'Plan comptable'])

    <div class="row g-3">
        <div class="col-lg-3">
            <div class="card border-0 rounded-0 h-100">
                <div class="card-header"><h5 class="mb-0 fs-16">Classes</h5></div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="javascript:void(0);" class="list-group-item list-group-item-action"
                           :class="{ active: !filtreClasse }" @click="filtreClasse = null; loadData()">Toutes</a>
                        <a v-for="(total, cls) in classes" :key="cls" href="javascript:void(0);"
                           class="list-group-item list-group-item-action d-flex justify-content-between"
                           :class="{ active: filtreClasse == cls }" @click="filtreClasse = cls; loadData()">
                            <span>Classe @{{ cls }}</span>
                            <span class="badge bg-light text-dark">@{{ total }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card border-0 rounded-0">
                <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                    <div class="input-icon input-icon-start position-relative">
                        <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" placeholder="N° compte ou libellé…" v-model="search" @input="debounceSearch">
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        @include('components.export-buttons')
                        <button type="button" class="btn btn-primary" @click="openForm()">
                            <i class="ti ti-square-rounded-plus-filled me-1"></i>Nouveau compte
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>N° compte</th>
                                    <th>Libellé</th>
                                    <th>Classe</th>
                                    <th>Type</th>
                                    <th>Tiers</th>
                                    <th>Rapp.</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="isLoading"><td colspan="7" class="text-center py-4">Chargement…</td></tr>
                                <tr v-else-if="!comptes.length"><td colspan="7" class="text-center py-4 text-muted">Aucun compte</td></tr>
                                <tr v-for="c in comptes" :key="c.id">
                                    <td><span class="fw-medium">@{{ c.num_compte }}</span></td>
                                    <td>@{{ c.libelle }}</td>
                                    <td>@{{ c.classe }}</td>
                                    <td><span class="text-muted fs-13">@{{ c.type_compte_detail || c.type_compte }}</span></td>
                                    <td><i v-if="c.est_compte_tiers" class="ti ti-check text-success"></i></td>
                                    <td><i v-if="c.est_rapprochable" class="ti ti-check text-success"></i></td>
                                    <td>
                                        <button v-if="!c.est_systeme || c.societe_id" type="button" class="btn btn-sm btn-outline-light" @click="editCompte(c)">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_compte" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@{{ form.id ? 'Modifier' : 'Nouveau' }} compte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="saveCompte">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">N° compte</label>
                                <input type="text" class="form-control" v-model="form.num_compte" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Classe</label>
                                <select class="form-select" v-model.number="form.classe" required>
                                    <option v-for="n in 9" :key="n" :value="n">@{{ n }}</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Libellé</label>
                                <input type="text" class="form-control" v-model="form.libelle" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Type détaillé</label>
                                <input type="text" class="form-control" v-model="form.type_compte_detail">
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" v-model="form.est_compte_tiers" id="chk_tiers">
                                    <label class="form-check-label" for="chk_tiers">Compte de tiers</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" v-model="form.est_rapprochable" id="chk_rapp">
                                    <label class="form-check-label" for="chk_rapp">Rapprochable</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" :disabled="isLoading">@{{ isLoading ? 'Enregistrement…' : 'Enregistrer' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/parametres/plan-comptable.js') }}"></script>
@endpush
