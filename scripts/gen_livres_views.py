from pathlib import Path

base = Path(__file__).resolve().parents[1] / "resources" / "views" / "livres"
base.mkdir(parents=True, exist_ok=True)

D = "motion"  # will replace

def d(cls="", close=False):
    if close:
        return f"</{D}>"
    return f'<{D} class="{cls}">' if cls else f"<{D}>"


def wrap(content: str) -> str:
    return f"""@extends('layouts.app')
@section('content')
@include('components.vue-splash')
<{D} class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('livres._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('livres._filtres')
{content}
    </template>
</{D}>
@endsection
@push('scripts')
<script>window.__LIVRES_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/livres/__SCRIPT__.js') }}"></script>
@endpush
""".replace("motion", "motion").replace("<motion", "<div").replace("</motion>", "</div>").replace("motion", "motion")


# fix - use div directly
def wrap2(content, script):
    return f"""@extends('layouts.app')
@section('content')
@include('components.vue-splash')
<div class="content pb-0" id="App" v-cloak>
    <template v-if="!pageReady">
        @include('components.vue-page-loading')
    </template>
    <template v-else>
    @include('livres._nav', ['active' => $page, 'title' => $title, 'breadcrumb' => $title])
    @include('livres._filtres')
{content}
    </template>
</motion>
@endsection
@push('scripts')
<script>window.__LIVRES_PAGE__ = @json($page);</script>
<script type="module" src="{{ asset('assets/js/scripts/livres/{script}.js') }}"></script>
@endpush
""".replace("</motion>", "</div>")


filtres = """
    <div class="card border-0 rounded-0 mb-3">
        <motion class="card-body">
            <motion class="row g-3 align-items-end">
                <motion class="col-md-2">
                    <label class="form-label">Du</label>
                    <input type="date" class="form-control" v-model="filtres.date_debut">
                </motion>
                <motion class="col-md-2">
                    <label class="form-label">Au</label>
                    <input type="date" class="form-control" v-model="filtres.date_fin">
                </motion>
                <motion class="col-md-2">
                    <label class="form-label">Devise affichage</label>
                    <select class="form-select" v-model="filtres.devise_affichage" @change="onFiltreChange">
                        <option v-for="d in options.devises" :key="d.code_iso" :value="d.code_iso">@{{ d.code_iso }} — @{{ d.libelle }}</option>
                    </select>
                </motion>
                <motion class="col-md-3">
                    <label class="form-label">Conversion</label>
                    <select class="form-select" v-model="filtres.mode_conversion" @change="onFiltreChange">
                        <option value="origine">Taux d'origine (à la saisie)</option>
                        <option value="actuel">Taux actuel / du jour</option>
                    </select>
                </motion>
                <motion class="col-md-3">
                    <label class="form-label">Taux du jour</label>
                    <motion class="input-group">
                        <span class="input-group-text">1 USD =</span>
                        <input type="number" step="0.01" class="form-control" v-model.number="tauxUsd" @change="saveTauxUsd">
                        <span class="input-group-text">CDF</span>
                    </motion>
                </motion>
                <motion class="col-12">
                    <span class="badge badge-soft-info me-1" v-if="exercice">@{{ exercice.libelle }}</span>
                    <span class="badge badge-soft-primary">Montants affichés en @{{ filtres.devise_affichage }}</span>
                    <span class="text-muted fs-12 ms-2">Les écritures conservent leur devise et taux d'origine.</span>
                </motion>
            </motion>
        </motion>
    </motion>
""".replace("motion", "motion")

# fix filtres
filtres = filtres.replace("<motion", "<div").replace("</motion>", "</div>")

