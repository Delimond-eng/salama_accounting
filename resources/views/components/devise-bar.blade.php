{{-- Sélecteur global devise / consolidation (header) — 6 modes unifiés --}}
<div id="DeviseBar" class="devise-bar d-none d-lg-flex align-items-center gap-2 me-2" v-cloak v-if="loaded">
    <select
        class="form-select form-select-sm border-0 shadow-sm"
        style="min-width:230px"
        v-model="filtres.mode_devise"
        @change="save"
        title="Mode d'affichage devise"
    >
        <option v-for="m in modesDeviseListe" :key="m.id" :value="m.id">@{{ m.label }}</option>
    </select>
    <span class="badge badge-soft-primary text-nowrap fs-11" :title="noteModeDevise">@{{ libelleModeDevise }}</span>
</div>
