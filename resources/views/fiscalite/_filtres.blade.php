<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-center">
            <div class="col-md-2" v-if="exercices && exercices.length">
                <label class="form-label text-muted fs-12 mb-1">Exercice</label>
                <select class="form-select form-select-sm" v-model.number="filtres.exercice_id" @change="onExerciceChange">
                    <option v-for="ex in exercices" :key="ex.id" :value="ex.id">@{{ ex.libelle }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Du</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="ti ti-calendar fs-14"></i></span>
                    <input type="date" class="form-control border-start-0 ps-0" v-model="filtres.date_debut" @change="loadData">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Au</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="ti ti-calendar fs-14"></i></span>
                    <input type="date" class="form-control border-start-0 ps-0" v-model="filtres.date_fin" @change="loadData">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Devise d'affichage</label>
                <select class="form-select form-select-sm" v-model="filtres.devise_affichage" @change="loadData">
                    <option v-for="d in options.devises" :key="d.code_iso" :value="d.code_iso">@{{ d.code_iso }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Mode de conversion</label>
                <select class="form-select form-select-sm" v-model="filtres.mode_conversion" @change="loadData">
                    <option value="origine">Taux d'origine</option>
                    <option value="actuel">Taux actuel</option>
                </select>
            </div>

            <div class="col-md-2" v-if="filtres.mode_conversion === 'actuel' || filtres.devise_affichage !== 'CDF'">
                <label class="form-label text-muted fs-12 mb-1">Taux (1 USD = )</label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" class="form-control" v-model.number="filtres.taux" @change="loadData">
                    <span class="input-group-text bg-light fs-11">CDF</span>
                </div>
            </div>
        </div>

        <div class="mt-3 pt-2 border-top d-flex align-items-center gap-2" v-if="exercice">
            <div class="d-flex align-items-center gap-1">
                <span class="badge bg-soft-info text-info px-2">@{{ exercice.libelle }}</span>
            </div>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-info fs-11"><i class="ti ti-info-circle me-1"></i>Montants exprimés en <strong>@{{ filtres.devise_affichage }}</strong></span>
            </div>
        </div>
    </div>
</div>
