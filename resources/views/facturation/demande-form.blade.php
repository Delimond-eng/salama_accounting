@extends('layouts.app')
@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('facturation._nav', ['active' => 'demandes', 'title' => $title, 'breadcrumb' => $title])
    <div v-if="!demandeId" class="card border-0 rounded-0 mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Montant</label><input type="number" class="form-control" v-model.number="form.montant"></div>
                <div class="col-md-2"><label class="form-label">Devise</label><input class="form-control" v-model="form.devise" maxlength="3"></div>
                <div class="col-12"><label class="form-label">Motif</label><textarea class="form-control" rows="3" v-model="form.motif"></textarea></div>
                <div class="col-12"><button type="button" class="btn btn-primary" @click="creer">Soumettre la demande</button></div>
            </div>
        </div>
    </div>
    <div v-else-if="demande" class="row g-3">
        <div class="col-lg-8">
            <div class="card border-0 rounded-0">
                <div class="card-header"><h5 class="mb-0">@{{ demande.numero }} — @{{ demande.statut }}</h5></div>
                <div class="card-body">
                    <p><strong>Montant :</strong> @{{ fmt(demande.montant) }} @{{ demande.devise }}</p>
                    <p><strong>Motif :</strong> @{{ demande.motif }}</p>
                    <p v-if="demande.etape_courante"><strong>Étape :</strong> @{{ demande.etape_courante.libelle }}</p>
                    <div v-if="demande.etape_courante?.imputation_comptable && demande.statut==='en_validation'" class="row g-2 mb-3">
                        <div class="col-md-6"><label>Compte débit</label><input class="form-control" v-model="traitement.compte_debit" placeholder="601100"></div>
                        <div class="col-md-6"><label>Compte crédit</label><input class="form-control" v-model="traitement.compte_credit" placeholder="471000"></div>
                    </div>
                    <div v-if="peutTraiter" class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-success" @click="traiter('approuve')">Approuver</button>
                        <button type="button" class="btn btn-danger" @click="traiter('rejete')">Rejeter</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 rounded-0">
                <div class="card-header">Historique</div>
                <ul class="list-group list-group-flush">
                    <li v-for="h in demande.historiques" :key="h.id" class="list-group-item small">
                        <strong>@{{ h.action }}</strong> — @{{ h.description }}<br>
                        <span class="text-muted">@{{ h.user?.name }} · @{{ h.created_at }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    </template>
</div>
@endsection
@push('scripts')
<script>window.__DEMANDE_ID__ = @json($demande_id);</script>
<script type="module" src="{{ asset('assets/js/scripts/facturation/demande-form.js') }}"></script>
@endpush
