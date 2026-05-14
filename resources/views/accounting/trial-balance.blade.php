@extends('layouts.app')

@section('content')
<div class="content pb-0">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Balance Générale</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Comptabilité</li>
                    <li class="breadcrumb-item active" aria-current="page">Balance</li>
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
                <input type="text" class="form-control" placeholder="Filtrer par compte...">
            </div>
        </div>
        <div class="card-body">

            <!-- table header filters -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="reportrange-picker reportrange d-flex align-items-center shadow">
                        <i class="ti ti-calendar-due text-dark fs-14 me-1"></i>
                        <span class="reportrange-picker-field">Période de vérification</span>
                    </div>
                </div>
            </div>
            <!-- /table header filters -->

            <div class="table-responsive custom-table table-nowrap">
                <table class="table table-nowrap datatable">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2">Compte</th>
                            <th rowspan="2">Intitulé</th>
                            <th colspan="2" class="text-center">Mouvements</th>
                            <th colspan="2" class="text-center">Soldes</th>
                        </tr>
                        <tr>
                            <th>Débit</th>
                            <th>Crédit</th>
                            <th>Débiteur</th>
                            <th>Créditeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>101000</td>
                            <td>Capital social</td>
                            <td>0.00</td>
                            <td>50,000.00</td>
                            <td>0.00</td>
                            <td>50,000.00</td>
                        </tr>
                        <tr>
                            <td>411100</td>
                            <td>Clients</td>
                            <td>1,200.00</td>
                            <td>0.00</td>
                            <td>1,200.00</td>
                            <td>0.00</td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="2"><strong>TOTAL GENERAL</strong></td>
                            <td><strong>51,200.00</strong></td>
                            <td><strong>51,200.00</strong></td>
                            <td><strong>51,200.00</strong></td>
                            <td><strong>51,200.00</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
