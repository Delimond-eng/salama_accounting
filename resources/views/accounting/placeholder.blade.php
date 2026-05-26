@extends('layouts.app')

@section('content')
<div class="content pb-0">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                </ol>
            </nav>
            <h4 class="mb-1">{{ $label }}</h4>
            <p class="text-muted mb-0">Module en cours de développement</p>
        </div>
        <a href="javascript:history.back()" class="btn btn-outline-light shadow-sm">
            <i class="ti ti-arrow-left me-1"></i> Retour
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <div class="avatar avatar-xl bg-primary-subtle text-primary rounded-circle mx-auto mb-3">
                <i class="ti ti-tools fs-24"></i>
            </div>
            <h5 class="mb-2">Fonctionnalité à venir</h5>
            <p class="text-muted mb-0 mx-auto" style="max-width: 420px;">
                Cette section SYSCOHADA sera disponible prochainement dans {{ $appBrand ?? 'Millenium ERP' }}.
            </p>
        </div>
    </div>
</div>
@endsection
