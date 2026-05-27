@extends("layouts.app")

@section("content")

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
        <div v-if="errorList.length" class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <ul class="mb-0 ps-3">
                <li v-for="(err, i) in errorList" :key="i">@{{ err }}</li>
            </ul>
            <button type="button" class="btn-close" @click="errorList = []"></button>
        </div>
        <div v-if="message" class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="ti ti-circle-check me-2"></i>@{{ message }}
            <button type="button" class="btn-close" @click="message = null"></button>
        </div>

        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-1">Utilisateurs <span class="badge badge-soft-primary ms-2">@{{ allUsers.length }}</span></h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Administration</li>
                        <li class="breadcrumb-item active">Utilisateurs</li>
                    </ol>
                </nav>
            </div>
            <div class="gap-2 d-flex align-items-center flex-wrap">
                @include('components.export-buttons')
                <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" @click="loadUsers" :disabled="isLoading">
                    <i class="ti ti-refresh" :class="{'ti-spin': isLoading}"></i>
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3 flex-wrap gap-3">
                <div class="search-box">
                    <div class="input-group input-group-sm border rounded-2 px-2 bg-light">
                        <span class="input-group-text bg-transparent border-0 p-0 me-2"><i class="ti ti-search text-muted"></i></span>
                        <input type="text" class="form-control bg-transparent border-0 ps-0" placeholder="Rechercher un utilisateur..." v-model="search">
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="btn btn-outline-light btn-sm shadow-sm dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                            <i class="ti ti-filter me-1"></i>Filtrer
                        </a>
                        <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 250px;">
                            <h6 class="mb-3 fw-bold small text-uppercase">Filtres</h6>
                            <div class="mb-3">
                                <label class="form-label small">Rôle système</label>
                                <select class="form-select form-select-sm" v-model="filtreRole">
                                    <option value="">Tous les rôles</option>
                                    <option v-for="role in allRoles" :value="role.name">@{{ role.label || roleLabel(role.name) }}</option>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="button" class="btn btn-primary btn-sm" @click="filtreRole = ''">Réinitialiser</button>
                            </div>
                        </div>
                    </div>
                    @can('users.create')
                    <button type="button" class="btn btn-primary btn-sm px-3" @click="openCreateUser">
                        <i class="ti ti-plus me-1"></i>Nouvel utilisateur
                    </button>
                    @endcan
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
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading"><td colspan="5" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Chargement…</td></tr>
                            <tr v-else-if="!filteredUsers.length"><td colspan="5" class="text-center py-5 text-muted">Aucun utilisateur trouvé</td></tr>
                            <tr v-for="u in filteredUsers" :key="u.id">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-md bg-label-primary rounded-circle me-3">
                                            <span class="fw-bold">@{{ u.name.charAt(0) }}</span>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-dark">@{{ u.name }}</span>
                                            <small class="text-muted">Créé le @{{ formatDateTime(u.created_at) }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-dark">@{{ u.email }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-soft-info">@{{ u.role_label }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-soft-success text-success">Actif</span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        @can('users.update')
                                        <button type="button" class="btn btn-icon btn-sm btn-label-warning" @click="getAccess(u)" title="Gérer les accès">
                                            <i class="ti ti-shield-lock"></i>
                                        </button>
                                        <button type="button" class="btn btn-icon btn-sm btn-label-primary" @click="editUser(u)" title="Modifier">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        @endcan
                                        @can('users.delete')
                                        <button v-if="u.id !== currentUserId" type="button" class="btn btn-icon btn-sm btn-label-danger" title="Supprimer">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal create/edit User -->
        @canany(['users.create','users.update'])
        <div class="modal fade" id="add_users" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary py-3">
                        <h5 class="modal-title text-white fw-bold">@{{ form.user_id ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="createUser">
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Nom complet <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control border-2" v-model="form.name" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Adresse Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control border-2" v-model="form.email" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Rôle système <span class="text-danger">*</span></label>
                                    <select class="form-select border-2" v-model="form.role" required>
                                        <option value="" hidden>Sélectionner un rôle</option>
                                        <option v-for="role in allRoles" :value="role.name">@{{ role.label || roleLabel(role.name) }}</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Mot de passe @{{ form.user_id ? '(Optionnel)' : '*' }}</label>
                                    <input type="password" class="form-control border-2" v-model="form.password" :required="!form.user_id">
                                    <div class="form-text small" v-if="form.user_id">Laissez vide pour conserver le mot de passe actuel.</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-0">
                            <button type="button" class="btn btn-white border px-4" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary px-4" :disabled="isLoading">
                                <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endcanany

        <!-- Modal Accès/Droits -->
        @can('users.update')
        <div class="modal fade" id="access_users" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-info py-3">
                        <h5 class="modal-title text-white fw-bold">Habilitations spécifiques</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="addAccess">
                        <div class="modal-body p-4">
                            <div class="alert bg-label-info border-0 mb-4">
                                <p class="mb-0 small"><i class="ti ti-info-circle me-1"></i>Ces droits s'ajoutent à ceux déjà hérités par le rôle principal de l'utilisateur.</p>
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
        @endcan
    </template>
</div>
@endsection

@push('styles')
<style>
    .table-custom thead th { background-color: #f8f9fa; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; font-weight: 700; padding: 12px 15px; border-bottom: 2px solid #dee2e6; color: #475569; }
    .table-custom tbody td { padding: 12px 15px; vertical-align: middle; font-size: 13.5px; border-bottom: 1px solid #f1f5f9; }
    .btn-label-primary { background: #e7e7ff; color: #696cff; border: none; }
    .btn-label-primary:hover { background: #696cff; color: #fff; }
    .btn-label-warning { background: #fff4e5; color: #ff9f43; border: none; }
    .btn-label-warning:hover { background: #ff9f43; color: #fff; }
    .btn-label-danger { background: #ffe5e5; color: #ff3e1d; border: none; }
    .btn-label-danger:hover { background: #ff3e1d; color: #fff; }
    .bg-label-primary { background-color: #e7e7ff; color: #696cff; }
    .bg-label-info { background-color: #d7f5fc; color: #03c3ec; }
    .bg-light-soft { background-color: #f8fafc; }
    .search-box { min-width: 280px; }
</style>
@endpush

@push('scripts')
<script>
    window.__CURRENT_USER_ID__ = @json(Auth::id());
</script>
<script type="module" src="{{ asset('assets/js/scripts/user.js') }}"></script>
@endpush
