@extends('layouts.app')

@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-1">Journal d'audit <span class="badge badge-soft-primary ms-2">@{{ logs.length }}</span></h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Administration</li>
                        <li class="breadcrumb-item active">Journal d'audit</li>
                    </ol>
                </nav>
            </div>
            <div class="gap-2 d-flex align-items-center flex-wrap">
                @include('components.export-buttons')
                <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" @click="viewAuditLogs" title="Actualiser">
                    <i class="ti ti-refresh"></i>
                </a>
            </div>
        </div>

        <div class="card border-0 rounded-0">
            <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <div class="input-icon input-icon-start position-relative">
                    <span class="input-icon-addon text-dark"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" v-model="search" placeholder="Rechercher (référence, description)…">
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive custom-table table-nowrap">
                    <table class="table table-nowrap">
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
                        <tr v-for="log in filteredLogs" :key="log.id">
                            <td>@{{ formatDateTime(log.created_at) }}</td>
                            <td>
                                <span class="fw-medium">@{{ log.user_name }}</span>
                                <small class="d-block text-muted" v-if="log.user_email">@{{ log.user_email }}</small>
                            </td>
                            <td><span class="badge badge-soft-info">@{{ log.action }}</span></td>
                            <td><code>@{{ log.reference || '—' }}</code></td>
                            <td class="text-muted">@{{ log.description || '—' }}</td>
                        </tr>
                        <tr v-if="filteredLogs.length === 0">
                            <td colspan="5" class="text-center text-muted py-4">Aucune entrée dans le journal d'audit.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/scripts/user.js') }}"></script>
@endpush
