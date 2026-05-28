<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="row g-3 align-items-center">
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Date d'arrêté</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="ti ti-calendar fs-14"></i></span>
                    <input type="date" class="form-control border-start-0 ps-0" v-model="filtres.date_arrete" @change="onDatesChange">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Exercice</label>
                <select class="form-select form-select-sm" v-model.number="filtres.exercice_id" @change="onExerciceChange">
                    <option v-for="ex in exercices" :key="ex.id" :value="ex.id">@{{ ex.libelle }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Devise</label>
                <select class="form-select form-select-sm" v-model="filtres.devise_affichage" @change="onFiltreChange">
                    <option v-for="d in options.devises" :key="d.code_iso" :value="d.code_iso">@{{ d.code_iso }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Présentation</label>
                <select class="form-select form-select-sm" v-model="filtres.scope_devise" @change="onFiltreChange">
                    <option value="natif">Native</option>
                    <option value="consolide">Consolidée</option>
                </select>
            </div>
            <div class="col-md-2" v-if="filtres.scope_devise === 'consolide'">
                <label class="form-label text-muted fs-12 mb-1">Conversion</label>
                <select class="form-select form-select-sm" v-model="filtres.mode_conversion" @change="onFiltreChange">
                    <option value="origine">Taux d'origine</option>
                    <option value="actuel">Taux actuel</option>
                </select>
            </div>
            <div class="col-md-2" v-if="filtres.scope_devise === 'consolide' && (filtres.mode_conversion === 'actuel' || filtres.devise_affichage !== 'CDF')">
                <label class="form-label text-muted fs-12 mb-1">Taux (1 USD =)</label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" class="form-control" v-model.number="filtres.taux" @change="onFiltreChange">
                    <span class="input-group-text bg-light fs-11">CDF</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="avec_n1" v-model="filtres.avec_n1" @change="loadData">
                    <label class="form-check-label fs-12" for="avec_n1">Comparer N-1</label>
                </div>
            </div>
        </div>
        <div class="mt-3 pt-2 border-top d-flex align-items-center gap-2" v-if="exercice">
            <span class="badge bg-soft-info text-info fs-11">@{{ exercice.libelle }}</span>
            <span class="badge bg-soft-primary text-primary fs-11" v-if="exerciceN1 && filtres.avec_n1">N-1 : @{{ exerciceN1.libelle }}</span>
            <span class="text-muted fs-11 ms-auto">
                <i class="ti ti-info-circle me-1"></i>
                @{{ filtres.scope_devise === 'natif' ? 'Écritures en ' + filtres.devise_affichage : 'Montants consolidés en ' + filtres.devise_affichage }}
            </span>
        </div>
    </div>
</div>
