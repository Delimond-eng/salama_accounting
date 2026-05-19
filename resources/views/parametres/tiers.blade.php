@extends('layouts.app')

@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('parametres._nav', ['active' => 'tiers', 'title' => 'Tiers', 'breadcrumb' => 'Tiers'])

    <div class="card border-0 rounded-0">
        <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <div class="input-icon input-icon-start position-relative">
                <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                <input type="text" class="form-control" placeholder="Code ou nom…" v-model="search" @input="debounceSearch">
            </div>
            <div class="d-flex gap-2 align-items-center">
                @include('components.export-buttons')
                <select class="form-select w-auto" v-model="filtreType" @change="loadTiers">
                    <option value="">Tous types</option>
                    <option value="client">Clients</option>
                    <option value="fournisseur">Fournisseurs</option>
                    <option value="client_fournisseur">Client / Fournisseur</option>
                </select>
                <button type="button" class="btn btn-primary" @click="openForm()">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Nouveau tiers
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-nowrap mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th><th>Nom</th><th>Type</th><th>Compte</th><th>Contact</th><th>Actif</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="isLoading"><td colspan="7" class="text-center py-4">Chargement…</td></tr>
                    <tr v-else-if="!liste.length"><td colspan="7" class="text-center py-4 text-muted">Aucun tiers</td></tr>
                    <tr v-for="t in liste" :key="t.id">
                        <td><span class="fw-medium">@{{ t.code }}</span></td>
                        <td>@{{ t.nom }}</td>
                        <td><span class="badge badge-soft-info">@{{ t.type }}</span></td>
                        <td>@{{ t.num_compte_collectif || '—' }}</td>
                        <td>@{{ t.email || t.telephone || '—' }}</td>
                        <td><span class="badge" :class="t.actif ? 'badge-soft-success' : 'badge-soft-secondary'">@{{ t.actif ? 'Oui' : 'Non' }}</span></td>
                        <td><button type="button" class="btn btn-sm btn-outline-light" @click="editTiers(t)"><i class="ti ti-edit"></i></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal_tiers" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form @submit.prevent="saveTiers">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{ form.id ? 'Modifier' : 'Nouveau' }} tiers</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-md-4"><label class="form-label">Code</label><input class="form-control" v-model="form.code" required></div>
                        <div class="col-md-8"><label class="form-label">Nom</label><input class="form-control" v-model="form.nom" required></div>
                        <div class="col-md-6">
                            <label class="form-label">Type</label>
                            <select class="form-select" v-model="form.type" required>
                                <option value="client">Client</option>
                                <option value="fournisseur">Fournisseur</option>
                                <option value="client_fournisseur">Client / Fournisseur</option>
                                <option value="salarie">Salarié</option>
                                <option value="banque">Banque</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Compte collectif</label>
                            @include('components.compte-select', ['compteKey' => 'tiers_collectif', 'inputClass' => 'form-control'])</div>
                        <div class="col-md-4"><label class="form-label">Email</label><input type="email" class="form-control" v-model="form.email"></div>
                        <div class="col-md-4"><label class="form-label">Téléphone</label><input class="form-control" v-model="form.telephone"></div>
                        <div class="col-md-4"><label class="form-label">Ville</label><input class="form-control" v-model="form.ville"></div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" v-model="form.actif" id="tiers_actif">
                                <label class="form-check-label" for="tiers_actif">Actif</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" :disabled="isLoading">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/parametres/tiers.js') }}"></script>
@endpush
