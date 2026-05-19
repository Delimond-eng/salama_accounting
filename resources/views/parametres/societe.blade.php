@extends('layouts.app')

@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('parametres._nav', ['active' => 'societe', 'title' => 'Société & exercice', 'breadcrumb' => 'Société'])

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card border-0 rounded-0 mb-3">
                <div class="card-header"><h5 class="mb-0 fs-16">Logo de la société</h5></div>
                <div class="card-body d-flex align-items-center gap-4 flex-wrap">
                    <div class="border rounded p-2 bg-light" style="min-width:120px;min-height:80px;display:flex;align-items:center;justify-content:center">
                        <img v-if="logoPreview" :src="logoPreview" alt="Logo" style="max-height:72px;max-width:160px;object-fit:contain">
                        <span v-else class="text-muted fs-13">Aucun logo</span>
                    </div>
                    <div>
                        <input type="file" class="form-control form-control-sm mb-2" accept="image/*" @change="onLogoSelected">
                        <button type="button" class="btn btn-sm btn-primary" :disabled="!logoFile || isLoading" @click="uploadLogo">
                            @{{ isLoading ? 'Envoi…' : 'Enregistrer le logo' }}
                        </button>
                        <p class="text-muted fs-12 mb-0 mt-2">PNG, JPG, SVG — max. 2 Mo. Affiché dans le menu et l'en-tête.</p>
                    </div>
                </div>
            </div>
            <div class="card border-0 rounded-0">
                <div class="card-header"><h5 class="mb-0 fs-16">Informations société</h5></div>
                <div class="card-body">
                    <form @submit.prevent="saveSociete" class="row g-3">
                        <div class="col-md-4"><label class="form-label">Code</label><input class="form-control" v-model="formSociete.code" required></div>
                        <div class="col-md-8"><label class="form-label">Raison sociale</label><input class="form-control" v-model="formSociete.raison_sociale" required></div>
                        <div class="col-md-4"><label class="form-label">Forme juridique</label><input class="form-control" v-model="formSociete.forme_juridique"></div>
                        <div class="col-md-4"><label class="form-label">Sigle</label><input class="form-control" v-model="formSociete.sigle"></div>
                        <div class="col-md-4">
                            <label class="form-label">Devise principale</label>
                            <select class="form-select" v-model="formSociete.devise_principale" required>
                                <option v-for="d in devises" :key="d.code_iso" :value="d.code_iso">@{{ d.code_iso }}</option>
                            </select>
                        </div>
                        <div class="col-12"><label class="form-label">Adresse</label><textarea class="form-control" rows="2" v-model="formSociete.adresse"></textarea></div>
                        <div class="col-md-4"><label class="form-label">Ville</label><input class="form-control" v-model="formSociete.ville"></div>
                        <div class="col-md-4"><label class="form-label">Pays</label><input class="form-control" v-model="formSociete.pays"></div>
                        <div class="col-md-4">
                            <label class="form-label">Statut</label>
                            <select class="form-select" v-model="formSociete.statut">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspendue">Suspendue</option>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">Téléphone</label><input class="form-control" v-model="formSociete.telephone"></div>
                        <div class="col-md-4"><label class="form-label">Email</label><input type="email" class="form-control" v-model="formSociete.email"></div>
                        <div class="col-md-4"><label class="form-label">RCCM</label><input class="form-control" v-model="formSociete.rccm"></div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary" :disabled="isLoading">Enregistrer la société</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 rounded-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fs-16">Exercices</h5>
                    <button type="button" class="btn btn-sm btn-primary" @click="openExerciceForm()"><i class="ti ti-plus"></i></button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-nowrap mb-0">
                        <thead class="table-light"><tr><th>Libellé</th><th>Période</th><th>Statut</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="ex in exercices" :key="ex.id">
                                <td>@{{ ex.libelle }} <span v-if="ex.est_courant" class="badge badge-soft-success ms-1">Courant</span></td>
                                <td class="fs-13">@{{ ex.date_debut }} → @{{ ex.date_fin }}</td>
                                <td>@{{ ex.statut }}</td>
                                <td>
                                    <button v-if="!ex.est_courant" type="button" class="btn btn-sm btn-outline-primary" @click="setCourant(ex)">Définir courant</button>
                                    <button type="button" class="btn btn-sm btn-outline-light" @click="editExercice(ex)"><i class="ti ti-edit"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_exercice" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form @submit.prevent="saveExercice">
                    <div class="modal-header"><h5 class="modal-title">Exercice</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body row g-3">
                        <div class="col-12"><label class="form-label">Libellé</label><input class="form-control" v-model="formExercice.libelle" required></div>
                        <div class="col-md-4"><label class="form-label">Année</label><input type="number" class="form-control" v-model.number="formExercice.annee" required></div>
                        <div class="col-md-4"><label class="form-label">Début</label><input type="date" class="form-control" v-model="formExercice.date_debut" required></div>
                        <div class="col-md-4"><label class="form-label">Fin</label><input type="date" class="form-control" v-model="formExercice.date_fin" required></div>
                        <div class="col-md-6">
                            <label class="form-label">Statut</label>
                            <select class="form-select" v-model="formExercice.statut">
                                <option value="ouvert">Ouvert</option>
                                <option value="pre_cloture">Pré-clôture</option>
                                <option value="cloture">Clôturé</option>
                                <option value="archive">Archivé</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" v-model="formExercice.est_courant" id="ex_courant">
                                <label class="form-check-label" for="ex_courant">Exercice courant</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/parametres/societe.js') }}"></script>
@endpush

