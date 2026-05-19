@extends('layouts.app')

@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('parametres._nav', ['active' => 'devises', 'title' => 'Devises & taux', 'breadcrumb' => 'Devises'])

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 rounded-0 h-100">
                <div class="card-header"><h5 class="mb-0 fs-16">Référentiel devises</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-nowrap mb-0">
                            <thead class="table-light"><tr><th>Code</th><th>Libellé</th><th>Symbole</th></tr></thead>
                            <tbody>
                                <tr v-for="d in devises" :key="d.code_iso">
                                    <td><span class="badge badge-soft-primary">@{{ d.code_iso }}</span></td>
                                    <td>@{{ d.libelle }}</td>
                                    <td>@{{ d.symbole || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 rounded-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fs-16">Taux de change</h5>
                    <button type="button" class="btn btn-primary btn-sm" @click="openTauxForm()">
                        <i class="ti ti-plus me-1"></i>Nouveau taux
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-nowrap mb-0">
                        <thead class="table-light"><tr><th>Date</th><th>Devise</th><th>Taux</th><th>Achat</th><th>Vente</th></tr></thead>
                        <tbody>
                            <tr v-if="!taux.length"><td colspan="5" class="text-center py-4 text-muted">Aucun taux</td></tr>
                            <tr v-for="t in taux" :key="t.id">
                                <td>@{{ t.date_taux }}</td>
                                <td>@{{ t.devise_code }}</td>
                                <td>@{{ t.taux }}</td>
                                <td>@{{ t.taux_achat || '—' }}</td>
                                <td>@{{ t.taux_vente || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_taux" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form @submit.prevent="saveTaux">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau taux</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Devise</label>
                            <select class="form-select" v-model="formTaux.devise_code" required>
                                <option v-for="d in devises" :key="d.code_iso" :value="d.code_iso">@{{ d.code_iso }} — @{{ d.libelle }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" v-model="formTaux.date_taux" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Taux</label>
                            <input type="number" step="0.000001" class="form-control" v-model.number="formTaux.taux" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Taux achat</label>
                            <input type="number" step="0.000001" class="form-control" v-model.number="formTaux.taux_achat">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Taux vente</label>
                            <input type="number" step="0.000001" class="form-control" v-model.number="formTaux.taux_vente">
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
<script type="module" src="{{ asset('assets/js/scripts/parametres/devises.js') }}"></script>
@endpush