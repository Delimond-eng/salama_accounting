<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="row g-3 align-items-center">
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Du</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="ti ti-calendar fs-14"></i></span>
                    <input type="date" class="form-control border-start-0 ps-0" v-model="filtres.date_debut" @change="onDatesChange">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Au</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="ti ti-calendar fs-14"></i></span>
                    <input type="date" class="form-control border-start-0 ps-0" v-model="filtres.date_fin" @change="onDatesChange">
                </div>
            </div>
            <div class="col-md-2" v-if="page === 'journal'">
                <label class="form-label text-muted fs-12 mb-1">Journal</label>
                <select class="form-select form-select-sm" v-model.number="journalId" @change="onFiltreChange">
                    <option :value="null">Tous les journaux</option>
                    <option v-for="j in journaux" :key="j.id" :value="j.id">@{{ j.code }}</option>
                </select>
            </div>
            <div class="col-md-2" v-if="page === 'balance'">
                <label class="form-label text-muted fs-12 mb-1">Classe</label>
                <select class="form-select form-select-sm" v-model="classe" @change="loadData">
                    <option value="">Toutes</option>
                    <option v-for="n in 9" :key="n" :value="n">Classe @{{ n }}</option>
                </select>
            </div>
            <div class="col-md-2" v-if="page === 'auxiliaire'">
                <label class="form-label text-muted fs-12 mb-1">Type tiers</label>
                <select class="form-select form-select-sm" v-model="typeTiers" @change="loadData">
                    <option value="">Tous</option>
                    <option value="client">Clients</option>
                    <option value="fournisseur">Fournisseurs</option>
                </select>
            </div>
            @include('components.mode-devise-select', ['change' => 'onFiltreChange'])
        </div>
        <div class="mt-3 pt-2 border-top d-flex align-items-center gap-2 flex-wrap" v-if="exercice">
            <span class="badge bg-soft-info text-info fs-11">@{{ exercice.libelle }}</span>
            @include('components.mode-devise-legende')
        </div>
    </div>
</div>
