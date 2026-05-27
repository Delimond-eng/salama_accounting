@extends('layouts.app')
@section('content')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">@include('components.vue-page-loading')</template>
    <template v-else>
    @include('analytique._nav', ['active' => 'axes', 'title' => 'Axes & comptes analytiques'])
    <div v-if="message" class="alert alert-success">@{{ message }}</div>
    <div v-if="error" class="alert alert-danger">@{{ error }}</div>

    <div class="card border-0 mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" v-model="axesRestreints" id="chk_rest" @change="saveConfig">
                <label class="form-check-label" for="chk_rest">Restreindre les axes par compte du plan comptable</label>
            </div>
            <button type="button" class="btn btn-primary btn-sm" @click="openAxeForm"><i class="ti ti-plus me-1"></i>Nouvel axe</button>
        </div>
    </div>

    <div v-for="axe in axes" :key="axe.id" class="card border-0 mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><span class="badge badge-soft-primary me-2">@{{ axe.code }}</span>@{{ axe.libelle }}</h6>
            <div>
                <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="openSectionForm(axe)"><i class="ti ti-plus"></i> Compte</button>
                <button type="button" class="btn btn-sm btn-outline-light" @click="editAxe(axe)"><i class="ti ti-edit"></i></button>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Code</th><th>Libellé</th><th class="text-end">Budget</th><th>Actif</th><th></th></tr></thead>
                <tbody>
                    <tr v-if="!axe.sections?.length"><td colspan="5" class="text-muted text-center py-3">Aucun compte analytique</td></tr>
                    <tr v-for="s in axe.sections" :key="s.id">
                        <td>@{{ s.code }}</td>
                        <td>@{{ s.libelle }}</td>
                        <td class="text-end">@{{ s.budget ? fmt(s.budget) : '—' }}</td>
                        <td><span class="badge" :class="s.actif ? 'badge-soft-success' : 'badge-soft-secondary'">@{{ s.actif ? 'Oui' : 'Non' }}</span></td>
                        <td><button type="button" class="btn btn-sm btn-outline-light" @click="editSection(axe, s)"><i class="ti ti-edit"></i></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal_axe" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <form @submit.prevent="saveAxe">
            <div class="modal-header"><h5 class="modal-title">@{{ formAxe.id ? 'Modifier' : 'Nouvel' }} axe</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Code</label><input class="form-control" v-model="formAxe.code" required></div>
                <div class="mb-2"><label class="form-label">Libellé</label><input class="form-control" v-model="formAxe.libelle" required></div>
                <div class="mb-2"><label class="form-label">Description</label><textarea class="form-control" rows="2" v-model="formAxe.description"></textarea></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" v-model="formAxe.actif" id="axe_act"><label class="form-check-label" for="axe_act">Actif</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-white border" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn btn-primary">Enregistrer</button></div>
        </form>
    </div></div></div>

    <div class="modal fade" id="modal_section" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <form @submit.prevent="saveSection">
            <div class="modal-header"><h5 class="modal-title">Compte analytique</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Code</label><input class="form-control" v-model="formSection.code" required></div>
                <div class="mb-2"><label class="form-label">Libellé</label><input class="form-control" v-model="formSection.libelle" required></div>
                <div class="mb-2"><label class="form-label">Budget</label><input type="number" step="0.01" class="form-control" v-model.number="formSection.budget"></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" v-model="formSection.actif" id="sec_act"><label class="form-check-label" for="sec_act">Actif</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-white border" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn btn-primary">Enregistrer</button></div>
        </form>
    </div></div></div>
    </template>
</div>
@endsection
@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/analytique/axes.js') }}"></script>
@endpush
