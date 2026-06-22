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
            @include('components.mode-devise-select', ['change' => 'onModeDeviseChange', 'colClass' => 'col-md-4'])
        </div>

        <div class="mt-3 pt-2 border-top d-flex align-items-center gap-2 flex-wrap">
            <div v-if="exercice" class="d-flex align-items-center gap-1">
                <span class="badge bg-soft-info text-info px-2">@{{ exercice.libelle }}</span>
            </div>
            @include('components.mode-devise-legende')
            {{--  <div class="ms-auto d-flex align-items-center gap-2">
                <span class="text-info fs-11 me-2"><i class="ti ti-info-circle me-1"></i>Montants en <strong>@{{ filtres.devise_affichage }}</strong></span>
                <button type="button" class="btn btn-sm btn-outline-primary" @click="loadData" :disabled="isLoading">
                    <i class="ti ti-refresh me-1"></i>Actualiser
                </button>
                @include('components.export-buttons')
            </div>  --}}
        </div>
    </div>
</div>
