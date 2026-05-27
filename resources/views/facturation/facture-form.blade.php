@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    <div v-if="error && error.length" class="alert alert-danger"><div v-for="(e,i) in error" :key="i">@{{ e }}</div></div>
    <div v-if="message" class="alert alert-success">@{{ message }}</div>
    <div v-if="lectureSeule" class="alert alert-warning">Cette facture n'est plus en brouillon : consultation seule.</div>
    <div class="card border-0 rounded-0">
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Tiers</label>
                    <select class="form-select" v-model.number="form.tiers_id" :disabled="lectureSeule">
                        <option :value="null">— Sélectionner —</option>
                        <option v-for="t in tiers" :key="t.id" :value="t.id">@{{ t.code }} — @{{ t.nom }}</option>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Date</label><input type="date" class="form-control" v-model="form.date_facture" :disabled="lectureSeule"></div>
                <div class="col-md-2"><label class="form-label">Échéance</label><input type="date" class="form-control" v-model="form.date_echeance" :disabled="lectureSeule"></div>
                <div class="col-md-2">
                    <label class="form-label">Devise facture</label>
                    <select class="form-select" v-model="form.devise" @change="onDeviseChange" :disabled="lectureSeule">
                        <option v-for="code in devisesFacture" :key="code" :value="code">@{{ code }}</option>
                    </select>
                    <div class="form-text fs-11" v-if="form.devise !== 'CDF'">Taux du jour requis à la validation (Paramètres &gt; Devises).</div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="tva" v-model="form.tva_active" :disabled="lectureSeule">
                        <label class="form-check-label" for="tva">TVA @{{ form.taux_tva }}%</label>
                    </div>
                </div>
            </div>
            <div class="mb-3"><label class="form-label">Objet</label><input class="form-control" v-model="form.objet" :disabled="lectureSeule" placeholder="Objet de la facture"></div>
            <div class="mb-3">
                <label class="form-label">Commentaires <span class="text-muted fw-normal">(affichés sur le PDF)</span></label>
                <textarea class="form-control" rows="3" v-model="form.notes" :disabled="lectureSeule" placeholder="Conditions de paiement, remarques, mentions légales…"></textarea>
            </div>

            <p class="text-muted fs-13 mb-2">
                Organisez la facture par <strong>rubriques</strong> (ex. CCTV, Access Control). Chaque rubrique occupe une ligne entière ; placez ensuite les articles en dessous.
            </p>
            <table class="table table-bordered mb-2">
                <thead class="table-light">
                    <tr>
                        <th style="width:5%">#</th>
                        <th>Désignation / rubrique</th>
                        <th style="width:10%">Qté</th>
                        <th style="width:14%">P.U. (@{{ form.devise }})</th>
                        <th style="width:12%">HT</th>
                        <th style="width:5%"></th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(l, i) in form.lignes">
                        <tr v-if="l.est_rubrique" :key="'r-'+i" class="table-secondary">
                            <td class="text-muted align-middle">§</td>
                            <td colspan="4">
                                <input class="form-control fw-semibold text-uppercase" v-model="l.rubrique"
                                    placeholder="Nom de la rubrique (ex. CCTV, Access Control…)" :disabled="lectureSeule">
                            </td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-sm btn-outline-danger" @click="form.lignes.splice(i,1)" :disabled="lectureSeule" title="Supprimer la rubrique">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-else :key="'l-'+i">
                            <td class="text-muted align-middle">@{{ numeroLigneArticle(i) }}</td>
                            <td>
                                <select class="form-select form-select-sm mb-1" v-model.number="l.produit_id" @change="appliquerProduit(i)" :disabled="lectureSeule">
                                    <option :value="null">— Saisie libre —</option>
                                    <option v-for="p in produits" :key="p.id" :value="p.id">@{{ p.code ? p.code + ' — ' : '' }}@{{ p.libelle }}</option>
                                </select>
                                <input class="form-control form-control-sm" v-model="l.libelle" placeholder="Libellé de l'article" :disabled="lectureSeule">
                            </td>
                            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" v-model.number="l.quantite" @input="recalc" :disabled="lectureSeule"></td>
                            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" v-model.number="l.prix_unitaire" @input="recalc" :disabled="lectureSeule"></td>
                            <td class="text-end align-middle">@{{ fmt(ligneHt(l)) }}</td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-sm btn-outline-danger" @click="form.lignes.splice(i,1); recalc()" :disabled="lectureSeule"><i class="ti ti-trash"></i></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="addRubrique" :disabled="lectureSeule">
                    <i class="ti ti-layout-rows"></i> Ajouter une rubrique
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" @click="addLigne" :disabled="lectureSeule">
                    <i class="ti ti-plus"></i> Ajouter un article
                </button>
            </div>
            <div class="text-end">
                <p>HT : <strong>@{{ fmt(totaux.ht) }}</strong> — TVA : @{{ fmt(totaux.tva) }} — <span class="fs-18 text-primary">TTC : @{{ fmt(totaux.ttc) }} @{{ form.devise }}</span></p>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="javascript:history.back()" class="btn btn-outline-light">Annuler</a>
                <button type="button" class="btn btn-primary" @click="save(false)" :disabled="isLoading || lectureSeule">Enregistrer brouillon</button>
                <button type="button" class="btn btn-success" @click="save(true)" :disabled="isLoading || lectureSeule">Enregistrer et valider</button>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__FACTURE_ID__ = @json($facture_id); window.__TYPE_DOCUMENT__ = @json($type_document); window.__FACTURATION_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/facturation/facture-form.js') }}"></script>
@endpush
