    <div class="card border-0 rounded-0 mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Du</label>
                    <input type="date" class="form-control" v-model="filtres.date_debut" @change="loadList">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Au</label>
                    <input type="date" class="form-control" v-model="filtres.date_fin" @change="loadList">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Recherche</label>
                    <input type="text" class="form-control" placeholder="Piece, libelle, reference…" v-model="search" @input="debounceLoad">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Statut</label>
                    <select class="form-select" v-model="filtreStatut" @change="loadList">
                        <option value="">Tous</option>
                        <option value="brouillon">Brouillon</option>
                        <option value="validee">Validee</option>
                    </select>
                </div>
                <div class="col-auto">
                    @include('components.export-buttons')
                </div>
            </div>
        </div>
    </div>
