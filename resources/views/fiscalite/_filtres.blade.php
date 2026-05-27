
    <div class="card border-0 rounded-0 mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3" v-if="exercices && exercices.length">
                    <label class="form-label">Exercice</label>
                    <select class="form-select" v-model.number="filtres.exercice_id" @change="onExerciceChange">
                        <option v-for="ex in exercices" :key="ex.id" :value="ex.id">@{{ ex.libelle }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Du</label>
                    <input type="date" class="form-control" v-model="filtres.date_debut" @change="loadData">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Au</label>
                    <input type="date" class="form-control" v-model="filtres.date_fin" @change="loadData">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Devise</label>
                    <select class="form-select" v-model="filtres.devise_affichage" @change="loadData">
                        <option v-for="d in options.devises" :key="d.code_iso" :value="d.code_iso">@{{ d.code_iso }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Présentation</label>
                    <select class="form-select" v-model="filtres.scope_devise" @change="onFiltreChange">
                        <option value="natif">Native</option>
                        <option value="consolide">Consolidée</option>
                    </select>
                </div>
                <div class="col-md-2" v-if="filtres.scope_devise === 'consolide'">
                    <label class="form-label">Taux</label>
                    <select class="form-select" v-model="filtres.mode_conversion" @change="onFiltreChange">
                        <option value="origine">Taux d'origine</option>
                        <option value="actuel">Taux actuel</option>
                    </select>
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
            <div class="mt-2" v-if="exercice">
                <span class="badge badge-soft-info">@{{ exercice.libelle }}</span>
                <span class="badge badge-soft-primary ms-1">@{{ filtres.scope_devise === 'natif' ? 'Natif ' + filtres.devise_affichage : 'Consolidé ' + filtres.devise_affichage }}</span>
            </div>
        </div>
    </div>


