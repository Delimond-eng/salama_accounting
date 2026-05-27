<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="row g-3 align-items-center">
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Du</label>
                <input type="date" class="form-control form-control-sm" v-model="filtres.date_debut" @change="loadList">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Au</label>
                <input type="date" class="form-control form-control-sm" v-model="filtres.date_fin" @change="loadList">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Recherche</label>
                <input type="text" class="form-control form-control-sm" placeholder="Piece, libelle…" v-model="search" @input="debounceLoad">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Statut</label>
                <select class="form-select form-select-sm" v-model="filtreStatut" @change="loadList">
                    <option value="">Tous</option>
                    <option value="brouillon">Brouillon</option>
                    <option value="validee">Validée</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label text-muted fs-12 mb-1">Conversion</label>
                <select class="form-select form-select-sm" v-model="filtres.mode_conversion" @change="loadList">
                    <option value="origine">Taux d'origine</option>
                    <option value="actuel">Taux actuel</option>
                </select>
            </div>

            <div class="col-md-2" v-if="filtres.mode_conversion === 'actuel'">
                <label class="form-label text-muted fs-12 mb-1">Taux (1 USD = )</label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" class="form-control" v-model.number="filtres.taux" @change="loadList">
                    <span class="input-group-text bg-light fs-11">CDF</span>
                </div>
            </div>

            {{--  <div class="col-auto ms-auto">
                @include('components.export-buttons')
            </div>  --}}
        </div>
    </div>
</div>
