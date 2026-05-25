<div class="card border-0 shadow-sm mb-4">
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
                <label class="form-label text-muted fs-12 mb-1">Conversion</label>
                <select class="form-select form-select-sm" v-model="filtres.mode_conversion" @change="onFiltreChange">
                    <option value="origine">Taux d'origine</option>
                    <option value="actuel">Taux actuel</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="form-check form-switch pt-4">
                    <input class="form-check-input" type="checkbox" id="avec_n1" v-model="filtres.avec_n1" @change="loadData">
                    <label class="form-check-label fs-13 text-dark" for="avec_n1">Comparer avec N-1</label>
                </div>
            </div>
        </div>

        <div class="mt-3 pt-2 border-top d-flex align-items-center gap-2" v-if="exercice">
            <div class="d-flex align-items-center gap-1">
                <span class="badge bg-soft-info text-info">@{{ exercice.libelle }}</span>
                <i class="ti ti-arrow-narrow-right text-muted mx-1" v-if="exerciceN1 && filtres.avec_n1"></i>
                <span class="badge bg-soft-primary text-primary" v-if="exerciceN1 && filtres.avec_n1">N-1 : @{{ exerciceN1.libelle }}</span>
            </div>
            <div class="ms-auto">
                <span class="text-info fs-11"><i class="ti ti-info-circle me-1"></i>Montants exprimés en <strong>@{{ filtres.devise_affichage }}</strong></span>
            </div>
        </div>
    </div>
</div>
