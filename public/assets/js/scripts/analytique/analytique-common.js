import { get } from "../../modules/http.js";
import { vuePageMixin } from "../../modules/vue-page-mixin.js";

export const analytiqueMixin = {
    mixins: [vuePageMixin],

    data() {
        return {
            axes: [],
            journaux: [],
            exercice: null,
            options: { devises: [], devise_affichage: "CDF", mode_conversion: "origine", scope_devise: "consolide" },
            filtres: {
                date_debut: "",
                date_fin: "",
                axe_id: null,
                section_id: null,
                journal_id: null,
                devise_affichage: "CDF",
                scope_devise: "consolide",
                mode_conversion: "origine",
            },
            result: null,
            error: null,
            isLoading: false,
            dataUrl: "",
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
                this.filtres.devise_affichage = this.options.devise_affichage || "CDF";
                this.filtres.scope_devise = this.options.scope_devise || "consolide";
                this.filtres.mode_conversion = this.options.mode_conversion || "origine";
            }
        },

        queryParams() {
            const p = new URLSearchParams();
            Object.entries(this.filtres).forEach(([k, v]) => {
                if (v !== null && v !== undefined && v !== "") p.set(k, v);
            });
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
            const devise = this.result?.devise || this.filtres.devise_affichage || "CDF";
            return (
                new Intl.NumberFormat("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v || 0) +
                " " +
                devise
            );
        },
    },
};
