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
                    <label class="form-label">Devise</label>
                    <select class="form-select" v-model="form.devise" @change="onDeviseChange" :disabled="lectureSeule">
                        <option value="CDF">CDF — Franc congolais</option>
                        <option value="USD">USD — Dollar américain</option>
                    </select>
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
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th style="width:12%">Rubrique</th>
                        <th style="width:26%">Produit / libellé</th>
                        <th>Qté</th>
                        <th>P.U. (@{{ form.devise }})</th>
                        <th>HT</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(l, i) in form.lignes" :key="i">
                        <td>
                            <input class="form-control form-control-sm" v-model="l.rubrique" placeholder="Ex. Prestations" :disabled="lectureSeule">
                        </td>
                        <td>
                            <select class="form-select form-select-sm mb-1" v-model.number="l.produit_id" @change="appliquerProduit(i)" :disabled="lectureSeule">
                                <option :value="null">— Saisie libre —</option>
                                <option v-for="p in produits" :key="p.id" :value="p.id">@{{ p.code ? p.code + ' — ' : '' }}@{{ p.libelle }}</option>
                            </select>
                            <input class="form-control form-control-sm" v-model="l.libelle" placeholder="Libellé" :disabled="lectureSeule">
                        </td>
                        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" v-model.number="l.quantite" @input="recalc" :disabled="lectureSeule"></td>
                        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" v-model.number="l.prix_unitaire" @input="recalc" :disabled="lectureSeule"></td>
                        <td class="text-end">@{{ fmt(ligneHt(l)) }}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" @click="form.lignes.splice(i,1); recalc()" :disabled="lectureSeule"><i class="ti ti-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline-primary mb-3" @click="addLigne" :disabled="lectureSeule"><i class="ti ti-plus"></i> Ligne</button>
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
