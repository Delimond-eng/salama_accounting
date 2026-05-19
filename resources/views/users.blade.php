@extends("layouts.app")

@section("content")

<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
        <div v-if="errorList.length" class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0 ps-3">
                <li v-for="(err, i) in errorList" :key="i">@{{ err }}</li>
            </ul>
            <button type="button" class="btn-close" @click="error = null"></button>
        </div>
        <div v-if="message" class="alert alert-success alert-dismissible fade show" role="alert">
            @{{ message }}
            <button type="button" class="btn-close" @click="message = null"></button>
        </div>
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-1">Utilisateurs <span class="badge badge-soft-primary ms-2">@{{ allUsers.length }}</span></h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Administration</li>
                        <li class="breadcrumb-item active" aria-current="page">Utilisateurs</li>
                    </ol>
                </nav>
            </div>
            <div class="gap-2 d-flex align-items-center flex-wrap">
                @include('components.export-buttons')
                <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow"
                    data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh"
                    data-bs-original-title="Refresh"><i class="ti ti-refresh"></i></a>
                <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow"
                    data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Collapse"
                    data-bs-original-title="Collapse" id="collapse-header"><i
                        class="ti ti-transition-top"></i></a>
            </div>
        </div>
        <!-- /Page Header -->

        <!-- card start -->
        <div class="card border-0 rounded-0">
            <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <div class="input-icon input-icon-start position-relative">
                    <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" placeholder="Rechercher un utilisateur...">
                </div>
                @can('users.create')
                <a href="javascript:void(0);" class="btn btn-primary" @click="openCreateUser">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Ajout Utilisateur
                </a>
                @endcan
            </div>
            <div class="card-body">

                <!-- table header filters -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light shadow"
                                data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Trier par</a>
                            <div class="dropdown-menu">
                                <ul>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Plus récent</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Plus ancien</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="btn btn-outline-light shadow px-2"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                                    class="ti ti-filter me-2"></i>Filtrer<i
                                    class="ti ti-chevron-down ms-2"></i></a>
                            <div class="filter-dropdown-menu dropdown-menu dropdown-menu-lg p-0">
                                <div class="filter-header d-flex align-items-center justify-content-between border-bottom p-3">
                                    <h4 class="mb-0 fs-16"><i class="ti ti-filter me-1"></i>Filtres</h4>
                                </div>
                                <div class="p-3">
                                    <div class="mb-3">
                                        <label class="form-label">Rôle</label>
                                        <select class="form-select">
                                            <option value="">Tous les rôles</option>
                                            <option v-for="role in allRoles" :value="role.name">@{{ role.label || roleLabel(role.name) }}</option>
                                        </select>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="javascript:void(0);" class="btn btn-outline-light w-100">Réinitialiser</a>
                                        <a href="#" class="btn btn-primary w-100">Appliquer</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="btn bg-soft-indigo border-0"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                                    class="ti ti-columns-3 me-2"></i>Colonnes</a>
                            <div class="dropdown-menu dropdown-menu-md dropdown-md p-3">
                                <ul>
                                    <li class="gap-1 d-flex align-items-center mb-2">
                                        <div class="form-check form-switch w-100 ps-0">
                                            <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                <span>Email</span>
                                                <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>
                                            </label>
                                        </div>
                                    </li>
                                    <li class="gap-1 d-flex align-items-center mb-2">
                                        <div class="form-check form-switch w-100 ps-0">
                                            <label class="form-check-label d-flex align-items-center gap-2 w-100">
                                                <span>Rôle</span>
                                                <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>
                                            </label>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /table header filters -->

                <!-- User List -->
                <div class="table-responsive custom-table table-nowrap">
                    <table class="table table-nowrap datatable" v-cloak>
                        <thead class="table-light">
                        <tr>
                            <th class="no-sort">
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Créé le</th>
                            <th class="no-sort">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(data, index) in allUsers">
                            <td>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox">
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center file-name-icon">
                                    <a href="javascript:void(0);" class="avatar avatar-md avatar-rounded">
                                        <img src="{{asset("assets/img/avatar.jpg")}}" class="img-fluid" alt="img">
                                    </a>
                                    <div class="ms-2">
                                        <h6 class="fw-medium mb-0">@{{ data.name }}</h6>
                                    </div>
                                </div>
                            </td>
                            <td>@{{ data.email }}</td>
                            <td>
                                <span class="badge badge-md p-2 fs-100 badge-soft-info"
                                    >@{{ data.role_label }}</span>
                            </td>
                            <td>@{{ formatDateTime(data.created_at) }}</td>
                            <td class="action-table-data">
                                <div class="edit-delete-action">
                                    @can('users.update')
                                        <a href="javascript:void(0);" class="me-2 p-2" @click="getAccess(data)" title="Accès">
                                            <i :class="{'text-gray-3': isProtectedRole(data.roles?.[0]?.name || data.role)}" class="ti ti-shield-lock text-warning"></i>
                                        </a>
                                        <a href="javascript:void(0);" class="me-2 p-2" @click="editUser(data)" title="Modifier">
                                            <i class="ti ti-edit text-info"></i>
                                        </a>
                                    @endcan
                                    @can('users.delete')
                                        <a href="javascript:void(0);" class="p-2" data-bs-toggle="modal" data-bs-target="#delete_modal" title="Supprimer">
                                            <i class="ti ti-trash text-danger"></i>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="row align-items-center mt-3">
                    <div class="col-md-6">
                        <div class="datatable-length"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="datatable-paginate"></div>
                    </div>
                </div>
                <!-- /User List -->

            </div>
        </div>
        <!-- card end -->

        <!-- Modal create/edit User -->
        @canany(['users.create','users.update'])
        <div class="modal fade" id="add_users">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">@{{ form.user_id ? 'Modifier l\'utilisateur' : 'Création compte utilisateur' }}</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="createUser">
                        <div class="modal-body pb-0">
                            <div v-if="errorList.length" class="alert alert-danger py-2 mb-3">
                                <ul class="mb-0 ps-3 small">
                                    <li v-for="(err, i) in errorList" :key="i">@{{ err }}</li>
                                </ul>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nom d'utilisateur</label>
                                        <input type="text" class="form-control" v-model="form.name" placeholder="ex: Gaston" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" v-model="form.email" class="form-control" placeholder="exemple@domain" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Mot de passe @{{ form.user_id ? '(laisser vide si inchangé)' : '' }}</label>
                                        <div class="pass-group">
                                            <input type="password" v-model="form.password" placeholder="***************" class="pass-input form-control" :required="!form.user_id">
                                            <span class="ti toggle-password ti-eye-off"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Rôle</label>
                                        <select class="form-select" v-model="form.role" required>
                                            <option value="" hidden selected>--Sélectionner un rôle</option>
                                            <option v-for="(data, i) in allRoles" :value="data.name">@{{ data.label || roleLabel(data.name) }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white border me-2"
                                    data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                @{{ isLoading ? "Enregistrement..." : "Enregistrer" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endcanany

        <!-- Modal access -->
        @can('users.update')
        <div class="modal fade" id="access_users">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Attribution accès utilisateur</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="addAccess">
                        <div class="modal-body pb-0">
                            <div class="table-responsive custom-table">
                                <table class="table">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Module Permissions</th>
                                        <th v-for="col in permissionColumns" :key="col" class="text-center">@{{ columnLabels[col] || col }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="module in allActions" :key="module.entity">
                                        <td>
                                            <h6 class="fs-14 fw-normal text-gray-9 mb-0">@{{ module.label }}</h6>
                                        </td>
                                        <td v-for="col in permissionColumns" :key="col" class="text-center">
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

                        <div class="modal-footer">
                            <button type="button" class="btn btn-white border me-2" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-info" :disabled="isLoading">
                                @{{ isLoading ? "Mise à jour..." : "Mettre à jour" }}
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

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/user.js") }}"></script>
@endpush
