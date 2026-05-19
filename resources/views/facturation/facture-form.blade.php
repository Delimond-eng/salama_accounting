@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    <div v-if="error && error.length" class="alert alert-danger"><div v-for="(e,i) in error" :key="i">@{{ e }}</div></div>
    <div v-if="message" class="alert alert-success">@{{ message }}</div>
    <div class="card border-0 rounded-0">
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Tiers</label>
                    <select class="form-select" v-model.number="form.tiers_id">
                        <option :value="null">— Sélectionner —</option>
                        <option v-for="t in tiers" :key="t.id" :value="t.id">@{{ t.code }} — @{{ t.nom }}</option>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Date</label><input type="date" class="form-control" v-model="form.date_facture"></div>
                <div class="col-md-2"><label class="form-label">Échéance</label><input type="date" class="form-control" v-model="form.date_echeance"></div>
                <div class="col-md-2"><label class="form-label">Devise</label><input class="form-control" v-model="form.devise" maxlength="3"></div>
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="tva" v-model="form.tva_active">
                        <label class="form-check-label" for="tva">TVA @{{ form.taux_tva }}%</label>
                    </div>
                </div>
            </div>
            <div class="mb-3"><label class="form-label">Objet</label><input class="form-control" v-model="form.objet"></div>
            <table class="table table-bordered">
                <thead class="table-light"><tr><th>Libellé</th><th>Qté</th><th>P.U.</th><th>HT</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="(l, i) in form.lignes" :key="i">
                        <td><input class="form-control form-control-sm" v-model="l.libelle"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm" v-model.number="l.quantite" @input="recalc"></td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm" v-model.number="l.prix_unitaire" @input="recalc"></td>
                        <td class="text-end">@{{ fmt(ligneHt(l)) }}</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" @click="form.lignes.splice(i,1); recalc()"><i class="ti ti-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline-primary mb-3" @click="addLigne"><i class="ti ti-plus"></i> Ligne</button>
            <div class="text-end">
                <p>HT : <strong>@{{ fmt(totaux.ht) }}</strong> — TVA : @{{ fmt(totaux.tva) }} — <span class="fs-18 text-primary">TTC : @{{ fmt(totaux.ttc) }} @{{ form.devise }}</span></p>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <a href="javascript:history.back()" class="btn btn-outline-light">Annuler</a>
                <button type="button" class="btn btn-primary" @click="save(false)" :disabled="isLoading">Enregistrer brouillon</button>
                <button type="button" class="btn btn-success" @click="save(true)" :disabled="isLoading">Enregistrer et valider</button>
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
