@extends('layouts.app')

@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('parametres._nav', ['active' => 'societe', 'title' => 'Identité & Exercices', 'breadcrumb' => 'Société'])

    <div class="row g-4">
        <!-- Colonne Gauche : Identité & Logo -->
        <div class="col-lg-7">
            <!-- Card Logo -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="ti ti-photo me-2"></i>Logo de l'entité</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-4 flex-wrap">
                        <div class="logo-preview-container border-dashed border-2 rounded-3 p-2 bg-light d-flex align-items-center justify-content-center">
                            <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="img-fluid" style="max-height:80px;">
                            <div v-else class="text-center text-muted">
                                <i class="ti ti-building fs-32 d-block mb-1"></i>
                                <span class="fs-11">AUCUN LOGO</span>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label fw-bold small text-uppercase">Changer le logo</label>
                            <div class="input-group input-group-sm mb-2">
                                <input type="file" class="form-control" accept="image/*" @change="onLogoSelected">
                            </div>
                            <button type="button" class="btn btn-primary btn-sm px-4" :disabled="!logoFile || isLoading" @click="uploadLogo">
                                <i class="ti ti-upload me-1" v-if="!isLoading"></i>
                                <span class="spinner-border spinner-border-sm me-1" v-else></span>
                                @{{ isLoading ? 'Envoi…' : 'Mettre à jour le logo' }}
                            </button>
                            <p class="text-muted fs-11 mb-0 mt-2 italic">Format suggéré: PNG ou SVG sur fond transparent (max. 2 Mo).</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Informations -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="ti ti-info-circle me-2"></i>Fiche d'identification</h5>
                </div>
                <div class="card-body p-4">
                    <form @submit.prevent="saveSociete">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Code interne</label>
                                <input class="form-control border-2 font-monospace" v-model="formSociete.code" required placeholder="ex: COMPTA-01">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold small">Raison sociale <span class="text-danger">*</span></label>
                                <input class="form-control border-2" v-model="formSociete.raison_sociale" required placeholder="Nom officiel de l'entreprise">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Forme juridique</label>
                                <input class="form-control border-2" v-model="formSociete.forme_juridique" placeholder="ex: SARL, SA, Ets...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Sigle / Nom commercial</label>
                                <input class="form-control border-2" v-model="formSociete.sigle">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Devise de tenue de compte</label>
                                <select class="form-select border-2" v-model="formSociete.devise_principale" required>
                                    <option v-for="d in devises" :key="d.code_iso" :value="d.code_iso">@{{ d.code_iso }} — @{{ d.libelle }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Régime fiscal</label>
                                <input class="form-control border-2" v-model="formSociete.regime_fiscal" placeholder="ex: Réel, Simplifié, RNI, RSI…">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small">Siège social / Adresse</label>
                                <textarea class="form-control border-2" rows="2" v-model="formSociete.adresse" placeholder="Adresse complète..."></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Ville</label>
                                <input class="form-control border-2" v-model="formSociete.ville">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Pays</label>
                                <input class="form-control border-2" v-model="formSociete.pays">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Statut</label>
                                <select class="form-select border-2" v-model="formSociete.statut">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspendue">Suspendue</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Téléphone</label>
                                <input class="form-control border-2" v-model="formSociete.telephone">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Email contact</label>
                                <input type="email" class="form-control border-2" v-model="formSociete.email">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">N° RCCM</label>
                                <input class="form-control border-2" v-model="formSociete.rccm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">N° Impôt (NIF)</label>
                                <input class="form-control border-2" v-model="formSociete.num_contribuable" placeholder="Numéro d'impôt">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Identification nationale</label>
                                <input class="form-control border-2" v-model="formSociete.identification_nationale">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">N° CNPS</label>
                                <input class="form-control border-2" v-model="formSociete.num_cnps">
                            </div>

                            <div class="col-12 mt-3">
                                <hr class="my-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 fw-bold text-info"><i class="ti ti-building-bank me-2"></i>Coordonnées bancaires</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="ajouterBanque">
                                        <i class="ti ti-plus me-1"></i> Ajouter une banque
                                    </button>
                                </div>

                                <div v-if="!banques.length" class="text-muted fs-12 mb-3 bg-light p-3 rounded text-center border-dashed">
                                    Aucun compte bancaire configuré. Ajoutez-en un pour l'afficher sur vos documents.
                                </div>

                                <div v-for="(b, i) in banques" :key="i" class="row g-2 align-items-end mb-3 border rounded p-3 bg-light-soft position-relative">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Nom de la banque</label>
                                        <input class="form-control form-control-sm border-2" v-model="b.banque" placeholder="Ex. Rawbank, Equity BCDC…">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">N° de compte</label>
                                        <input class="form-control form-control-sm border-2" v-model="b.numero_compte">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold">Devise</label>
                                        <select class="form-select form-select-sm border-2" v-model="b.devise">
                                            <option value="CDF">CDF</option>
                                            <option value="USD">USD</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex gap-2">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" :id="'bdef_'+i" v-model="b.est_defaut" @change="definirBanqueDefaut(i)">
                                            <label class="form-check-label fs-11" :for="'bdef_'+i">Défaut</label>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger border-0" @click="banques.splice(i,1)" title="Supprimer">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-4 pt-2 border-top">
                                <button type="submit" class="btn btn-primary px-5 shadow-sm" :disabled="isLoading">
                                    <i class="ti ti-device-floppy me-2" v-if="!isLoading"></i>
                                    <span class="spinner-border spinner-border-sm me-2" v-else></span>
                                    Enregistrer la configuration de la société
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Colonne Droite : Exercices -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="ti ti-calendar-event me-2"></i>Exercices comptables</h5>
                    <button type="button" class="btn btn-primary btn-sm" @click="openExerciceForm()">
                        <i class="ti ti-plus"></i>
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Libellé</th>
                                    <th>Période</th>
                                    <th class="text-center">Statut</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="ex in exercices" :key="ex.id" :class="{'bg-soft-primary': ex.est_courant}">
                                    <td class="fw-bold">
                                        <div class="d-flex align-items-center">
                                            @{{ ex.libelle }}
                                            <span v-if="ex.est_courant" class="badge bg-primary ms-2 fs-10">COURANT</span>
                                        </div>
                                    </td>
                                    <td class="fs-12 text-muted">@{{ ex.date_debut }} <i class="ti ti-arrow-narrow-right"></i> @{{ ex.date_fin }}</td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill text-uppercase fs-10" :class="exerciceStatutClass(ex.statut)">@{{ ex.statut }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <button v-if="!ex.est_courant" type="button" class="btn btn-xs btn-outline-primary" @click="setCourant(ex)" title="Définir comme courant">
                                                <i class="ti ti-star"></i>
                                            </button>
                                            <button type="button" class="btn btn-xs btn-label-primary" @click="editExercice(ex)">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!exercices.length">
                                    <td colspan="4" class="text-center py-5 text-muted">Aucun exercice configuré</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Exercice -->
    <div class="modal fade" id="modal_exercice" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary py-3">
                    <h5 class="modal-title text-white fw-bold">Gestion de l'exercice</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="saveExercice">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Désignation de l'exercice</label>
                                <input class="form-control border-2" v-model="formExercice.libelle" required placeholder="ex: Exercice 2024">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Année</label>
                                <input type="number" class="form-control border-2" v-model.number="formExercice.annee" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Date de début</label>
                                <input type="date" class="form-control border-2" v-model="formExercice.date_debut" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Date de fin</label>
                                <input type="date" class="form-control border-2" v-model="formExercice.date_fin" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">État d'ouverture</label>
                                <select class="form-select border-2" v-model="formExercice.statut">
                                    <option value="ouvert">Ouvert</option>
                                    <option value="pre_cloture">Pré-clôture</option>
                                    <option value="cloture">Clôturé</option>
                                    <option value="archive">Archivé</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" v-model="formExercice.est_courant" id="ex_courant">
                                    <label class="form-check-label fw-medium" for="ex_courant">Définir comme courant</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-top-0">
                        <button type="button" class="btn btn-white border px-4" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary px-4">Valider l'exercice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection

@push('styles')
<style>
    .logo-preview-container { width: 180px; height: 100px; }
    .table-custom thead th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        font-weight: 700;
        padding: 12px 15px;
        border-bottom: 2px solid #dee2e6;
        color: #475569;
    }
    .table-custom tbody td { padding: 12px 15px; vertical-align: middle; font-size: 13.5px; border-bottom: 1px solid #f1f5f9; }
    .btn-xs { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
    .btn-label-primary { background: #e7e7ff; color: #696cff; border: none; }
    .btn-label-primary:hover { background: #696cff; color: #fff; }
    .bg-soft-primary { background-color: rgba(63, 122, 253, 0.05); }
    .bg-light-soft { background-color: #f8fafc; }
</style>
@endpush

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/parametres/societe.js') }}"></script>
@endpush