nav = """@php
    $navItems = [
        'journal' => ['route' => 'accounting.livres.journal', 'label' => 'Journal général', 'icon' => 'ti-notebook'],
        'grand-livre' => ['route' => 'accounting.livres.grand-livre', 'label' => 'Grand livre', 'icon' => 'ti-book-2'],
        'balance' => ['route' => 'accounting.livres.balance', 'label' => 'Balance générale', 'icon' => 'ti-scale'],
        'auxiliaire' => ['route' => 'accounting.livres.auxiliaire', 'label' => 'Balance auxiliaire', 'icon' => 'ti-scale-outline'],
        'lettrage' => ['route' => 'accounting.livres.lettrage', 'label' => 'Lettrage', 'icon' => 'ti-checkup-list'],
        'comptes-tiers' => ['route' => 'accounting.livres.comptes-tiers', 'label' => 'Comptes de tiers', 'icon' => 'ti-users-group'],
    ];
    $active = $active ?? '';
@endphp
<div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
    <div>
        <h4 class="mb-1">{{ $title ?? 'Livres comptables' }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                <li class="breadcrumb-item"><a href="{{ route('accounting.modules.show', ['module' => 'livres']) }}">Livres</a></li>
                <li class="breadcrumb-item active">{{ $breadcrumb ?? '' }}</li>
            </ol>
        </nav>
    </div>
</div>
<ul class="nav nav-tabs nav-tabs-solid nav-tabs-rounded mb-4 flex-wrap">
    @foreach ($navItems as $key => $item)
        <li class="nav-item">
            <a class="nav-link {{ $active === $key ? 'active' : '' }}" href="{{ route($item['route']) }}">
                <i class="ti {{ $item['icon'] }} me-1"></i>{{ $item['label'] }}
            </a>
        </li>
    @endforeach
</ul>
<div v-if="error" class="alert alert-danger alert-dismissible fade show">
    <ul class="mb-0" v-if="Array.isArray(error)"><li v-for="(e,i) in error" :key="i">@{{ e }}</li></ul>
    <span v-else>@{{ error }}</span>
    <button type="button" class="btn-close" @click="error=null"></button>
</div>
"""

balance_body = """
    <div class="card border-0 rounded-0">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">Balance de vérification</h5>
            <button type="button" class="btn btn-sm btn-outline-light" @click="loadData"><i class="ti ti-refresh"></i></button>
        </div>
        <div class="card-body p-0">
            <motion class="table-responsive">
                <table class="table table-bordered table-nowrap mb-0 balance-syscohada">
                    <thead class="table-light text-center">
                        <tr>
                            <th rowspan="2" class="text-start">N° comptes</th>
                            <th rowspan="2" class="text-start">Intitulés</th>
                            <th colspan="2">Soldes début</th>
                            <th colspan="2">Mouvements</th>
                            <th colspan="2">Soldes fin</th>
                        </tr>
                        <tr>
                            <th>Débiteurs</th><th>Créditeurs</th>
                            <th>Débit</th><th>Crédit</th>
                            <th>Débiteurs</th><th>Créditeurs</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading"><td colspan="8" class="text-center py-4">Chargement…</td></tr>
                        <tr v-for="r in lignes" :key="r.num_compte">
                            <td>@{{ r.num_compte }}</td>
                            <td>@{{ r.libelle }}</td>
                            <td class="text-end">@{{ fmt(r.solde_debut_debiteur) }}</td>
                            <td class="text-end">@{{ fmt(r.solde_debut_crediteur) }}</td>
                            <td class="text-end">@{{ fmt(r.mouvement_debit) }}</td>
                            <td class="text-end">@{{ fmt(r.mouvement_credit) }}</td>
                            <td class="text-end">@{{ fmt(r.solde_fin_debiteur) }}</td>
                            <td class="text-end">@{{ fmt(r.solde_fin_crediteur) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-semibold" v-if="totaux">
                        <tr>
                            <td colspan="2" class="text-end">TOTAUX</td>
                            <td class="text-end">@{{ fmt(totaux.solde_debut_debiteur) }}</td>
                            <td class="text-end">@{{ fmt(totaux.solde_debut_crediteur) }}</td>
                            <td class="text-end">@{{ fmt(totaux.mouvement_debit) }}</td>
                            <td class="text-end">@{{ fmt(totaux.mouvement_credit) }}</td>
                            <td class="text-end">@{{ fmt(totaux.solde_fin_debiteur) }}</td>
                            <td class="text-end">@{{ fmt(totaux.solde_fin_crediteur) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </motion>
        </motion>
    </motion>
""".replace("motion", "motion").replace("<motion", "<motion>").replace("<motion>", "<div>").replace("</motion>", "</motion>")

balance_body = balance_body.replace("<motion>", "<div>").replace("</motion>", "</div>")

(base / "_filtres.blade.php").write_text(filtres, encoding="utf-8")
(base / "_nav.blade.php").write_text(nav, encoding="utf-8")
(base / "balance.blade.php").write_text(wrap2(balance_body, "balance"), encoding="utf-8")

print("OK partials + balance")
