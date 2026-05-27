@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])

    <div v-if="error && error.length" class="alert alert-danger shadow-sm border-0 mb-4">
        <div v-for="(e,i) in error" :key="i"><i class="ti ti-alert-circle me-2"></i>@{{ e }}</div>
    </div>
    <div v-if="message" class="alert alert-success shadow-sm border-0 mb-4">@{{ message }}</div>
    <div v-if="lectureSeule" class="alert alert-warning shadow-sm border-0 mb-4">
        <i class="ti ti-info-circle me-2"></i>Cette facture n'est plus en brouillon : consultation seule.
    </div>

    <form @submit.prevent="save(false)">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary text-uppercase fs-14">
                    <i class="ti ti-file-invoice me-2"></i>@{{ form.id ? 'Édition de la Pièce' : 'Nouvelle Pièce de Facturation' }}
                </h5>
                <span v-if="form.numero" class="badge bg-label-primary font-monospace fs-14">@{{ form.numero }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Partenaire / Tiers <span class="text-danger">*</span></label>
                        <select class="form-select border-2" v-model.number="form.tiers_id" required :disabled="lectureSeule">
                            <option :value="null">— Sélectionner un tiers —</option>
                            <option v-for="t in tiers" :key="t.id" :value="t.id">@{{ t.code }} — @{{ t.nom }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-uppercase text-muted">Date Facture</label>
                        <input type="date" class="form-control border-2" v-model="form.date_facture" required :disabled="lectureSeule">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-uppercase text-muted">Date Échéance</label>
                        <input type="date" class="form-control border-2" v-model="form.date_echeance" required :disabled="lectureSeule">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-uppercase text-muted">Devise</label>
                        <select class="form-select border-2 font-monospace fw-bold" v-model="form.devise" @change="onDeviseChange" :disabled="lectureSeule">
                            <option v-for="code in devisesFacture" :key="code" :value="code">@{{ code }}</option>
                        </select>
                        <div class="form-text fs-10" v-if="form.devise !== 'CDF'">Taux requis à la validation.</div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="tva" v-model="form.tva_active" @change="recalc" :disabled="lectureSeule">
                            <label class="form-check-label fw-medium" for="tva">Appliquer TVA (@{{ form.taux_tva }}%)</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small text-uppercase text-muted">Objet / Libellé de la facture</label>
                        <input class="form-control border-2" v-model="form.objet" placeholder="ex: Prestations du mois de Mai 2024..." :disabled="lectureSeule">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small text-uppercase text-muted">Commentaires <span class="text-muted fw-normal">(affichés sur le PDF)</span></label>
                        <textarea class="form-control border-2" rows="3" v-model="form.notes" :disabled="lectureSeule" placeholder="Conditions de paiement, remarques, mentions légales…"></textarea>
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-custom-edit">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 50px">#</th>
                                <th>Description des articles / services / rubriques</th>
                                <th style="width: 100px" class="text-center">Quantité</th>
                                <th style="width: 150px" class="text-end">Prix Unitaire</th>
                                <th style="width: 150px" class="text-end">Total HT</th>
                                <th style="width: 50px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="(l, i) in form.lignes">
                                <tr v-if="l.est_rubrique" :key="'r-'+i" class="table-secondary">
                                    <td class="text-muted align-middle text-center">§</td>
                                    <td colspan="4">
                                        <input class="form-control form-control-sm border-0 bg-transparent fw-bold text-uppercase" v-model="l.rubrique"
                                            placeholder="Nom de la rubrique (ex. CCTV, Access Control…)" :disabled="lectureSeule">
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-sm text-danger p-0" @click="form.lignes.splice(i,1)" :disabled="lectureSeule" title="Supprimer la rubrique">
                                            <i class="ti ti-trash fs-18"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-else :key="'l-'+i">
                                    <td class="text-muted align-middle text-center small">@{{ numeroLigneArticle(i) }}</td>
                                    <td class="p-2">
                                        <select class="form-select form-select-sm border-0 bg-transparent mb-1" v-model.number="l.produit_id" @change="appliquerProduit(i)" :disabled="lectureSeule">
                                            <option :value="null">— Saisie libre —</option>
                                            <option v-for="p in produits" :key="p.id" :value="p.id">@{{ p.code ? p.code + ' — ' : '' }}@{{ p.libelle }}</option>
                                        </select>
                                        <input class="form-control form-control-sm border-0 bg-transparent" v-model="l.libelle" placeholder="Désignation..." :disabled="lectureSeule">
                                    </td>
                                    <td class="p-2"><input type="number" step="0.01" class="form-control form-control-sm border-0 text-center bg-transparent" v-model.number="l.quantite" @input="recalc" :disabled="lectureSeule"></td>
                                    <td class="p-2"><input type="number" step="0.01" class="form-control form-control-sm border-0 text-end bg-transparent fw-medium" v-model.number="l.prix_unitaire" @input="recalc" :disabled="lectureSeule"></td>
                                    <td class="p-2 text-end align-middle fw-bold">@{{ fmt(ligneHt(l)) }}</td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-sm text-danger p-0" @click="form.lignes.splice(i,1); recalc()" :disabled="lectureSeule">
                                            <i class="ti ti-trash fs-18"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-start mt-4">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3" @click="addRubrique" :disabled="lectureSeule">
                            <i class="ti ti-layout-rows me-1"></i>Rubrique
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm px-3" @click="addLigne" :disabled="lectureSeule">
                            <i class="ti ti-plus me-1"></i>Article
                        </button>
                    </div>

                    <div class="invoice-summary border rounded-3 p-3 bg-light" style="min-width: 350px;">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Hors Taxes :</span>
                            <span class="fw-bold">@{{ fmt(totaux.ht) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2" v-if="form.tva_active">
                            <span class="text-muted">TVA (@{{ form.taux_tva }}%) :</span>
                            <span class="fw-bold">@{{ fmt(totaux.tva) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="h5 fw-bold mb-0">TOTAL TTC :</span>
                            <span class="h5 fw-bold text-primary mb-0">@{{ fmt(totaux.ttc) }} @{{ form.devise }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light-soft border-top p-4 d-flex gap-2 justify-content-end">
                <a href="javascript:history.back()" class="btn btn-white border px-4">Annuler</a>
                <button type="submit" class="btn btn-outline-primary px-4" :disabled="isLoading || lectureSeule">
                    <i class="ti ti-device-floppy me-1"></i>Enregistrer brouillon
                </button>
                <button type="button" class="btn btn-success px-4" @click="save(true)" :disabled="isLoading || lectureSeule">
                    <i class="ti ti-check me-1"></i>Valider la facture
                </button>
            </div>
        </div>
    </form>
    </template>
</div>
@endsection

@push('styles')
<style>
    .table-custom-edit thead th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        font-weight: 700;
        padding: 10px 15px;
        border-bottom: 2px solid #dee2e6;
        color: #475569;
    }
    .table-custom-edit tbody td { padding: 0; }
    .table-custom-edit .form-control:focus { box-shadow: none; background-color: #fff !important; }
    .bg-light-soft { background-color: #f8fafc; }
    .bg-label-primary { background: #e7e7ff; color: #696cff; }
</style>
@endpush

@push('scripts')
<script>window.__FACTURE_ID__ = @json($facture_id); window.__TYPE_DOCUMENT__ = @json($type_document); window.__FACTURATION_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/facturation/facture-form.js') }}"></script>
@endpush
