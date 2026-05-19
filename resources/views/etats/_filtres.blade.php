
    <div class="card border-0 rounded-0 mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Date d'arrete</label>
                    <input type="date" class="form-control" v-model="filtres.date_arrete" @change="onDatesChange">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Exercice</label>
                    <select class="form-select" v-model.number="filtres.exercice_id" @change="onExerciceChange">
                        <option v-for="ex in exercices" :key="ex.id" :value="ex.id">@{{ ex.libelle }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Devise</label>
                    <select class="form-select" v-model="filtres.devise_affichage" @change="onFiltreChange">
                        <option v-for="d in options.devises" :key="d.code_iso" :value="d.code_iso">@{{ d.code_iso }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Conversion</label>
                    <select class="form-select" v-model="filtres.mode_conversion" @change="onFiltreChange">
                        <option value="origine">Taux d'origine</option>
                        <option value="actuel">Taux actuel</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="avec_n1" v-model="filtres.avec_n1" @change="loadData">
                        <label class="form-check-label" for="avec_n1">Comparer N-1</label>
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
            <div class="mt-2" v-if="exercice">
                <span class="badge badge-soft-info me-1">@{{ exercice.libelle }}</span>
                <span class="badge badge-soft-primary" v-if="exerciceN1 && filtres.avec_n1">N-1 : @{{ exerciceN1.libelle }}</span>
                <span class="text-muted fs-12 ms-2">Montants en @{{ filtres.devise_affichage }}</span>
            </div>
        </div>
    </div>

