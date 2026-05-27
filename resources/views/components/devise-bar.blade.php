{{-- Sélecteur global devise / consolidation (header) --}}
<div id="DeviseBar" class="devise-bar d-none d-lg-flex align-items-center gap-2 me-2" v-cloak v-if="loaded">
    <label class="form-label mb-0 text-muted fs-12 text-nowrap">Affichage</label>
    <select class="form-select form-select-sm border-0 shadow-sm" style="min-width:88px" v-model="prefs.devise_affichage" @change="save">
        <option v-for="d in options.devises" :key="d.code_iso" :value="d.code_iso">@{{ d.code_iso }}</option>
    </select>
    <select class="form-select form-select-sm border-0 shadow-sm" style="min-width:120px" v-model="prefs.scope_devise" @change="save">
        <option value="natif">Natif</option>
        <option value="consolide">Consolidé</option>
    </select>
    <select v-if="prefs.scope_devise === 'consolide'" class="form-select form-select-sm border-0 shadow-sm" style="min-width:110px" v-model="prefs.mode_conversion" @change="save">
        <option value="origine">Taux origine</option>
        <option value="actuel">Taux actuel</option>
    </select>
    <span class="badge badge-soft-primary text-nowrap">@{{ libelle }}</span>
</div>
