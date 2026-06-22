import { get, postJson } from "../../modules/http.js";
import { vuePageMixin } from "../../modules/vue-page-mixin.js";
import { exportMixin } from "../../modules/export-mixin.js";
import { deviseFiltreMixin } from "../../modules/devise-filtre-mixin.js";

export const livresMixin = {
    mixins: [vuePageMixin, exportMixin, deviseFiltreMixin],

    data() {
        return {
            page: window.__LIVRES_PAGE__ || "balance",
            societe: null,
            exercice: null,
            options: { devises: [], modes_devise: [], devise_affichage: "CDF", mode_conversion: "origine", scope_devise: "consolide", mode_devise: "cdf_consolide" },
            journaux: [],
            filtres: {
                date_debut: "",
                date_fin: "",
                mode_devise: "cdf_consolide",
                devise_affichage: "CDF",
                scope_devise: "consolide",
                mode_conversion: "origine",
            },
            tauxUsd: 1,
            dateTaux: null,
            error: null,
            message: null,
            isLoading: false,
            journalId: null,
            exportBase: `/accounting/export/livres/${window.__LIVRES_PAGE__ || "balance"}`,
        };
    },

    computed: {
        pageTitle() {
            const titles = {
                journal: "Journal Général",
                "grand-livre": "Grand Livre",
                balance: "Balance de Vérification",
                auxiliaire: "Balance Auxiliaire",
                lettrage: "Lettrage des Comptes",
                banque: "Livre de Banque",
                caisse: "Livre de Caisse",
                "comptes-tiers": "Situation des Tiers"
            };
            return titles[this.page] || "Livres Comptables";
        },
        pageSubtitle() {
            let parts = [];
            if (this.filtres.date_debut && this.filtres.date_fin) {
                parts.push(`Période du ${this.fmtDate(this.filtres.date_debut)} au ${this.fmtDate(this.filtres.date_fin)}`);
            }
            parts.push(`Devise: ${this.deviseAffichageCourante}`);
            return parts.join(' • ');
        }
    },

    async mounted() {
        await this.bootPage(async () => {
            await this.loadMetadata();
            if (typeof this.initPage === "function") {
                await this.initPage();
            }
        });
    },

    methods: {
        async loadMetadata() {
            const { data } = await get("/accounting/livres/metadata");
            if (data.status !== "success") {
                return;
            }
            this.societe = data.societe;
            this.exercice = data.exercice;
            this.options = data.options || this.options;
            this.journaux = data.journaux || [];
            this.tauxUsd = data.taux_usd ?? this.tauxUsd;
            this.dateTaux = data.date_taux;
            this.filtres.date_debut = data.date_debut || this.filtres.date_debut;
            this.filtres.date_fin = data.date_fin || this.filtres.date_fin;
            this.filtres.mode_devise = this.options.mode_devise || "cdf_consolide";
            this.applyDeviseOptionsFromPayload({ options: this.options });
        },

        queryParams(extra = {}) {
            const p = new URLSearchParams({
                date_debut: this.filtres.date_debut,
                date_fin: this.filtres.date_fin,
                mode_devise: this.queryParamModeDevise(),
                taux: this.tauxUsd,
            });
            if (this.page === "journal" && this.journalId) {
                p.set("journal_id", this.journalId);
            }
            Object.entries(extra).forEach(([k, v]) => {
                if (v !== null && v !== undefined && v !== "") {
                    p.set(k, v);
                }
            });
            return p.toString();
        },

        async onDatesChange() {
            await this.loadData();
        },

        async onFiltreChange() {
            this.syncDeviseFromMode();
            await this.savePreferences();
            await this.loadData();
        },

        async loadData() {
            console.warn("loadData non implémenté");
        },

        async savePreferences() {
            const { data } = await postJson("/accounting/livres/preferences", {
                mode_devise: this.queryParamModeDevise(),
            });
            if (data.status === "success" && data.options) {
                this.options = data.options;
                this.applyDeviseOptionsFromPayload({ options: data.options });
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
            if (v === null || v === undefined || v === "") return "—";
            return new Intl.NumberFormat("fr-FR", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(v || 0);
        },

        fmtDate(d) {
            if (!d) return "";
            const parts = d.split('-');
            if (parts.length !== 3) return d;
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        },

        suffixDevise(code) {
            const c = (code || "CDF").toUpperCase();
            if (c === "CDF") return "Fr";
            if (c === "USD") return "USD";
            return c;
        },

        fmtMontantDevise(montant, devise) {
            const n = Number(montant) || 0;
            return `${this.fmt(n)} ${this.suffixDevise(devise)}`;
        },

        fmtDateTime(dt) {
            if (!dt) return "—";
            const s = String(dt).replace("T", " ");
            return s.length >= 19 ? s.slice(0, 19) : s;
        },

        journalBadgeClass(type, code) {
            const c = (code || "").toUpperCase();
            if (c === "BQ") return "bg-soft-primary text-primary";
            if (c === "CA") return "bg-soft-warning text-warning";
            if (c === "VT") return "bg-soft-success text-success";
            if (c === "HA") return "bg-soft-danger text-danger";
            return "bg-soft-secondary text-secondary";
        }
    },
};
