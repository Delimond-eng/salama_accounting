<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-center">
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
                <label class="form-label text-muted fs-12 mb-1">Devise</label>
                <select class="form-select form-select-sm" v-model="filtres.devise_affichage" @change="loadData">
                    <option v-for="d in options.devises" :key="d.code_iso" :value="d.code_iso">@{{ d.code_iso }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Présentation</label>
                <select class="form-select form-select-sm" v-model="filtres.scope_devise" @change="loadData">
                    <option value="natif">Native</option>
                    <option value="consolide">Consolidée</option>
                </select>
            </div>
            <div class="col-md-2" v-if="filtres.scope_devise === 'consolide'">
                <label class="form-label text-muted fs-12 mb-1">Conversion</label>
                <select class="form-select form-select-sm" v-model="filtres.mode_conversion" @change="loadData">
                    <option value="origine">Taux d'origine</option>
                    <option value="actuel">Taux actuel</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Axe</label>
                <select class="form-select form-select-sm" v-model.number="filtres.axe_id" @change="loadData">
                    <option :value="null">Tous les axes</option>
                    <option v-for="a in axes" :key="a.id" :value="a.id">@{{ a.code }} — @{{ a.libelle }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted fs-12 mb-1">Compte analytique</label>
                <select class="form-select form-select-sm" v-model.number="filtres.section_id" @change="loadData">
                    <option :value="null">Tous les comptes</option>
                    <option v-for="s in sectionsFiltre" :key="s.id" :value="s.id">@{{ s.code }} — @{{ s.libelle }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Journal</label>
                <select class="form-select form-select-sm" v-model.number="filtres.journal_id" @change="loadData">
                    <option :value="null">Tous les journaux</option>
                    <option v-for="j in journaux" :key="j.id" :value="j.id">@{{ j.code }}</option>
                </select>
            </div>
        </div>
        <div class="mt-3 pt-2 border-top d-flex align-items-center gap-2" v-if="result?.devise">
            <span class="badge bg-soft-primary text-primary fs-11">Montants exprimés en @{{ result.devise }}</span>
        </div>
    </div>
</div>
