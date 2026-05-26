@extends('layouts.app')

@section('content')
<div class="content pb-0">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Balance Auxiliaire<span class="badge badge-soft-primary ms-2">15</span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Comptabilité</li>
                    <li class="breadcrumb-item active" aria-current="page">Balance Auxiliaire</li>
                </ol>
            </nav>
        </div>
        <div class="gap-2 d-flex align-items-center flex-wrap">
            <div class="dropdown">
                <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light px-2 shadow"
                    data-bs-toggle="dropdown"><i class="ti ti-package-export me-2"></i>Export</a>
                <div class="dropdown-menu dropdown-menu-end">
                    <ul>
                        <li><a href="javascript:void(0);" class="dropdown-item"><i class="ti ti-file-type-pdf me-1"></i>PDF</a></li>
                        <li><a href="javascript:void(0);" class="dropdown-item"><i class="ti ti-file-type-xls me-1"></i>Excel</a></li>
                    </ul>
                </div>
            </div>
            <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow"
                data-bs-toggle="tooltip" data-bs-placement="top" title="Refresh"><i class="ti ti-refresh"></i></a>
            <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow"
                data-bs-toggle="tooltip" data-bs-placement="top" title="Collapse" id="collapse-header"><i class="ti ti-transition-top"></i></a>
        </div>
    </div>
    <!-- /Page Header -->

    <!-- card start -->
    <div class="card border-0 rounded-0">
        <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <div class="input-icon input-icon-start position-relative">
                <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                <input type="text" class="form-control" placeholder="Rechercher un tiers...">
            </div>
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
                                <li><a href="javascript:void(0);" class="dropdown-item">Nom</a></li>
                                <li><a href="javascript:void(0);" class="dropdown-item">Solde décroissant</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="reportrange-picker reportrange d-flex align-items-center shadow">
                        <i class="ti ti-calendar-due text-dark fs-14 me-1"></i>
                        <span class="reportrange-picker-field">Période</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="btn btn-outline-light shadow px-2"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                                class="ti ti-filter me-2"></i>Type de tiers</a>
                        <div class="dropdown-menu p-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="cli" checked>
                                <label class="form-check-label" for="cli">Clients</label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="fou" checked>
                                <label class="form-check-label" for="fou">Fournisseurs</label>
                            </div>
                            <button class="btn btn-primary btn-sm w-100">Appliquer</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /table header filters -->

            <div class="table-responsive custom-table table-nowrap">
                <table class="table table-nowrap datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Code Tiers</th>
                            <th>Nom / Raison Sociale</th>
                            <th>Compte Collectif</th>
                            <th>Débit</th>
                            <th>Crédit</th>
                            <th>Solde</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>CLI-001</td>
                            <td>Client démo</td>
                            <td>411100</td>
                            <td>1,200.00</td>
                            <td>0.00</td>
                            <td>1,200.00 Dr</td>
                        </tr>
                        <tr>
                            <td>FOU-005</td>
                            <td>OFFICE DEPOT</td>
                            <td>401100</td>
                            <td>0.00</td>
                            <td>450.00</td>
                            <td>450.00 Cr</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="row align-items-center mt-3">
                <div class="col-md-6"><div class="datatable-length"></div></div>
                <div class="col-md-6"><div class="datatable-paginate"></div></div>
            </div>
        </div>
    </div>
</div>
@endsection
