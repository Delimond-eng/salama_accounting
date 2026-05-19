@extends('layouts.app')

@section('content')
<div class="content pb-0">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-0">Réouverture d'exercice</h4>
            <p class="text-muted mb-0">Initialisation des reports à nouveau pour le nouvel exercice</p>
        </div>
        <div class="gap-2 d-flex align-items-center flex-wrap">
            <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" data-bs-toggle="tooltip" title="Refresh"><i class="ti ti-refresh"></i></a>
        </div>
    </div>
    <!-- /Page Header -->

    <div class="card">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h5>Génération du Bilan d'Ouverture</h5>
                    <p class="text-muted">Cette opération va récupérer les soldes de clôture de l'exercice précédent et créer les écritures de reports à nouveau (RAN) dans le journal d'ouverture.</p>

                    <div class="alert alert-info border-0 mb-4">
                        <div class="d-flex">
                            <i class="ti ti-info-circle me-2 fs-4 text-info"></i>
                            <div>
                                <strong>Exercice source :</strong> 2023 <br>
                                <strong>Nouvel exercice :</strong> 2024
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Journal d'ouverture destination</label>
                        <select class="form-select">
                            <option>RAN - Reports à nouveau</option>
                            <option>JO - Journal d'Ouverture</option>
                        </select>
                    </div>

                    <button class="btn btn-primary d-inline-flex align-items-center">
                        <i class="ti ti-player-play me-2"></i>Générer les reports à nouveau
                    </button>
                </div>
                <div class="col-lg-4 text-center d-none d-lg-block">
                    <img src="{{ asset('assets/img/book.webp') }}" alt="Reopening" class="img-fluid" style="max-height: 200px; opacity: 0.5;">
                </div>
            </div>
        </div>
    </div>

    <div class="card table-list-card">
        <div class="card-header">
            <h5 class="card-title">Historique des ouvertures</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table datatable">
                    <thead>
                        <tr>
                            <th>Date Opération</th>
                            <th>Exercice Cible</th>
                            <th>Utilisateur</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>01/01/2024</td>
                            <td>2024</td>
                            <td>Admin Salama</td>
                            <td><span class="badge bg-success-light">Terminé</span></td>
                            <td><a href="#" class="btn btn-sm btn-outline-info">Voir détail</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
