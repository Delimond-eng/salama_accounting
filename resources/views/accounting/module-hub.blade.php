@extends('layouts.app')

@section('content')
<div class="content pb-0">

    <div class="module-hub-hero mb-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $module['title'] }}</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-3">
                    <div class="module-hub-hero-icon bg-{{ $module['color'] ?? 'primary' }}-subtle text-{{ $module['color'] ?? 'primary' }}">
                        <i class="ti {{ $module['icon'] }}"></i>
                    </div>
                    <div>
                        <p class="text-muted mb-1 small fw-medium">{{ $module['number'] ?? '' }} Module comptable</p>
                        <h4 class="mb-1">{{ $module['title'] }}</h4>
                        <p class="text-muted mb-0">{{ $module['subtitle'] ?? '' }}</p>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge badge-soft-{{ $module['color'] ?? 'primary' }} px-3 py-2">
                    {{ count($items) }} fonction{{ count($items) > 1 ? 's' : '' }}
                </span>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-light shadow-sm">
                    <i class="ti ti-arrow-left me-1"></i> Retour
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @forelse ($items as $item)
        <div class="col-xxl-3 col-xl-4 col-md-6">
            <a href="{{ $item['url'] }}" class="module-card-link text-decoration-none d-block h-100">
                <div class="card module-card h-100 border-0 shadow-sm">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="module-card-icon bg-{{ $module['color'] ?? 'primary' }}-subtle text-{{ $module['color'] ?? 'primary' }}">
                                <i class="ti {{ $item['icon'] }}"></i>
                            </div>
                            @if (!empty($item['coming_soon']))
                            <span class="badge badge-soft-secondary">Bientôt</span>
                            @else
                            <i class="ti ti-arrow-up-right text-muted module-card-arrow"></i>
                            @endif
                        </div>
                        <h5 class="mb-2 text-dark">{{ $item['title'] }}</h5>
                        <p class="text-muted mb-0 small flex-grow-1">{{ $item['description'] }}</p>
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
    .module-hub-hero {
        background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.06) 0%, rgba(var(--bs-info-rgb), 0.04) 100%);
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 16px;
        padding: 1.5rem 1.75rem;
    }
    .module-hub-hero-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    .module-card {
        border-radius: 14px !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        background: #fff;
    }
    .module-card-link:hover .module-card {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.1) !important;
    }
    .module-card-link:hover .module-card-arrow {
        color: var(--bs-primary) !important;
        transform: translate(2px, -2px);
    }
    .module-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }
    .module-card-arrow {
        font-size: 1.1rem;
        transition: transform 0.2s ease, color 0.2s ease;
    }
</style>
@endpush
@endsection
