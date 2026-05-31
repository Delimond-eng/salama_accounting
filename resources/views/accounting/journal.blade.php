@extends('layouts.app')

@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
            <div>
                <h4 class="mb-1">@{{ pageTitle }} <span class="badge badge-soft-primary ms-2">@{{ (lignes || []).length }}</span></h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Comptabilité</li>
                        <li class="breadcrumb-item active" aria-current="page">Journal</li>
                    </ol>
                </nav>
            </div>
            <div class="gap-2 d-flex align-items-center flex-wrap">
                @include('components.export-buttons')
                <button type="button" class="btn btn-outline-primary btn-sm px-3" @click="openImportModal()">
                    <i class="ti ti-file-import me-1"></i>Importer
                </button>
                <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow"
                   @click="loadData" :disabled="isLoading" title="Actualiser">
                    <i class="ti ti-refresh" :class="{'ti-spin': isLoading}"></i>
                </a>
                <a href="javascript:void(0);" class="btn btn-icon btn-outline-light shadow" id="collapse-header">
                    <i class="ti ti-transition-top"></i>
                </a>
            </div>
        </div>
        <!-- /Page Header -->

        <!-- card start -->
        <div class="card border-0 rounded-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between gap-2 flex-wrap py-3">
                <div class="search-box" style="min-width: 300px;">
                    <div class="input-group input-group-sm border rounded-2 px-2 bg-light">
                        <span class="input-group-text bg-transparent border-0 p-0 me-2"><i class="ti ti-search text-muted"></i></span>
                        <input type="text" class="form-control bg-transparent border-0 ps-0" v-model="search" placeholder="Rechercher une écriture (pièce, compte, libellé)...">
                    </div>
                </div>
                <a href="{{ route('accounting.saisie.nouvelle') }}" class="btn btn-primary btn-sm px-3">
                    <i class="ti ti-square-rounded-plus-filled me-1"></i>Nouvelle Écriture
                </a>
            </div>
            <div class="card-body">

                <!-- table header filters -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="dropdown-toggle btn btn-outline-light shadow-sm btn-sm"
                                data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Trier par</a>
                            <div class="dropdown-menu">
                                <ul>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Plus récent</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item">Plus ancien</a></li>
                                </ul>
                            </div>
                        </div>

                        <div class="reportrange-picker reportrange d-flex align-items-center shadow-sm btn btn-sm btn-outline-light"
                             @click="onDatesChange" style="cursor: pointer;">
                            <i class="ti ti-calendar-due text-dark fs-14 me-1"></i>
                            <span class="reportrange-picker-field">@{{ fmtDate(filtres.date_debut) }} - @{{ fmtDate(filtres.date_fin) }}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="btn btn-outline-light shadow-sm btn-sm px-2"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                                    class="ti ti-filter me-2"></i>Filtrer<i
                                    class="ti ti-chevron-down ms-2"></i></a>
                            <div class="filter-dropdown-menu dropdown-menu dropdown-menu-lg p-0">
                                <div class="filter-header d-flex align-items-center justify-content-between border-bottom p-3">
                                    <h4 class="mb-0 fs-16"><i class="ti ti-filter me-1"></i>Filtres</h4>
                                </div>
                                <div class="p-3">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Journal spécifique</label>
                                        <select class="form-select form-select-sm border-2" v-model="journalId">
                                            <option value="">Tous les journaux</option>
                                            <option v-for="j in journaux" :key="j.id" :value="j.id">@{{ j.code }} - @{{ j.libelle }}</option>
                                        </select>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-outline-light btn-sm w-100" @click="journalId = ''">Réinitialiser</button>
                                        <button type="button" class="btn btn-primary btn-sm w-100" @click="onFiltreChange">Appliquer</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /table header filters -->

                <!-- Journal List -->
                <div class="table-responsive custom-table">
                    <table class="table table-hover mb-0" id="journal-list">
                        <thead class="table-light">
                            <tr>
                                <th class="no-sort" style="width: 40px">
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                    </div>
                                </th>
                                <th style="width: 100px">Date</th>
                                <th style="width: 120px">Pièce</th>
                                <th style="width: 80px">Journal</th>
                                <th style="width: 110px">Compte</th>
                                <th>Libellé</th>
                                <th class="text-end" style="width: 120px">Débit</th>
                                <th class="text-end" style="width: 120px">Crédit</th>
                                <th class="no-sort text-end" style="width: 80px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading && !lignes.length">
                                <td colspan="9" class="text-center py-5">
                                    <div class="spinner-border spinner-border-sm text-primary me-2"></div> Chargement des écritures...
                                </td>
                            </tr>
                            <tr v-for="l in filteredLignes" :key="l.id">
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox" :value="l.id">
                                    </div>
                                </td>
                                <td class="small text-nowrap">@{{ fmtDate(l.date_ecriture) }}</td>
                                <td><code class="small text-primary fw-bold">@{{ l.num_piece }}</code></td>
                                <td>
                                    <span class="badge" :class="journalBadgeClass(null, l.journal_code)">@{{ l.journal_code }}</span>
                                </td>
                                <td><span class="fw-medium">@{{ l.num_compte }}</span></td>
                                <td class="text-wrap">
                                    <div class="fw-bold fs-13">@{{ l.libelle_ligne || l.libelle_ecriture }}</div>
                                    <div class="small text-muted" v-if="l.nom_tiers">@{{ l.nom_tiers }}</div>
                                </td>
                                <td class="text-end fw-bold">@{{ l.debit > 0 ? fmt(l.debit) : '—' }}</td>
                                <td class="text-end fw-bold">@{{ l.credit > 0 ? fmt(l.credit) : '—' }}</td>
                                <td class="text-end">
                                    <div class="edit-delete-action">
                                        <a class="me-2 p-1" :href="'/accounting/saisie/achats/ecriture/' + l.ecriture_id" title="Modifier">
                                            <i class="ti ti-edit text-info"></i>
                                        </a>
                                        <a class="p-1" href="javascript:void(0);" @click="deleteLigne(l)" title="Supprimer">
                                            <i class="ti ti-trash text-danger"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="filteredLignes.length > 0" class="table-light">
                            <tr class="fw-bold">
                                <td colspan="6" class="text-end uppercase fs-11">Totaux de la période</td>
                                <td class="text-end text-primary">@{{ fmt(totaux.debit) }}</td>
                                <td class="text-end text-primary">@{{ fmt(totaux.credit) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div v-if="!isLoading && filteredLignes.length === 0" class="text-center text-muted py-5">
                        <i class="ti ti-receipt-off d-block fs-1 mb-2 opacity-25"></i>
                        Aucune ligne d'écriture trouvée pour cette sélection.
                    </div>
                </div>
            </div>
        </div>
        <!-- card end -->

        <!-- Modal d'importation -->
        <div class="modal fade" id="modal_import" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-dark py-3">
                        <h5 class="modal-title text-white fw-bold"><i class="ti ti-file-import me-2"></i>Importer des écritures</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 bg-light-info text-info small mb-4">
                            <i class="ti ti-info-circle me-1"></i>
                            Importez vos écritures via un fichier Excel (.xlsx, .xls).
                        </div>

                        <div class="upload-zone border-2 border-dashed rounded-3 p-5 text-center position-relative"
                             :class="{'border-primary bg-light-primary': isDragging}"
                             @dragover.prevent="isDragging = true"
                             @dragleave.prevent="isDragging = false"
                             @drop.prevent="handleDrop">
                            <input type="file" ref="fileInput" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer"
                                   accept=".xlsx, .xls" @change="handleFileSelect">

                            <div v-if="!importFile">
                                <i class="ti ti-cloud-upload fs-1 text-muted mb-3"></i>
                                <h6 class="fw-bold mb-1">Cliquez ou glissez-déposez le fichier Excel</h6>
                                <p class="text-muted small mb-0">Format accepté : .xlsx, .xls</p>
                            </div>
                            <div v-else class="text-primary">
                                <i class="ti ti-file-spreadsheet fs-1 mb-3"></i>
                                <h6 class="fw-bold mb-1">@{{ importFile.name }}</h6>
                                <p class="small mb-0 text-success">Fichier prêt pour l'importation</p>
                            </div>
                        </div>

                        <div v-if="isImporting" class="mt-4">
                            <div class="progress progress-sm mb-2">
                                <div class="progress-bar progress-bar-animated progress-bar-striped" role="progressbar" style="width: 100%"></div>
                            </div>
                            <p class="text-center small text-muted mb-0">Importation en cours...</p>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top-0 p-3">
                        <button type="button" class="btn btn-white px-4 border" data-bs-dismiss="modal" :disabled="isImporting">Annuler</button>
                        <button type="button" class="btn btn-primary px-4" @click="processImport" :disabled="!importFile || isImporting">
                            Lancer l'importation
                        </button>
                    </div>
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
    .badge-soft-primary { background-color: #e7e7ff; color: #696cff; }
    .fs-11 { font-size: 11px; }
    .fs-13 { font-size: 13px; }
    .bg-soft-indigo { background-color: #e7e7ff; color: #6610f2; }
    .uppercase { text-transform: uppercase; }
    .edit-delete-action a { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 4px; background: #f8f9fa; transition: all 0.2s; }
    .edit-delete-action a:hover { background: #eee; }
    .dataTables_filter { display: none; }
    .upload-zone { transition: all 0.3s; background: #fdfdfd; }
    .bg-light-primary { background-color: rgba(63, 122, 253, 0.05) !important; }
    .bg-light-info { background-color: #e7f7ff !important; }
</style>
@endpush

@push('scripts')
    <script>
        window.__LIVRES_PAGE__ = "journal";
    </script>
    <script type="module" src="{{ asset('assets/js/scripts/livres/journal.js') }}"></script>
@endpush
