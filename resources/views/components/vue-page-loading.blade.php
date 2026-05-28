<div class="vue-page-loading d-flex flex-column align-items-center justify-content-start pt-5" style="height: max-content">
    <div class="software-spinner-container mb-4">
        <svg width="48" height="48" viewBox="0 0 50 50" class="software-svg-spinner">
            <circle class="bg" cx="25" cy="25" r="20" fill="none" stroke="#f1f3f4" stroke-width="5"></circle>
            <circle class="path" cx="25" cy="25" r="20" fill="none" stroke="#3f7afd" stroke-width="5" stroke-linecap="round"></circle>
        </svg>
    </div>
    <div class="loader-text text-center">
        <h5 class="fw-bold text-dark mb-1" style="letter-spacing: 2px; font-size: 18px;">Chargement de la page...</h5>
        <div class="d-flex align-items-center justify-content-center gap-2">
            <span class="dot-animation"></span>
            <p class="text-muted small mb-0">Veuillez patienter pendant le chargement...</p>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .vue-page-loading {
        min-height: 400px;
        width: 100%;
        border-radius: 12px;
        transition: opacity 0.3s ease;
    }

    .software-svg-spinner {
        animation: rotate-software 2s linear infinite;
    }

    .software-svg-spinner .path {
        stroke-dasharray: 1, 150;
        stroke-dashoffset: 0;
        animation: dash-software 1.5s ease-in-out infinite;
    }

    @keyframes rotate-software {
        100% { transform: rotate(360deg); }
    }

    @keyframes dash-software {
        0% { stroke-dasharray: 1, 150; stroke-dashoffset: 0; }
        50% { stroke-dasharray: 90, 150; stroke-dashoffset: -35; }
        100% { stroke-dasharray: 90, 150; stroke-dashoffset: -124; }
    }

    .dot-animation {
        width: 4px;
        height: 4px;
        background-color: #3f7afd;
        border-radius: 50%;
        animation: dot-pulse 1.5s infinite ease-in-out;
    }

    @keyframes dot-pulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(2.5); opacity: 0.3; }
    }
</style>
@endpush
@endonce
