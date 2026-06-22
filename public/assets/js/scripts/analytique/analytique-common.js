import { get } from "../../modules/http.js";
import { vuePageMixin } from "../../modules/vue-page-mixin.js";
import { exportMixin } from "../../modules/export-mixin.js";
import { deviseFiltreMixin } from "../../modules/devise-filtre-mixin.js";

export const analytiqueMixin = {
    mixins: [vuePageMixin, exportMixin, deviseFiltreMixin],

    data() {
        return {
            axes: [],
            journaux: [],
            exercice: null,
            options: { devises: [], modes_devise: [], devise_affichage: "CDF", mode_conversion: "origine", scope_devise: "consolide", mode_devise: "cdf_consolide" },
            filtres: {
                date_debut: "",
                date_fin: "",
                axe_id: null,
                section_id: null,
                journal_id: null,
                mode_devise: "cdf_consolide",
                devise_affichage: "CDF",
                scope_devise: "consolide",
                mode_conversion: "origine",
            },
            result: null,
            error: null,
            message: null,
            isLoading: false,
            dataUrl: "",
            exportBase: "", // Défini individuellement dans chaque page
        };
    },

    computed: {
        sectionsFiltre() {
            if (!this.filtres.axe_id) {
                return (this.axes || []).flatMap((a) => a.sections || []);
            }
            const axe = (this.axes || []).find((a) => a.id === this.filtres.axe_id);
            return axe?.sections || [];
        },
    },

    async mounted() {
        await this.bootPage(async () => {
            await this.loadMeta();
            if (this.dataUrl) await this.loadData();
        });
    },

    methods: {
        async loadMeta() {
            const { data } = await get("/accounting/analytique/metadata");
            if (data.status === "success") {
                this.axes = data.axes || [];
                this.journaux = data.journaux || [];
                this.exercice = data.exercice;
                this.options = data.options || this.options;
                this.filtres.date_debut = data.date_debut || this.filtres.date_debut;
                this.filtres.date_fin = data.date_fin || this.filtres.date_fin;
                this.filtres.mode_devise = this.options.mode_devise || "cdf_consolide";
                this.applyDeviseOptionsFromPayload({ options: this.options });
            }
        },

        queryParams() {
            const p = new URLSearchParams({
                date_debut: this.filtres.date_debut,
                date_fin: this.filtres.date_fin,
                mode_devise: this.queryParamModeDevise(),
            });
            if (this.filtres.axe_id) p.set("axe_id", this.filtres.axe_id);
            if (this.filtres.section_id) p.set("section_id", this.filtres.section_id);
            if (this.filtres.journal_id) p.set("journal_id", this.filtres.journal_id);
            return p.toString();
        },

        async loadData() {
            if (!this.dataUrl) return;
            this.isLoading = true;
            this.error = null;
            try {
                const { data } = await get(`${this.dataUrl}?${this.queryParams()}`);
                if (data.status === "success") {
                    this.result = data.data;
                } else {
                    this.error = data.errors?.[0] || "Erreur de chargement.";
                }
            } catch {
                this.error = "Impossible de charger les données.";
            } finally {
                this.isLoading = false;
            }
        },

        fmt(v) {
            const n = Number(v) || 0;
            const devise = this.result?.devise || this.deviseAffichageCourante || "CDF";
            return (
                new Intl.NumberFormat("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n) +
                " " +
                this.suffixDevise(devise)
            );
        },

        suffixDevise(code) {
            const c = (code || "CDF").toUpperCase();
            if (c === "CDF") return "Fr";
            if (c === "USD") return "USD";
            return c;
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
