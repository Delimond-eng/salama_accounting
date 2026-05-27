@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    <div class="d-flex align-items-center justify-content-between gap-2 mb-4 flex-wrap">
        <div>
            <h4 class="mb-1">Gestion des tâches</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Tâches</li>
                </ol>
            </nav>
            <p class="text-muted fs-13 mb-0" v-if="meta">
                <span v-if="meta.utilisateur.est_super_admin">Vue super administrateur — toutes les tâches</span>
                <span v-else>Vos tâches assignées et celles que vous avez créées</span>
            </p>
        </div>
        <button type="button" class="btn btn-primary" @click="openCreate">
            <i class="ti ti-plus me-1"></i>Nouvelle tâche
        </button>
    </div>

    <div v-if="error && error.length" class="alert alert-danger"><div v-for="(e,i) in error" :key="i">@{{ e }}</div></div>
    <div v-if="message" class="alert alert-success">@{{ message }}</div>

    <div class="row g-3">
        <div :class="detail ? 'col-lg-5' : 'col-12'">
            <div class="card border-0 rounded-0">
                <div class="card-body p-0">
                    <table class="table table-nowrap mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tâche</th>
                                <th>Assignés</th>
                                <th style="width:140px">Progression</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading"><td colspan="4" class="text-center py-4">Chargement…</td></tr>
                            <tr v-else-if="!liste.length"><td colspan="4" class="text-center py-4 text-muted">Aucune tâche</td></tr>
                            <tr v-for="t in liste" :key="t.id" @click="openDetail(t.id)" style="cursor:pointer" :class="detail && detail.id === t.id ? 'table-primary' : ''">
                                <td>
                                    <div class="fw-medium">@{{ t.titre }}</div>
                                    <div class="fs-12 text-muted">Par @{{ t.createur?.name }} — @{{ fmtDate(t.created_at) }}</div>
                                </td>
                                <td class="fs-13">@{{ (t.assignes || []).join(', ') || '—' }}</td>
                                <td>
                                    <div class="progress mb-1" style="height:8px">
                                        <div class="progress-bar" :class="progressClass(t)" :style="{width: (t.progression?.pourcent || 0) + '%'}"></div>
                                    </div>
                                    <span class="fs-12">@{{ t.progression?.faites || 0 }}/@{{ t.progression?.total || 0 }} (@{{ t.progression?.pourcent || 0 }}%)</span>
                                </td>
                                <td><span class="badge" :class="statutBadge(t.statut)">@{{ statutLabel(t.statut) }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7" v-if="detail">
            <div class="card border-0 rounded-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">@{{ detail.titre }}</h5>
                    <button type="button" class="btn-close" @click="detail=null"></button>
                </div>
                <div class="card-body">
                    <p v-if="detail.description" class="text-muted">@{{ detail.description }}</p>
                    <p class="fs-13 mb-3">
                        Créée par <strong>@{{ detail.createur?.name }}</strong>
                        <span v-if="detail.date_echeance"> — Échéance @{{ fmtDate(detail.date_echeance) }}</span>
                    </p>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-medium">Progression globale</span>
                            <span>@{{ detail.progression?.pourcent || 0 }}%</span>
                        </div>
                        <div class="progress" style="height:10px">
                            <div class="progress-bar bg-primary" :style="{width: (detail.progression?.pourcent || 0) + '%'}"></div>
                        </div>
                    </div>

                    <h6 class="text-uppercase fs-12 text-muted">Étapes par collaborateur</h6>
                    <div v-for="g in etapesParUser" :key="g.user_id" class="mb-3 border rounded p-2">
                        <div class="fw-medium mb-2"><i class="ti ti-user me-1"></i>@{{ g.nom }}</div>
                        <div v-for="e in g.etapes" :key="e.id" class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" :id="'et-'+e.id" :checked="e.est_terminee"
                                :disabled="!peutCocherEtape(e)" @change="toggleEtape(e.id)">
                            <label class="form-check-label" :for="'et-'+e.id" :class="e.est_terminee ? 'text-decoration-line-through text-muted' : ''">
                                @{{ e.libelle }}
                                <span v-if="e.est_terminee && e.terminee_le" class="fs-11 text-success">(@{{ fmtDateTime(e.terminee_le) }})</span>
                            </label>
                        </div>
                    </div>

                    <div v-if="detail.est_assigne || detail.peut_modifier" class="border-top pt-3 mt-3">
                        <h6 class="text-uppercase fs-12 text-muted">Rapport d'avancement</h6>
                        <textarea class="form-control mb-2" rows="3" v-model="rapportTexte" placeholder="Décrivez l'avancement…"></textarea>
                        <div class="d-flex gap-2 flex-wrap mb-2">
                            <button type="button" class="btn btn-sm btn-primary" @click="envoyerRapport" :disabled="isLoading">Publier le rapport</button>
                            <label class="btn btn-sm btn-outline-secondary mb-0">
                                <i class="ti ti-paperclip"></i> Joindre un fichier
                                <input type="file" class="d-none" @change="joindreFichier">
                            </label>
                        </div>
                    </div>

                    <div v-if="detail.rapports && detail.rapports.length" class="mt-3">
                        <h6 class="text-uppercase fs-12 text-muted">Rapports</h6>
                        <div v-for="r in detail.rapports" :key="r.id" class="bg-light rounded p-2 mb-2">
                            <div class="fs-12 text-muted mb-1">@{{ r.auteur?.name }} — @{{ fmtDateTime(r.created_at) }}</div>
                            <div style="white-space:pre-wrap">@{{ r.contenu }}</div>
                        </div>
                    </div>

                    <div v-if="detail.fichiers && detail.fichiers.length" class="mt-2">
                        <h6 class="text-uppercase fs-12 text-muted">Fichiers joints</h6>
                        <ul class="list-unstyled mb-0">
                            <li v-for="f in detail.fichiers" :key="f.id" class="mb-1">
                                <a :href="'/accounting/taches/fichiers/'+f.id+'/download'" target="_blank">
                                    <i class="ti ti-download me-1"></i>@{{ f.nom_fichier }}
                                </a>
                                <span class="text-muted fs-12"> — @{{ f.auteur?.name }}</span>
                            </li>
                        </ul>
                    </div>

                    <div v-if="detail.peut_modifier" class="mt-3 text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" @click="editFromDetail">
                            <i class="ti ti-edit"></i> Modifier la tâche
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_tache" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form @submit.prevent="saveTache">
                    <div class="modal-header">
                        <h5 class="modal-title">@{{ form.id ? 'Modifier' : 'Nouvelle' }} tâche</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Titre</label>
                            <input class="form-control" v-model="form.titre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="2" v-model="form.description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date d'échéance</label>
                            <input type="date" class="form-control" v-model="form.date_echeance">
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Étapes (plusieurs utilisateurs possibles)</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="addEtapeForm">+ Étape</button>
                        </div>
                        <div v-for="(e, i) in form.etapes" :key="i" class="row g-2 mb-2 align-items-end">
                            <div class="col-md-4">
                                <select class="form-select form-select-sm" v-model.number="e.user_id" required>
                                    <option :value="null">Utilisateur</option>
                                    <option v-for="u in users" :key="u.id" :value="u.id">@{{ u.name }}</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <input class="form-control form-control-sm" v-model="e.libelle" placeholder="Libellé de l'étape" required>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-sm btn-outline-danger" @click="form.etapes.splice(i,1)" v-if="form.etapes.length>1"><i class="ti ti-trash"></i></button>
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
<script type="module" src="{{ asset('assets/js/scripts/taches/taches.js') }}"></script>
@endpush
