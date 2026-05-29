import { get, postJson } from "../../modules/http.js";
import { compteSelectMixin } from "../../modules/compte-select-mixin.js";
import { analytiqueSelectMixin } from "../../modules/analytique-select-mixin.js";
import { saisieMixin } from "./saisie-common.js";

// Directive Select2 optimisée pour Vue.js
Vue.directive('select2', {
    inserted: function (el) {
        const $el = $(el);
        $el.select2({
            width: '100%',
            placeholder: $el.attr('placeholder') || 'Sélectionner...',
            allowClear: true,
            language: {
                searching: () => "Recherche...",
                noResults: () => "Aucun résultat"
            }
        }).on('change', function () {
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });

        $el.on('select2:open', function() {
            setTimeout(() => {
                const searchField = document.querySelector('.select2-container--open .select2-search__field');
                if (searchField) searchField.focus();
            }, 0);
        });
    },
    componentUpdated: function (el) {
        $(el).trigger('change.select2');
    },
    unbind: function (el) {
        $(el).off().select2('destroy');
    }
});

new Vue({
    el: "#App",
    mixins: [saisieMixin, compteSelectMixin, analytiqueSelectMixin],
    data() {
        return {
            ecritureId: window.__ECRITURE_ID__ || null,
            duplicateId: window.__DUPLICATE_ID__ || null,
            multiDevise: false,
            journalVerrouille: false,
            template: [],
            tiersOptions: [],
            entete: {
                id: null,
                exercice_id: null,
                journal_id: null,
                date_ecriture: new Date().toISOString().slice(0, 10),
                date_echeance: null,
                libelle: "",
                reference_facture: "",
                reference_externe: "",
                devise: "CDF",
                taux_change: 1,
                type_ecriture: "normale",
            },
            lignes: [],
            analytiqueObligatoireJournal: false,
            axesAnalytiques: [],
            comptesCache: {},
        };
    },

    watch: {
        "entete.journal_id"() {
            this.appliquerDeviseJournal();
            const j = (this.journaux || []).find((x) => x.id === this.entete.journal_id);
            this.analytiqueObligatoireJournal = !!j?.analytique_obligatoire;
        },
        // Propagation automatique du libellé général aux lignes
        "entete.libelle"(newVal, oldVal) {
            this.lignes.forEach(l => {
                // On met à jour si la ligne est vide ou si elle avait la valeur précédente (non modifiée manuellement)
                if (!l.libelle || l.libelle === (oldVal || "")) {
                    l.libelle = newVal;
                }
            });
        },
        tiersOptions() { this.refreshSelect2(); },
        axesAnalytiques() { this.refreshSelect2(); },
        journaux() { this.refreshSelect2(); }
    },

    computed: {
        showColonneAnalytique() {
            return this.analytiqueObligatoireJournal || (this.axesAnalytiques || []).length > 0;
        },
        sectionsListe() {
            return (this.axesAnalytiques || []).flatMap((axe) =>
                (axe.sections || []).map((s) => ({ ...s, axe }))
            );
        },
        listeUrl() {
            return `/accounting/saisie/${this.page}`;
        },
        deviseVerrouillee() {
            return this.journalDeviseEtrangere;
        },
        totalDebit() {
            return this.lignes.reduce((s, l) => s + (parseFloat(l.debit) || 0), 0);
        },
        totalCredit() {
            return this.lignes.reduce((s, l) => s + (parseFloat(l.credit) || 0), 0);
        },
        ecart() {
            return Math.abs(this.totalDebit - this.totalCredit);
        },
        equilibre() {
            return this.totalDebit > 0 && this.ecart < 0.01;
        },
    },

    methods: {
        refreshSelect2() {
            this.$nextTick(() => {
                $('select[v-select2]').trigger('change.select2');
            });
        },
        async initPage() {
            await this.loadTiers();
            this.initEntete();

            if (this.ecritureId) {
                await this.loadEcriture(this.ecritureId);
            } else if (this.duplicateId) {
                await this.loadEcriture(this.duplicateId, true);
            } else {
                this.appliquerTemplate();
            }
            await this.prefetchComptesFromLignes(this.lignes);
        },

        initEntete() {
            if (this.exercice) this.entete.exercice_id = this.exercice.id;
            if (this.journal) {
                this.entete.journal_id = this.journal.id;
                this.journalVerrouille = !!this.journal.id && this.page !== "nouvelle";
            }
            this.appliquerDeviseJournal();
        },

        async loadEcriture(id, isDuplicate = false) {
            const { data } = await get(`/accounting/saisie/ecritures/${id}`);
            if (data.status !== "success") return;
            const e = data.ecriture;
            this.entete = {
                id: isDuplicate ? null : e.id,
                exercice_id: e.exercice_id,
                journal_id: e.journal_id,
                date_ecriture: isDuplicate ? new Date().toISOString().slice(0, 10) : (e.date_ecriture?.substring?.(0, 10) || e.date_ecriture),
                date_echeance: isDuplicate ? null : (e.date_echeance?.substring?.(0, 10) || null),
                libelle: e.libelle,
                reference_facture: isDuplicate ? "" : e.reference_facture,
                reference_externe: isDuplicate ? "" : e.reference_externe,
                devise: e.devise,
                taux_change: e.taux_change,
                type_ecriture: e.type_ecriture,
            };
            this.lignes = (e.lignes || []).map((l) => {
                const sec = l.section_analytique || l.sectionAnalytique;
                const row = {
                    id_vue: Math.random().toString(36).substring(7),
                    num_compte: l.num_compte,
                    libelle: l.libelle,
                    debit: l.debit,
                    credit: l.credit,
                    tiers_id: l.tiers_id,
                    montant_devise: l.montant_devise,
                    taux_change: l.taux_change,
                    section_analytique_id: l.section_analytique_id,
                    _section: sec,
                };
                if (sec) {
                    row._section_label = `${sec.axe?.code || ""} / ${sec.code} — ${sec.libelle}`;
                }
                return row;
            });
            if (isDuplicate) {
                this.$nextTick(() => {
                   this.lignes.forEach((_, i) => this.onSectionSelectChange(i));
                });
            }
        },

        async loadTiers() {
            const { data } = await get("/accounting/saisie/tiers/search?q=");
            if (data.status === "success") this.tiersOptions = data.tiers || [];
        },

        isAnalytiqueEligible(numCompte) {
            if (!numCompte) return false;
            const c = String(numCompte).charAt(0);
            return c === '6' || c === '7';
        },

        selectCompteOption(key, compte) {
            this.setCompteNumero(key, compte.num_compte, compte.libelle);
            if (typeof key === "number" && this.lignes[key]) {
                const ligne = this.lignes[key];
                if (!this.isAnalytiqueEligible(ligne.num_compte)) {
                    ligne.section_analytique_id = null;
                    ligne._section = null;
                    ligne._section_label = "";
                }
            }
        },

        appliquerTemplate() {
            const base = { section_analytique_id: null, _section: null, _section_label: "" };
            if (this.template?.length) {
                this.lignes = this.template.map((l) => ({
                    ...l, ...base,
                    libelle: l.libelle || this.entete.libelle,
                    debit: 0,
                    credit: 0,
                    id_vue: Math.random().toString(36).substring(7)
                }));
            } else {
                this.lignes = [
                    { id_vue: 'l1', num_compte: "", libelle: this.entete.libelle, debit: 0, credit: 0, tiers_id: null, ...base },
                    { id_vue: 'l2', num_compte: "", libelle: this.entete.libelle, debit: 0, credit: 0, tiers_id: null, ...base },
                ];
            }
            if (this.entete.libelle === "" && this.journal) {
                this.entete.libelle = "Écriture " + this.journal.libelle;
                this.lignes.forEach(l => l.libelle = this.entete.libelle);
            }
        },

        ajouterLigne() {
            this.lignes.push({
                id_vue: Math.random().toString(36).substring(7),
                num_compte: "",
                libelle: this.entete.libelle,
                debit: 0,
                credit: 0,
                tiers_id: null,
                section_analytique_id: null,
            });
        },

        onSectionSelectChange(idx) {
            const l = this.lignes[idx];
            if (!l) return;
            const section = this.sectionsListe.find((s) => s.id === l.section_analytique_id);
            if (section) {
                l._section = section;
                l._section_label = `${section.axe?.code || ""} / ${section.code} — ${section.libelle}`;
            } else {
                l._section = null;
                l._section_label = "";
            }
        },

        supprimerLigne(idx) {
            if (this.lignes.length > 2) this.lignes.splice(idx, 1);
        },

        onMontant(ligne, col) {
            if (col === "debit" && ligne.debit > 0) ligne.credit = 0;
            if (col === "credit" && ligne.credit > 0) ligne.debit = 0;
        },

        async fetchTaux() {
            const { data } = await get(
                `/accounting/saisie/taux?devise=${this.entete.devise}&date=${this.entete.date_ecriture}`
            );
            if (data.status === "success") this.entete.taux_change = data.taux;
        },

        lignesPayload() {
            return this.lignes.map((l) => {
                return {
                    num_compte: (l.num_compte || "").trim(),
                    libelle: l.libelle,
                    debit: parseFloat(l.debit) || 0,
                    credit: parseFloat(l.credit) || 0,
                    tiers_id: l.tiers_id || null,
                    montant_devise: l.montant_devise,
                    taux_change: l.taux_change,
                    section_analytique_id: l.section_analytique_id,
                };
            });
        },

        async save(valider) {
            if (!this.equilibre) {
                this.error = ["L'écriture doit être équilibrée (débit = crédit)."];
                return;
            }
            this.isLoading = true;
            const { data } = await postJson("/accounting/saisie/ecritures/store", {
                entete: this.entete,
                lignes: this.lignesPayload(),
                valider,
            });
            this.isLoading = false;
            if (!this.handleResponse(data)) return;
            if (data.ecriture?.id && !this.warnings?.length) {
                window.location.href = this.listeUrl;
            }
        },
    },
});
