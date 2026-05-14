@extends('layouts.app')

@section('content')
<div class="content pb-0">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-0">Clôture Comptable</h4>
            <p class="text-muted mb-0">Processus de clôture périodique ou annuelle</p>
        </div>
        <div class="gap-2 d-flex align-items-center flex-wrap">
            <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" title="Refresh"><i class="ti ti-refresh"></i></a>
        </div>
    </div>
    <!-- /Page Header -->

    <div class="row">
        <div class="col-xl-4 col-md-6 d-flex">
            <div class="card flex-fill">
                <div class="card-header pb-0">
                    <h5 class="card-title">Clôture Mensuelle</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Verrouiller les écritures pour le mois en cours afin d'éviter toute modification ultérieure.</p>
                    <div class="form-group mb-3">
                        <label class="form-label">Mois à clôturer</label>
                        <select class="form-select">
                            <option>Juin 2024</option>
                            <option>Mai 2024</option>
                        </select>
                    </div>
                    <button class="btn btn-warning w-100 mt-3"><i class="ti ti-lock me-2"></i>Lancer la clôture</button>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 d-flex">
            <div class="card flex-fill">
                <div class="card-header pb-0">
                    <h5 class="card-title">Clôture Annuelle</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Génération automatique des écritures de résultat et clôture définitive de l'exercice.</p>
                    <div class="form-group mb-3">
                        <label class="form-label">Exercice</label>
                        <input type="text" class="form-control" value="2023" readonly>
                    </div>
                    <button class="btn btn-danger w-100 mt-3"><i class="ti ti-archive me-2"></i>Clôturer l'exercice</button>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 d-flex">
            <div class="card flex-fill bg-info-light">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <i class="ti ti-info-circle mb-2 fs-1 text-info"></i>
                    <h5>Note Importante</h5>
                    <p>La clôture annuelle est irréversible. Assurez-vous que toutes les balances sont exactes et que les rapprochements bancaires sont terminés.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
