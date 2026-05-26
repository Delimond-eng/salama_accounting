@extends('layouts.app')

@section('content')
<div class="content pb-0">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-0">Lettrage des Comptes</h4>
            <p class="text-muted mb-0">Rapprochement des écritures (Factures vs Règlements)</p>
        </div>
        <div class="gap-2 d-flex align-items-center flex-wrap">
            <div class="daterangepick form-control w-auto d-flex align-items-center me-2">
                <i class="ti ti-calendar text-dark me-2"></i>
                <span class="reportrange-picker-field text-dark">01 Jan 2024 - 31 Dec 2024</span>
            </div>
            <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" title="Refresh"><i class="ti ti-refresh"></i></a>
        </div>
    </div>
    <!-- /Page Header -->

    <div class="row">
        <div class="col-md-6">
            <div class="card flex-fill">
                <div class="card-header">
                    <h5 class="card-title">Écritures non lettrées (Débit)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" class="form-check-input"></th>
                                    <th>Date</th>
                                    <th>Libellé</th>
                                    <th>Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"></td>
                                    <td>12/06/2024</td>
                                    <td>FAC-001 - Client démo</td>
                                    <td>1,200.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card flex-fill">
                <div class="card-header">
                    <h5 class="card-title">Écritures non lettrées (Crédit)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" class="form-check-input"></th>
                                    <th>Date</th>
                                    <th>Libellé</th>
                                    <th>Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"></td>
                                    <td>14/06/2024</td>
                                    <td>VIR Client - FAC-001</td>
                                    <td>1,200.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-3 mb-4">
        <button class="btn btn-primary btn-lg d-inline-flex align-items-center">
            <i class="ti ti-link me-2"></i>Effectuer le Lettrage
        </button>
    </div>
</div>
@endsection
