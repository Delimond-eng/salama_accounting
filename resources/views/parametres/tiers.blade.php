@extends('layouts.app')

@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('parametres._nav', ['active' => 'tiers', 'title' => 'Gestion des Tiers', 'breadcrumb' => 'Tiers'])

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3 flex-wrap gap-3">
            <div>
                <h5 class="mb-0 fw-bold text-primary">Répertoire des Tiers</h5>
                <p class="mb-0 text-muted small">Clients, fournisseurs et partenaires de l'entreprise.</p>
            </div>
<<<<<<< HEAD
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <div class="search-box">
                    <div class="input-group input-group-sm border rounded-2 px-2 bg-light">
                        <span class="input-group-text bg-transparent border-0 p-0 me-2"><i class="ti ti-search text-muted"></i></span>
                        <input type="text" class="form-control bg-transparent border-0 ps-0" placeholder="Rechercher code, nom..." v-model="search" @input="debounceSearch">
                    </div>
                </div>
                <select class="form-select form-select-sm w-auto border-2" v-model="filtreType" @change="loadTiers">
                    <option value="">Tous les types</option>
=======
            <div class="d-flex gap-2 align-items-center">
                @include('components.export-buttons')
                <select class="form-select w-auto" v-model="filtreType" @change="loadData">
                    <option value="">Tous types</option>
>>>>>>> 356d4919f7208489f8fadf9a5b1244abeb82c9b0
                    <option value="client">Clients</option>
                    <option value="fournisseur">Fournisseurs</option>
                    <option value="client_fournisseur">Client / Fournisseur</option>
                    <option value="salarie">Salariés</option>
                </select>
                @include('components.export-buttons')
                <button type="button" class="btn btn-primary btn-sm px-3" @click="openForm()">
                    <i class="ti ti-plus me-1"></i>Nouveau tiers
                </button>
            </div>
        </div>
        <div class="card-body p-0">
<<<<<<< HEAD
            <div class="table-responsive">
                <table class="table table-hover table-bordered table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 100px">Code</th>
                            <th>Nom / Raison Sociale</th>
                            <th>Type</th>
                            <th>Compte Collectif</th>
                            <th>Email & Téléphone</th>
                            <th class="text-center">Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="7" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement…</td></tr>
                        <tr v-else-if="!liste.length"><td colspan="7" class="text-center py-5 text-muted">Aucun tiers enregistré</td></tr>
                        <tr v-for="t in liste" :key="t.id">
                            <td class="font-monospace fw-bold text-primary">@{{ t.code }}</td>
                            <td class="fw-medium">
                                <div class="d-flex flex-column">
                                    <span>@{{ t.nom }}</span>
                                    <small class="text-muted" v-if="t.ville">@{{ t.ville }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge" :class="typeBadgeClass(t.type)">@{{ t.type }}</span>
                            </td>
                            <td class="font-monospace text-muted">@{{ t.num_compte_collectif || '—' }}</td>
                            <td class="fs-13">
                                <div v-if="t.email" class="text-muted"><i class="ti ti-mail me-1"></i>@{{ t.email }}</div>
                                <div v-if="t.telephone" class="text-muted"><i class="ti ti-phone me-1"></i>@{{ t.telephone }}</div>
                                <span v-if="!t.email && !t.telephone" class="text-light-soft">—</span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill" :class="t.actif ? 'bg-soft-success text-success' : 'bg-soft-secondary text-secondary'">
                                    @{{ t.actif ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-icon btn-sm btn-label-primary" @click="editTiers(t)">
                                    <i class="ti ti-edit"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
=======
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
                        <td><span class="badge badge-soft-info">@{{ labelType(t.type) }}</span></td>
                        <td>@{{ t.num_compte_collectif || '—' }}</td>
                        <td>@{{ t.email || t.telephone || '—' }}</td>
                        <td><span class="badge" :class="t.actif ? 'badge-soft-success' : 'badge-soft-secondary'">@{{ t.actif ? 'Oui' : 'Non' }}</span></td>
                        <td><button type="button" class="btn btn-sm btn-outline-light" @click="editTiers(t)"><i class="ti ti-edit"></i></button></td>
                    </tr>
                </tbody>
            </table>
>>>>>>> 356d4919f7208489f8fadf9a5b1244abeb82c9b0
        </div>
    </div>

    <!-- Modal Tiers -->
    <div class="modal fade" id="modal_tiers" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary py-3">
                    <h5 class="modal-title text-white fw-bold">@{{ form.id ? 'Modifier le tiers' : 'Nouveau tiers' }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="saveTiers">
                    <div class="modal-body p-4">
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Code Tiers <span class="text-danger">*</span></label>
                                <input class="form-control border-2 text-uppercase font-monospace" v-model="form.code" required placeholder="ex: CLI001">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Nom ou Raison Sociale <span class="text-danger">*</span></label>
                                <input class="form-control border-2" v-model="form.nom" required placeholder="Nom complet du partenaire">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Type de tiers <span class="text-danger">*</span></label>
                                <select class="form-select border-2" v-model="form.type" required>
                                    <option value="client">Client</option>
                                    <option value="fournisseur">Fournisseur</option>
                                    <option value="client_fournisseur">Client / Fournisseur</option>
                                    <option value="salarie">Salarié</option>
                                    <option value="banque">Banque</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Compte collectif de rattachement</label>
                                @include('components.compte-select', ['compteKey' => 'tiers_collectif', 'inputClass' => 'form-control border-2'])
                            </div>
                            <div class="col-12"><hr class="my-0"></div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Adresse Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ti ti-mail"></i></span>
                                    <input type="email" class="form-control border-2" v-model="form.email" placeholder="contact@email.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Téléphone</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="ti ti-phone"></i></span>
                                    <input class="form-control border-2" v-model="form.telephone" placeholder="+243 ...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Ville</label>
                                <input class="form-control border-2" v-model="form.ville">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" v-model="form.actif" id="tiers_actif">
                                    <label class="form-check-label fw-medium" for="tiers_actif">Tiers actif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top-0 p-3">
                        <button type="button" class="btn btn-white px-4 border" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary px-4" :disabled="isLoading">
                            <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                            Enregistrer le tiers
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
        padding: 12px 15px;
        vertical-align: middle;
        font-size: 13.5px;
        border-bottom: 1px solid #f1f5f9;
    }
    .btn-label-primary { background: #e7e7ff; color: #696cff; border: none; }
    .btn-label-primary:hover { background: #696cff; color: #fff; }
    .text-light-soft { color: #cbd5e1; }
    .search-box { min-width: 250px; }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/parametres/tiers.js') }}"></script>
@endpush
