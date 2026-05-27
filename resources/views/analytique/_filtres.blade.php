<div class="card border-0 rounded-0 mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label fs-12">Date début</label>
                <input type="date" class="form-control form-control-sm" v-model="filtres.date_debut" @change="loadData">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-12">Date fin</label>
                <input type="date" class="form-control form-control-sm" v-model="filtres.date_fin" @change="loadData">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-12">Devise</label>
                <select class="form-select form-select-sm" v-model="filtres.devise_affichage" @change="loadData">
                    <option v-for="d in options.devises" :key="d.code_iso" :value="d.code_iso">@{{ d.code_iso }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-12">Présentation</label>
                <select class="form-select form-select-sm" v-model="filtres.scope_devise" @change="loadData">
                    <option value="natif">Native (@{{ filtres.devise_affichage }})</option>
                    <option value="consolide">Consolidée</option>
                </select>
            </div>
            <div class="col-md-2" v-if="filtres.scope_devise === 'consolide'">
                <label class="form-label fs-12">Taux</label>
                <select class="form-select form-select-sm" v-model="filtres.mode_conversion" @change="loadData">
                    <option value="origine">Taux d'origine</option>
                    <option value="actuel">Taux actuel</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-12">Axe</label>
                <select class="form-select form-select-sm" v-model.number="filtres.axe_id" @change="loadData">
                    <option :value="null">Tous</option>
                    <option v-for="a in axes" :key="a.id" :value="a.id">@{{ a.code }} — @{{ a.libelle }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fs-12">Compte analytique</label>
                <select class="form-select form-select-sm" v-model.number="filtres.section_id" @change="loadData">
                    <option :value="null">Tous</option>
                    <option v-for="s in sectionsFiltre" :key="s.id" :value="s.id">@{{ s.code }} — @{{ s.libelle }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-12">Journal</label>
                <select class="form-select form-select-sm" v-model.number="filtres.journal_id" @change="loadData">
                    <option :value="null">Tous</option>
                    <option v-for="j in journaux" :key="j.id" :value="j.id">@{{ j.code }}</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-primary w-100" @click="loadData" :disabled="isLoading"><i class="ti ti-refresh"></i></button>
            </div>
        </div>
        <div class="mt-2" v-if="result?.devise">
            <span class="badge badge-soft-primary">Montants en @{{ result.devise }}</span>
        </div>
    </div>
</div>
