@extends("layouts.app")

@section("content")
    <div class="content pb-0" id="App">
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-1">Rôles & Permissions <span class="badge badge-soft-primary ms-2">@{{ allRoles.length }}</span></h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Administration</li>
                        <li class="breadcrumb-item active" aria-current="page">Rôles</li>
                    </ol>
                </nav>
            </div>
            <div class="gap-2 d-flex align-items-center flex-wrap">
                <div class="dropdown">
                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light px-2 shadow"
                        data-bs-toggle="dropdown"><i class="ti ti-package-export me-2"></i>Export</a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <ul>
                            <li><a href="javascript:void(0);" class="dropdown-item"><i class="ti ti-file-type-pdf me-1"></i>Exporter en PDF</a></li>
                            <li><a href="javascript:void(0);" class="dropdown-item"><i class="ti ti-file-type-xls me-1"></i>Exporter en Excel</a></li>
                        </ul>
                    </div>
                </div>
                <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="Refresh"><i class="ti ti-refresh"></i></a>
                <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="Collapse" id="collapse-header"><i
                        class="ti ti-transition-top"></i></a>
            </div>
        </div>
        <!-- /Page Header -->

        <!-- card start -->
        <div class="card border-0 rounded-0">
            <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <div class="input-icon input-icon-start position-relative">
                    <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" placeholder="Rechercher un rôle...">
                </div>
                @can('roles.create')
                <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#role-create">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Ajout Rôle
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
                                    <li><a href="javascript:void(0);" class="dropdown-item">Nom A-Z</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Date création</a></li>
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
                        </div>
                    </div>
                </div>
                <!-- /table header filters -->

                <!-- Roles List -->
                <div class="table-responsive custom-table table-nowrap">
                    <table class="table table-nowrap datatable" v-cloak>
                        <thead class="table-light">
                        <tr>
                            <th class="no-sort">
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th>Libellé du Rôle</th>
                            <th>Date de création</th>
                            <th>Dernière modification</th>
                            <th>Statut</th>
                            <th class="no-sort">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(data, index) in allRoles">
                            <td>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox">
                                </div>
                            </td>
                            <td class="fw-medium text-dark">@{{ data.name }}</td>
                            <td>@{{ data.created_at }}</td>
                            <td>@{{ data.updated_at }}</td>
                            <td>
                                <span class="badge badge-success d-inline-flex align-items-center badge-xs">
                                    <i class="ti ti-point-filled me-1"></i>Actif
                                </span>
                            </td>
                            <td class="action-table-data">
                                <div class="edit-delete-action">
                                    @can('roles.update')
                                        <a href="javascript:void(0);" class="me-2 p-2" @click="editRole(data)" title="Modifier">
                                            <i :class="{'text-gray-3':data.name==='admin' || data.name==='manager'}" class="ti ti-edit text-info"></i>
                                        </a>
                                    @endcan
                                    @can('roles.delete')
                                        <a href="javascript:void(0);" class="p-2" data-bs-toggle="modal" data-bs-target="#delete_modal" title="Supprimer">
                                            <i class="ti ti-trash text-danger" :class="{'text-gray-3':data.name==='admin' || data.name==='manager'}"></i>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="row align-items-center mt-3">
                    <div class="col-md-6"><div class="datatable-length"></div></div>
                    <div class="col-md-6"><div class="datatable-paginate"></div></div>
                </div>
                <!-- /Roles List -->
            </div>
        </div>
        <!-- card end -->

        <!-- Modal create/edit Role -->
        @canany(['roles.create','roles.update'])
        <div class="modal fade" id="role-create">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">@{{ formRole.role_id ? 'Modifier le Rôle' : 'Création Rôle utilisateur' }}</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="createRole">
                        <div class="modal-body pb-0">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Libellé du rôle</label>
                                <input v-model="formRole.name" type="text" class="form-control" placeholder="ex: Comptable Senior" required>
                            </div>

                            <div class="table-responsive custom-table">
                                <table class="table">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Module Permissions</th>
                                        <th v-for="col in ['voir', 'créer', 'modifier', 'supprimer', 'importer', 'exporter']" :key="col" class="text-center">@{{ col.charAt(0).toUpperCase() + col.slice(1) }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="module in allActions" :key="module.entity">
                                        <td>
                                            <h6 class="fs-14 fw-normal text-gray-9 mb-0">@{{ module.label }}</h6>
                                        </td>
                                        <td v-for="col in ['view', 'create', 'update', 'delete', 'import', 'export']" :key="col" class="text-center">
                                            <div class="form-check form-check-md d-inline-block">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    :value="`${module.entity}.${col}`"
                                                    v-model="formRole.permissions"
                                                    :disabled="!module.actions.some(a => a.action === col)"
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
                            <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                <span v-if="formRole.role_id">@{{ isLoading ? "Mise à jour..." : "Mettre à jour" }}</span>
                                <span v-else>@{{ isLoading ? "Enregistrement..." : "Enregistrer" }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endcanany
    </div>
@endsection

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/user.js") }}"></script>
@endpush
