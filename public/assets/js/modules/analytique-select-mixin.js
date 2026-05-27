import { get } from "./http.js";

/**
 * Autocomplete compte analytique (section) par ligne de saisie.
 */
export const analytiqueSelectMixin = {
    data() {
        return {
            sectionUi: {},
        };
    },

    methods: {
        sectionUiKey(idx) {
            return `l${idx}`;
        },

        sectionUiOpen(idx) {
            return !!this.sectionUi[this.sectionUiKey(idx)]?.open;
        },

        sectionUiLoading(idx) {
            return !!this.sectionUi[this.sectionUiKey(idx)]?.loading;
        },

        sectionUiResults(idx) {
            return this.sectionUi[this.sectionUiKey(idx)]?.results || [];
        },

        sectionDisplayText(idx) {
            const l = this.lignes[idx];
            if (!l) return "";
            if (l._section_label) return l._section_label;
            if (l.section_analytique_id && l._section) {
                return `${l._section.axe?.code || ""} / ${l._section.code} — ${l._section.libelle}`;
            }
            return "";
        },

        onSectionSearchInput(idx, ev) {
            const q = ev.target.value;
            this.$set(this.sectionUi, this.sectionUiKey(idx), {
                open: true,
                loading: true,
                results: [],
                q,
            });
            this.searchSections(idx, q);
        },

        onSectionSearchFocus(idx) {
            const key = this.sectionUiKey(idx);
            const cur = this.sectionUi[key] || {};
            this.$set(this.sectionUi, key, { ...cur, open: true });
            if (!cur.results?.length) {
                this.searchSections(idx, cur.q || "");
            }
        },

        onSectionSearchBlur(idx) {
            setTimeout(() => {
                const key = this.sectionUiKey(idx);
                if (this.sectionUi[key]) {
                    this.$set(this.sectionUi, key, { ...this.sectionUi[key], open: false });
                }
            }, 200);
        },

        async searchSections(idx, q) {
            const ligne = this.lignes[idx];
            const params = new URLSearchParams({ q: q || "" });
            if (ligne?.num_compte) params.set("num_compte", ligne.num_compte);
            const { data } = await get(`/accounting/saisie/sections/search?${params}`);
            const key = this.sectionUiKey(idx);
            this.$set(this.sectionUi, key, {
                ...(this.sectionUi[key] || {}),
                loading: false,
                results: data.status === "success" ? data.sections || [] : [],
            });
        },

        selectSectionOption(idx, section) {
            const l = this.lignes[idx];
            if (!l) return;
            l.section_analytique_id = section.id;
            l._section = section;
            l._section_label = `${section.axe?.code || ""} / ${section.code} — ${section.libelle}`;
            this.$set(this.sectionUi, this.sectionUiKey(idx), { open: false, results: [], loading: false });
            if (typeof this.onSectionSelectChange === "function") {
                this.onSectionSelectChange(idx);
            }
        },

        ligneAfficheAnalytique(ligne) {
            if (!ligne?.num_compte) return !!this.analytiqueObligatoireJournal;
            const compte = (this.comptesCache || {})[ligne.num_compte];
            return this.analytiqueObligatoireJournal || compte?.exige_analytique;
        },
    },
};
