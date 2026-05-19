@extends('layouts.app')

@section('content')
<div class="content pb-0">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-0">Exports Comptables</h4>
            <p class="text-muted mb-0">Génération des fichiers d'échange et rapports officiels</p>
        </div>
        <div class="gap-2 d-flex align-items-center flex-wrap">
            <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" title="Refresh"><i class="ti ti-refresh"></i></a>
        </div>
    </div>
    <!-- /Page Header -->

    <div class="row">
        <div class="col-lg-4 col-sm-6 col-12 d-flex">
            <div class="card flex-fill">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="ti ti-file-type-pdf fs-1 text-danger"></i>
                    </div>
                    <h5>États Financiers (PDF)</h5>
                    <p class="text-muted">Bilan, Compte de Résultat et Tableaux de Financement au format PDF pour impression.</p>
                    <button class="btn btn-outline-primary w-100 mt-auto">Générer le rapport</button>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-6 col-12 d-flex">
            <div class="card flex-fill">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="ti ti-file-excel fs-1 text-success"></i>
                    </div>
                    <h5>Grand Livre Export (Excel)</h5>
                    <p class="text-muted">Export complet du grand livre avec filtres personnalisés pour analyse approfondie.</p>
                    <button class="btn btn-outline-success w-100 mt-auto">Exporter vers Excel</button>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-6 col-12 d-flex">
            <div class="card flex-fill">
                <div class="card-body">
                    <div class="mb-3">
                        <i class="ti ti-file-code fs-1 text-info"></i>
                    </div>
                    <h5>Fichier des Écritures (FEC)</h5>
                    <p class="text-muted">Génération du fichier normé pour les contrôles fiscaux ou transfert vers un autre logiciel.</p>
                    <button class="btn btn-outline-info w-100 mt-auto">Générer le fichier FEC</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Paramètres d'export</h5>
        </div>
        <div class="card-body">
            <form>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Période</label>
                            <select class="form-select">
                                <option>Année 2024</option>
                                <option>Année 2023</option>
                                <option>Personnalisé...</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Type de Journal</label>
                            <select class="form-select">
                                <option>Tous les journaux</option>
                                <option>Ventes uniquement</option>
                                <option>Achats uniquement</option>
                                <option>Trésorerie uniquement</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="mb-3 w-100">
                            <button type="button" class="btn btn-primary w-100 d-inline-flex align-items-center justify-content-center">
                                <i class="ti ti-settings me-2"></i>Appliquer les filtres
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
