@extends('layouts.app')

@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-1">Journal d'audit</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Administration</li>
                        <li class="breadcrumb-item active">Journal d'audit</li>
                    </ol>
                </nav>
            </div>
            <div class="gap-2 d-flex align-items-center flex-wrap">
                <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" @click="viewAuditLogs" :disabled="isLoading" title="Actualiser">
                    <i class="ti ti-refresh" :class="{'ti-spin': isLoading}"></i>
                </a>
            </div>
        </div>

        <div class="card border-0 rounded-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="audit-logs-table">
                        <thead class="table-light">
                        <tr>
                            <th>Date / Heure</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Référence</th>
                            <th>Description</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="log in logs" :key="log.id">
                            <td class="small text-nowrap" :data-order="log.created_at_iso">@{{ log.created_at }}</td>
                            <td>
                                <div class="fw-bold">@{{ log.user_name }}</div>
                                <div class="small text-muted" v-if="log.user_email">@{{ log.user_email }}</div>
                            </td>
                            <td><span class="badge bg-soft-info text-info">@{{ log.action }}</span></td>
                            <td><code class="small">@{{ log.reference || '—' }}</code></td>
                            <td>@{{ log.description || '—' }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@push('styles')
<style>
    .ti-spin { animation: ti-spin 2s infinite linear; }
    @keyframes ti-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .bg-soft-info { background-color: #e0f2fe; color: #0369a1; }
    /* Masquer le champ de recherche par défaut si nécessaire pour le style */
    .dataTables_filter input { border: 1px solid #dee2e6; border-radius: 4px; padding: 4px 8px; outline: none; }
</style>
@endpush

@push('scripts')
    <script type="module" src="{{ asset('assets/js/scripts/user.js') }}"></script>
@endpush
