@extends('layouts.app')

@section('content')
<div class="content pb-0">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Journal Comptable<span class="badge badge-soft-primary ms-2">1,245</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Comptabilité</li>
                    <li class="breadcrumb-item active" aria-current="page">Journal</li>
                </ol>
            </nav>
        </div>
        <div class="gap-2 d-flex align-items-center flex-wrap">
            <div class="dropdown">
                <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light px-2 shadow"
                    data-bs-toggle="dropdown"><i class="ti ti-package-export me-2"></i>Export</a>
                <div class="dropdown-menu dropdown-menu-end">
                    <ul>
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item"><i
                                    class="ti ti-file-type-pdf me-1"></i>Exporter en PDF</a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item"><i
                                    class="ti ti-file-type-xls me-1"></i>Exporter en Excel</a>
                        </li>
                    </ul>
                </div>
            </div>
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
                <input type="text" class="form-control" placeholder="Rechercher une écriture...">
            </div>
            <a href="javascript:void(0);" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-entry">
                <i class="ti ti-square-rounded-plus-filled me-1"></i>Nouvelle Écriture
            </a>
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
                    <div class="reportrange-picker reportrange d-flex align-items-center shadow">
                        <i class="ti ti-calendar-due text-dark fs-14 me-1"></i>
                        <span class="reportrange-picker-field">01 Juin 24 - 30 Juin 24</span>
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
                                    <label class="form-label">Journal</label>
                                    <select class="form-select">
                                        <option>Tous les journaux</option>
                                        <option>Ventes (VT)</option>
                                        <option>Achats (AC)</option>
                                        <option>Banque (BQ)</option>
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
                                            <span>Journal</span>
                                            <input class="form-check-input switchCheckDefault ms-auto" type="checkbox" role="switch" checked>
                                        </label>
                                    </div>
                                </li>
                                <!-- More columns can be added here -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /table header filters -->

            <!-- Journal List -->
            <div class="table-responsive custom-table table-nowrap">
                <table class="table table-nowrap datatable" id="journal-list">
                    <thead class="table-light">
                        <tr>
                            <th class="no-sort">
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th>Date</th>
                            <th>Pièce</th>
                            <th>Journal</th>
                            <th>Compte</th>
                            <th>Libellé</th>
                            <th>Débit</th>
                            <th>Crédit</th>
                            <th class="no-sort">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox">
                                </div>
                            </td>
                            <td>12 Juin 2024</td>
                            <td>FAC-001</td>
                            <td>Ventes (VT)</td>
                            <td>411100 - Clients</td>
                            <td>Facture Client - Client démo</td>
                            <td>1,200.00</td>
                            <td>0.00</td>
                            <td class="action-table-data">
                                <div class="edit-delete-action">
                                    <a class="me-2 p-2" href="#"><i class="ti ti-edit text-info"></i></a>
                                    <a class="p-2" href="#"><i class="ti ti-trash text-danger"></i></a>
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
            <!-- /Journal List -->

        </div>
    </div>
    <!-- card end -->
</div>
@endsection
