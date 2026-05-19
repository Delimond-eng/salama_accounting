{{-- Select compte éditable — nécessite compteSelectMixin. Variable : $compteKey (string ou utiliser idx dans v-for) --}}
@php($ck = $compteKey ?? 'single')
<div class="compte-select-wrap position-relative">
    <input
        type="text"
        class="{{ $inputClass ?? 'form-control form-control-sm' }}"
        :value="compteDisplayText('{{ $ck }}')"
        @input="onCompteSearchInput('{{ $ck }}', $event)"
        @focus="onCompteSearchFocus('{{ $ck }}')"
        @blur="onCompteSearchBlur('{{ $ck }}')"
        placeholder="{{ $placeholder ?? 'Rechercher un compte (n° ou libellé)…' }}"
        autocomplete="off"
    >
    <ul
        v-show="compteUiOpen('{{ $ck }}')"
        class="dropdown-menu show w-100 shadow-sm compte-select-dropdown"
    >
        <li v-if="compteUiLoading('{{ $ck }}')">
            <span class="dropdown-item text-muted"><i class="ti ti-loader ti-spin me-1"></i> Recherche…</span>
        </li>
        <li v-else-if="!compteUiResults('{{ $ck }}').length">
            <span class="dropdown-item text-muted">Aucun compte trouvé</span>
        </li>
        <li v-for="c in compteUiResults('{{ $ck }}')" :key="c.id">
            <a
                href="javascript:void(0)"
                class="dropdown-item py-2"
                @mousedown.prevent="selectCompteOption('{{ $ck }}', c)"
            >
                <span class="fw-medium text-primary">@{{ c.num_compte }}</span>
                <span class="d-block text-muted fs-12 text-truncate">@{{ c.libelle }}</span>
            </a>
        </li>
    </ul>
</div>
