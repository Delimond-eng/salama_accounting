import {get, postJson } from "../../modules/http.js";
import { vuePageMixin } from "../../modules/vue-page-mixin.js";
import { exportMixin } from "../../modules/export-mixin.js";

export const etatsMixin = {
    mixins: [vuePageMixin, exportMixin],

    data() {
        return {
            page: window.__ETATS_PAGE__ || "bilan",
            societe: null,
            exercice: null,
            exerciceN1: null,
            exercices: [],
            options: { devises: [], devise_affichage: "CDF", mode_conversion: "origine" },
            filtres: {
                date_arrete: "",
                exercice_id: null,
                devise_affichage: "CDF",
                mode_conversion: "origine",
                avec_n1: true,
                taux: 1,
            },
            data: null,
            error: null,
            isLoading: false,
            exportBase: `/accounting/export/etats/${window.__ETATS_PAGE__ || "bilan"}`,
        };
    },

    computed: {
        pageTitle() {
            const titles = {
                bilan: "Bilan (Actif/Passif)",
                "compte-resultat": "Compte de Résultat",
                "flux-tresorerie": "Tableau des Flux de Trésorerie",
                "variation-kp": "Variation des Capitaux Propres",
                annexes: "Annexes SYSCOHADA",
                comparatif: "Comparatif N / N-1",
                exports: "Centre d'Exportation"
            };
            return titles[this.page] || "États Financiers";
        },
        pageSubtitle() {
            let parts = [];
            if (this.filtres.date_arrete) {
                parts.push(`Arrêté au ${this.fmtDate(this.filtres.date_arrete)}`);
            }
            parts.push(`Devise: ${this.filtres.devise_affichage}`);
            if (this.filtres.avec_n1 && this.exerciceN1) {
                parts.push(`Comparatif N-1 actif`);
            }
            return parts.join(' • ');
        }
    },

    async mounted() {
        await this.bootPage(async() => {
            await this.loadMetadata();
            if (typeof this.initPage === "function") {
                await this.initPage();
            }
        });
    },

    methods: {
        async loadMetadata() {
            const { data } = await get("/accounting/etats/metadata");
            if (data.status !== "success") {
                return;
            }
            this.societe = data.societe;
            this.exercice = data.exercice;
            this.exerciceN1 = data.exercice_n1;
            this.exercices = data.exercices || [];
            this.options = data.options || this.options;
            this.filtres.date_arrete = data.date_arrete || this.filtres.date_arrete;
            this.filtres.exercice_id = data.exercice?.id || null;
            this.filtres.devise_affichage = this.options.devise_affichage || "CDF";
            this.filtres.mode_conversion = this.options.mode_conversion || "origine";

            if (data.taux_usd) {
                this.filtres.taux = data.taux_usd;
            }
        },

        queryParams() {
            return new URLSearchParams({
                date_arrete: this.filtres.date_arrete,
                exercice_id: this.filtres.exercice_id || "",
                devise_affichage: this.filtres.devise_affichage,
                mode_conversion: this.filtres.mode_conversion,
                avec_n1: this.filtres.avec_n1 ? "1" : "0",
                taux: this.filtres.taux,
            }).toString();
        },

        onExerciceChange() {
            const ex = this.exercices.find((e) => e.id === this.filtres.exercice_id);
            if (ex?.date_fin) {
                this.filtres.date_arrete = String(ex.date_fin).slice(0, 10);
            }
            this.loadData();
        },

        onDatesChange() {
            this.loadData();
        },

        async onFiltreChange() {
            await this.loadData();
        },

        async saveTauxUsd() {
            if (!this.filtres.taux || this.filtres.taux <= 0) return;
            const date = new Date().toISOString().slice(0, 10);
            const { data } = await postJson("/accounting/parametres/taux-change/save", {
                devise_code: "USD",
                date_taux: date,
                taux: this.filtres.taux,
            });
            if (data.errors) {
                this.error = data.errors;
                return;
            }
            if (this.filtres.mode_conversion === "actuel") {
                await this.loadData();
            }
        },

        handleResponse(data) {
            if (data.errors) {
                this.error = data.errors;
                return false;
            }
            this.error = null;
            return true;
        },

        fmt(v) {
            if (v === null || v === undefined || v === "") {
                return "—";
            }
            return new Intl.NumberFormat("fr-FR", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(v) || 0);
        },

        fmtDate(d) {
            if (!d) return "";
            const parts = d.split('-');
            if (parts.length !== 3) return d;
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        },

        fmtCompact(v) {
            if (v === null || v === undefined || v === "") {
                return "—";
            }
            const n = Number(v) || 0;
            const d = this.filtres?.devise_affichage || "CDF";
            const formatter = new Intl.NumberFormat("fr-FR", {
                notation: "compact",
                compactDisplay: "short",
                maximumFractionDigits: 1,
            });
            return formatter.format(n) + ` ${d}`;
        },

        fmtMontant(v) {
            return this.fmt(v);
        },

        isTitre(l) {
            return (l.type || "") === "titre";
        },

        isTotal(l) {
            return (l.type || "") === "total" || (l.type || "") === "formule";
        },

        rowClass(l) {
            if (this.isTitre(l)) return "row-titre bg-light fw-bold text-uppercase text-dark small";
            if (this.isTotal(l)) return "row-formule fw-bold bg-primary-soft text-primary border-top border-primary border-opacity-25";
            return "align-middle";
        },

        exportUrl(format) {
            let type = this.page;
            if (type === 'exports') {
                type = 'globaux';
            }
            return `/accounting/export/etats/${type}/${format}?${this.queryParams()}`;
        },
    },
};
