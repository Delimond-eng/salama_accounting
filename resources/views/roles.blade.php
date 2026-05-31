@extends("layouts.app")

@section("content")

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
        <div v-if="errorList.length" class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
            <ul class="mb-0 ps-3"><li v-for="(err, i) in errorList" :key="i">@{{ err }}</li></ul>
            <button type="button" class="btn-close" @click="error = null"></button>
        </div>

        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-1 text-dark fw-bold">Rôles & Habilitations</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Administration</li>
                        <li class="breadcrumb-item active" aria-current="page">Rôles</li>
                    </ol>
                </nav>
            </div>
            <div class="gap-2 d-flex align-items-center">
                @can('roles.create')
                <button type="button" class="btn btn-primary px-4 shadow-sm" @click="openRoleForm()">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Ajout Rôle
                </button>
                @endcan
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0 fw-bold text-primary">Matrice des droits d'accès</h5>
                    </div>
                    <div class="col-auto">
                        <div class="input-group input-group-sm rounded-2 px-2">
                            <span class="input-group-text"><i class="ti ti-search text-muted"></i></span>
                            <input type="text" class="form-control" placeholder="Rechercher un rôle..." v-model="searchRole">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-custom mb-0" id="roles-table">
                        <thead>
                            <tr>
                                <th>Libellé du Rôle</th>
                                <th>Code Système</th>
                                <th>Création</th>
                                <th>Dernière Maj.</th>
                                <th class="text-center">Statut</th>
                                <th class="text-end no-sort">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="data in filteredRoles" :key="data.id">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm bg-soft-primary text-primary rounded me-3">
                                            <i class="ti ti-shield-check fs-18"></i>
                                        </div>
                                        <span class="fw-bold text-dark">@{{ data.label || roleLabel(data.name) }}</span>
                                    </div>
                                </td>
                                <td><code class="fs-12">@{{ data.name }}</code></td>
                                <td class="text-muted fs-12">@{{ formatDateTime(data.created_at) }}</td>
                                <td class="text-muted fs-12">@{{ formatDateTime(data.updated_at) }}</td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-soft-success text-success px-3">Actif</span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        @can('roles.update')
                                        <button type="button" class="btn btn-icon btn-sm btn-label-primary" @click="editRole(data)" :disabled="isProtectedRole(data.name)">
                                            <i class="ti ti-edit"></i>
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

        <!-- Modal create/edit Role -->
        @canany(['roles.create','roles.update'])
        <div class="modal fade" id="role-modal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary py-3">
                        <h5 class="modal-title text-white fw-bold">@{{ formRole.role_id ? 'Configuration du Rôle' : 'Création d\'un Rôle' }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="createRole">
                        <div class="modal-body p-4">
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-uppercase">Libellé du rôle <span class="text-danger">*</span></label>
                                <input v-model="formRole.name" type="text" class="form-control border-2" placeholder="ex: Responsable Facturation" required>
                            </div>

                            <div class="permissions-matrix mt-4">
                                <h6 class="fw-bold mb-3"><i class="ti ti-key me-2 text-primary"></i>Matrice des permissions</h6>
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
                                                            v-model="formRole.permissions"
                                                        >
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-0">
                            <button type="button" class="btn btn-white border px-4" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary px-4" :disabled="isLoading">
                                <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                                @{{ formRole.role_id ? "Mettre à jour" : "Enregistrer le rôle" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endcanany
    </template>
</div>
@endsection

@push('styles')
<style>
    .table-custom thead th { background-color: #f8f9fa; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; font-weight: 700; padding: 12px 15px; border-bottom: 2px solid #dee2e6; color: #475569; }
    .table-custom tbody td { padding: 12px 15px; vertical-align: middle; font-size: 13.5px; border-bottom: 1px solid #f1f5f9; }
    .bg-light-soft { background-color: #f8fafc; }
    .btn-label-primary { background: #e7e7ff; color: #696cff; border: none; }
    .btn-label-danger { background: #ffe5e5; color: #ff3e1d; border: none; }
    .bg-soft-primary { background-color: rgba(63, 122, 253, 0.1); }
    .dataTables_filter { display: none; }
</style>
@endpush

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/user.js") }}"></script>
@endpush
