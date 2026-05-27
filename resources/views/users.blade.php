@extends('layouts.app')

@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
        <!-- Header Section -->
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-1 text-dark fw-bold">Gestion des Utilisateurs</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Administration / Utilisateurs</li>
                    </ol>
                </nav>
            </div>
            <div class="gap-2 d-flex align-items-center">
                <button type="button" class="btn btn-primary px-4 shadow-sm" @click="openForm()">
                    <i class="ti ti-user-plus me-1"></i>Nouvel Utilisateur
                </button>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0 fw-bold text-primary">Registre du Personnel</h5>
                    </div>
                    <div class="col-auto">
                        <div class="input-group input-group-sm bg-light rounded-2 px-2">
                            <span class="input-group-text bg-transparent border-0"><i class="ti ti-search text-muted"></i></span>
                            <input type="text" class="form-control bg-transparent border-0" placeholder="Rechercher..." v-model="search">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Email & Contact</th>
                                <th>Rôle système</th>
                                <th class="text-center">Statut</th>
                                <th class="text-center">Accès</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading"><td colspan="6" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement…</td></tr>
                            <tr v-for="u in filteredUsers" :key="u.id">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-md bg-label-primary rounded-circle me-3">
                                            <span class="fw-bold">@{{ u.name.charAt(0) }}</span>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-dark">@{{ u.name }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-dark">@{{ u.email }}</div>
                                    <small class="text-muted" v-if="u.telephone">@{{ u.telephone }}</small>
                                </td>
                                <td>
                                    <span v-for="role in u.roles" :key="role.id" class="badge bg-soft-primary text-primary text-uppercase fs-10 px-2 me-1">@{{ role.name }}</span>
                                    <span v-if="!u.roles.length" class="text-light-soft small">Aucun rôle</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill" :class="u.actif ? 'bg-soft-success text-success' : 'bg-soft-secondary text-secondary'">
                                        @{{ u.actif ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info rounded-pill px-3" @click="manageAccess(u)">
                                        <i class="ti ti-shield-lock me-1"></i>Droits
                                    </button>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button type="button" class="btn btn-icon btn-sm btn-label-primary" @click="editUser(u)">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <button v-if="u.id !== currentUserId" type="button" class="btn btn-icon btn-sm btn-label-danger" @click="deleteUser(u)">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal User -->
        <div class="modal fade" id="modal_user" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary py-3">
                        <h5 class="modal-title text-white fw-bold">@{{ form.id ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="saveUser">
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Nom complet</label>
                                    <input type="text" class="form-control border-2" v-model="form.name" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Adresse Email</label>
                                    <input type="email" class="form-control border-2" v-model="form.email" required>
                                </div>
                                <div class="col-12" v-if="!form.id">
                                    <label class="form-label fw-bold">Mot de passe</label>
                                    <input type="password" class="form-control border-2" v-model="form.password" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Rôle principal</label>
                                    <select class="form-select border-2" v-model="form.role_id" required>
                                        <option v-for="r in roles" :key="r.id" :value="r.id">@{{ r.name }}</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" v-model="form.actif" id="user_active">
                                        <label class="form-check-label" for="user_active">Utilisateur actif</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-0">
                            <button type="button" class="btn btn-white border px-4" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary px-4" :disabled="isLoading">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Accès/Droits -->
        <div class="modal fade" id="access_users" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-info py-3">
                        <h5 class="modal-title text-white fw-bold">Habilitations de l'utilisateur</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="addAccess">
                        <div class="modal-body p-4">
                            <div class="alert bg-label-info border-0 mb-4">
                                <p class="mb-0 small">Définissez des permissions spécifiques pour cet utilisateur. Ces droits s'ajoutent à ceux déjà hérités par son rôle principal.</p>
                            </div>

                            <div class="table-responsive border rounded-3 bg-light-soft">
                                <table class="table table-sm mb-0">
                                    <thead class="bg-white">
                                        <tr>
                                            <th class="ps-3 py-2">Module / Entité</th>
                                            <th v-for="col in permissionColumns" :key="col" class="text-center py-2">@{{ columnLabels[col] || col }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="module in allActions" :key="module.entity">
                                            <td class="ps-3 py-2 fw-medium text-dark fs-13">@{{ module.label }}</td>
                                            <td v-for="col in permissionColumns" :key="col" class="text-center py-2">
                                                <div class="form-check form-check-md d-inline-block" v-if="moduleHasAction(module, col)">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        :value="`${module.entity}.${col}`"
                                                        v-model="form.permissions"
                                                    >
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-0">
                            <button type="button" class="btn btn-white border px-4" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-info text-white px-4" :disabled="isLoading">
                                <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                                Mettre à jour les accès
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
    .table-custom thead th { background-color: #f8f9fa; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; font-weight: 700; padding: 12px 15px; border-bottom: 2px solid #dee2e6; color: #475569; }
    .table-custom tbody td { padding: 12px 15px; vertical-align: middle; font-size: 13.5px; border-bottom: 1px solid #f1f5f9; }
    .btn-label-primary { background: #e7e7ff; color: #696cff; border: none; }
    .btn-label-danger { background: #ffe5e5; color: #ff3e1d; border: none; }
    .bg-label-primary { background-color: #e7e7ff; color: #696cff; }
    .bg-label-info { background-color: #d7f5fc; color: #03c3ec; }
    .bg-light-soft { background-color: #f8fafc; }
</style>
@endpush

@push('scripts')
<script>
    window.__CURRENT_USER_ID__ = @json(Auth::id());
</script>
<script type="module" src="{{ asset('assets/js/scripts/user.js') }}"></script>
@endpush
