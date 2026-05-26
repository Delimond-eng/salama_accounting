    <div class="card border-0 rounded-0 mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Du</label>
                    <input type="date" class="form-control" v-model="filtres.date_debut" @change="onDatesChange">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Au</label>
                    <input type="date" class="form-control" v-model="filtres.date_fin" @change="onDatesChange">
                </div>
                <div class="col-md-2" v-if="page === 'journal'">
                    <label class="form-label">Journal</label>
                    <select class="form-select" v-model.number="journalId" @change="onFiltreChange">
                        <option :value="null">Tous</option>
                        <option v-for="j in journaux" :key="j.id" :value="j.id">@{{ j.code }} &mdash; @{{ j.libelle }}</option>
                    </select>
                </div>
                <div class="col-md-2" v-if="page === 'balance'">
                    <label class="form-label">Classe</label>
                    <select class="form-select" v-model="classe" @change="loadData">
                        <option value="">Toutes</option>
                        <option v-for="n in 9" :key="n" :value="n">Classe @{{ n }}</option>
                    </select>
                </div>
                <div class="col-md-2" v-if="page === 'auxiliaire'">
                    <label class="form-label">Type tiers</label>
                    <select class="form-select" v-model="typeTiers" @change="loadData">
                        <option value="">Tous</option>
                        <option value="client">Clients</option>
                        <option value="fournisseur">Fournisseurs</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Devise</label>
                    <select class="form-select" v-model="filtres.devise_affichage" @change="onFiltreChange">
                        <option value="CDF">CDF — Franc congolais</option>
                        <option value="USD">USD — Dollar</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Présentation</label>
                    <select class="form-select" v-model="filtres.scope_devise" @change="onFiltreChange">
                        <option value="natif">Native (@{{ filtres.devise_affichage }})</option>
                        <option value="consolide">Consolidée</option>
                    </select>
                </div>
                <div class="col-md-2" v-if="filtres.scope_devise==='consolide'">
                    <label class="form-label">Taux</label>
                    <select class="form-select" v-model="filtres.mode_conversion" @change="onFiltreChange">
                        <option value="origine">Taux d'origine</option>
                        <option value="actuel">Taux actuel</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Taux du jour</label>
                    <div class="input-group">
                        <span class="input-group-text">1 USD =</span>
                        <input type="number" step="0.01" class="form-control" v-model.number="tauxUsd" @change="saveTauxUsd">
                        <span class="input-group-text">CDF</span>
                    </div>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-primary" @click="loadData" :disabled="isLoading" title="Actualiser">
                        <i class="ti ti-refresh"></i>
                    </button>
                </div>
                <div class="col-auto">
                    @include('components.export-buttons')
                </div>
            </div>
            <div class="row mt-2" v-if="exercice">
                <div class="col-12">
                    <span class="badge badge-soft-info me-1">@{{ exercice.libelle }}</span>
                    <span class="badge badge-soft-primary">@{{ filtres.scope_devise === 'natif' ? 'Écritures ' + filtres.devise_affichage : 'Consolidé ' + filtres.devise_affichage }}</span>
                </div>
            </div>
        </div>
    </div>


