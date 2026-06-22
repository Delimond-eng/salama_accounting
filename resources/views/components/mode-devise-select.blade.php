{{--
    Sélecteur unifié des 6 modes devise.
    Props :
      - model : expression v-model Vue (défaut filtres.mode_devise)
      - change : handler @change (défaut onModeDeviseChange)
      - colClass : classes Bootstrap colonne (défaut col-md-4)
      - disabled : expression Vue pour désactiver le select
--}}
@props([
    'model' => 'filtres.mode_devise',
    'change' => 'onModeDeviseChange',
    'colClass' => 'col-md-4',
    'disabled' => 'false',
])

<div class="{{ $colClass }}">
    <label class="form-label text-muted fs-12 mb-1">
        <i class="ti ti-coins fs-14 me-1"></i>Mode d'affichage devise
    </label>
    <select class="form-select form-select-sm" v-model="{{ $model }}" @change="{{ $change }}" :disabled="{{ $disabled }}">
        <option v-for="m in modesDeviseListe" :key="m.id" :value="m.id">@{{ m.label }}</option>
    </select>
</div>
