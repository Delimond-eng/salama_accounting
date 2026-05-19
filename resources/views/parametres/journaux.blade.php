@extends('layouts.app')

@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('parametres._nav', ['active' => 'journaux', 'title' => 'Journaux comptables', 'breadcrumb' => 'Journaux'])

    <div class="card border-0 rounded-0">
        <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <p class="mb-0 text-muted fs-13">Journaux SYSCOHADA de la societe active</p>
            <div class="d-flex gap-2 align-items-center">
                @include('components.export-buttons')
            <button type="button" class="btn btn-primary" @click="openForm()">
                <i class="ti ti-square-rounded-plus-filled me-1"></i>Nouveau journal
            </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-nowrap mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Libellé</th>
                            <th>Type</th>
                            <th>Contrepartie</th>
                            <th>Numérotation</th>
                            <th>Actif</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="7" class="text-center py-4">Chargement…</td></tr>
                        <tr v-else-if="!journaux.length"><td colspan="7" class="text-center py-4 text-muted">Aucun journal</td></tr>
                        <tr v-for="j in journaux" :key="j.id">
                            <td><span class="badge badge-soft-primary">@{{ j.code }}</span></td>
                            <td>@{{ j.libelle }}</td>
                            <td>@{{ j.type }}</td>
                            <td>@{{ j.compte_contrepartie || '—' }}</td>
                            <td>@{{ j.format_numerotation }}</td>
                            <td>
                                <span class="badge" :class="j.actif ? 'badge-soft-success' : 'badge-soft-secondary'">
                                    @{{ j.actif ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-light" @click="editJournal(j)"><i class="ti ti-edit"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_journal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@{{ form.id ? 'Modifier' : 'Nouveau' }} journal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="saveJournal">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Code</label>
                                <input type="text" class="form-control" v-model="form.code" required maxlength="10">
                            </div>
                            <div class="col-md-9">
                                <label class="form-label">Libellé</label>
                                <input type="text" class="form-control" v-model="form.libelle" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <select class="form-select" v-model="form.type" required>
                                    <option value="achats">Achats</option>
                                    <option value="ventes">Ventes</option>
                                    <option value="banque">Banque</option>
                                    <option value="caisse">Caisse</option>
                                    <option value="operations_diverses">Opérations diverses</option>
                                    <option value="salaires">Salaires</option>
                                    <option value="stocks">Stocks</option>
                                    <option value="effets">Effets</option>
                                    <option value="immobilisations">Immobilisations</option>
                                    <option value="ouverture">Ouverture</option>
                                    <option value="cloture">Clôture</option>
                                    <option value="simulation">Simulation</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Compte contrepartie</label>
                                @include('components.compte-select', ['compteKey' => 'journal_cp', 'inputClass' => 'form-control'])
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Préfixe pièce</label>
                                <input type="text" class="form-control" v-model="form.prefixe_piece">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Numérotation</label>
                                <select class="form-select" v-model="form.format_numerotation">
                                    <option value="annuel">Annuel</option>
                                    <option value="mensuel">Mensuel</option>
                                    <option value="continu">Continu</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Padding</label>
                                <input type="number" class="form-control" v-model.number="form.padding_numero" min="1" max="8">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ordre affichage</label>
                                <input type="number" class="form-control" v-model.number="form.ordre_affichage">
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" v-model="form.saisie_tiers_obligatoire" id="chk_st">
                                    <label class="form-check-label" for="chk_st">Tiers obligatoire</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" v-model="form.actif" id="chk_act">
                                    <label class="form-check-label" for="chk_act">Actif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" :disabled="isLoading">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/parametres/journaux.js') }}"></script>
@endpush
