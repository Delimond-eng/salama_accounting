@extends('layouts.app')

@section('content')
<div class="content pb-0">

    <div class="module-hub-header mb-4">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $module['title'] }}</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-2">
                    <div class="module-hub-icon-sm bg-{{ $module['color'] ?? 'primary' }}-subtle text-{{ $module['color'] ?? 'primary' }}">
                        <i class="ti {{ $module['icon'] }}"></i>
                    </div>
                    <h4 class="mb-0 fw-bold">{{ $module['title'] }}</h4>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="text-end d-none d-sm-block me-2">
                    <p class="text-muted mb-0 small fw-medium">{{ $module['subtitle'] ?? '' }}</p>
                    <span class="text-primary fs-12 fw-bold">{{ count($items) }} services disponibles</span>
                </div>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-light shadow-sm btn-icon rounded-circle">
                    <i class="ti ti-smart-home"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        @forelse ($items as $item)
        <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-6">
            <a href="{{ $item['url'] }}" class="module-card-link text-decoration-none d-block h-100">
                <div class="card module-card h-100 border-0 shadow-sm">
                    <div class="card-body p-3 d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="module-card-icon-sm bg-{{ $module['color'] ?? 'primary' }}-subtle text-{{ $module['color'] ?? 'primary' }}">
                                <i class="ti {{ $item['icon'] }}"></i>
                            </div>
                            @if (!empty($item['coming_soon']))
                                <span class="badge bg-soft-secondary fs-10">Bientôt</span>
                            @else
                                <i class="ti ti-chevron-right text-muted fs-12 module-card-arrow"></i>
                            @endif
                        </div>
                        <h6 class="mb-1 text-dark fw-bold text-truncate">{{ $item['title'] }}</h6>
                        <p class="text-muted mb-0 miniature-description flex-grow-1">{{ $item['description'] }}</p>
                    </div>
                </div>
            </a>
        </div>
        @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="ti ti-lock text-muted fs-1 mb-3 d-block"></i>
                    <p class="text-muted mb-0">Aucun sous-menu accessible avec vos droits actuels.</p>
                </div>
            </div>
        </div>
        @endforelse
    </div>

</div>

@push('styles')
<style>
    .module-hub-header {
        padding: 1rem 0;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .module-hub-icon-sm {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .module-card {
        border-radius: 12px !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        background: #fff;
        border: 1px solid transparent !important;
    }
    .module-card-link:hover .module-card {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
        border-color: rgba(var(--bs-primary-rgb), 0.2) !important;
    }
    .module-card-icon-sm {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }
    .module-card-link:hover .module-card-icon-sm {
        transform: scale(1.1);
    }
    .miniature-description {
        font-size: 11.5px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .module-card-arrow {
        transition: transform 0.2s ease;
    }
    .module-card-link:hover .module-card-arrow {
        transform: translateX(3px);
        color: var(--bs-primary) !important;
    }
    .fs-10 { font-size: 10px; }
</style>
@endpush
@endsection
