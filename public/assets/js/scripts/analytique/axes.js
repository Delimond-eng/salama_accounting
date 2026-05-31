import {get, postJson } from "../../modules/http.js";
import { vuePageMixin } from "../../modules/vue-page-mixin.js";

new Vue({
    el: "#App",
    mixins: [vuePageMixin],
    data() {
        return {
            axes: [],
            axesRestreints: false,
            exercice: null, // Requis par le nav
            exportBase: "analytique_axes", // Requis par les boutons d'export
            formAxe: { id: null, code: "", libelle: "", description: "", actif: true, ordre_affichage: 10 },
            formSection: { id: null, axe_analytique_id: null, code: "", libelle: "", budget: null, actif: true },
            error: null,
            message: null,
            isLoading: false,
            isSaving: false,
        };
    },
    computed: {
        errorList() {
            if (!this.error) return [];
            if (Array.isArray(this.error)) return this.error;
            if (typeof this.error === "object") return Object.values(this.error).flat();
            return [String(this.error)];
        }
    },
    async mounted() {
        await this.bootPage(() => this.loadAxes());
    },
    methods: {
        fmt(v) {
            return new Intl.NumberFormat("fr-FR", { minimumFractionDigits: 2 }).format(v || 0);
        },

        async onSocieteChanged() {
            await this.loadAxes();
        },

        // Requis par le composant export-buttons
        exportUrl(format) {
            return `/accounting/analytique/axes/export?format=${format}`;
        },

        // Alias requis par le bouton de rafraîchissement du nav
        async loadData() {
            await this.loadAxes();
        },

        async loadAxes() {
            this.isLoading = true;
            try {
                // Le timestamp ?t= force le navigateur à ignorer le cache
                const { data } = await get("/accounting/analytique/axes/all?t=" + Date.now());
                if (data.status === "success") {
                    this.axes = data.axes || [];
                    this.axesRestreints = data.analytique_axes_restreints;
                    if (data.exercice) this.exercice = data.exercice;
                }
            } finally {
                this.isLoading = false;
            }
        },

        getModal(id) {
            const el = document.getElementById(id);
            return bootstrap.Modal.getOrCreateInstance(el);
        },

        openAxeForm() {
            this.error = null;
            this.message = null;
            this.formAxe = { id: null, code: "", libelle: "", description: "", actif: true, ordre_affichage: 10 };
            this.getModal("modal_axe").show();
        },

        editAxe(a) {
            this.error = null;
            this.message = null;
            this.formAxe = {...a };
            this.getModal("modal_axe").show();
        },

        async saveAxe() {
            if (this.isSaving) return;
            this.isSaving = true;
            this.error = null;
            try {
                const { data } = await postJson("/accounting/analytique/axes/save", this.formAxe);
                if (data.errors) {
                    this.error = data.errors;
                    return;
                }
                // Rafraîchir les données
                await this.loadAxes();
                // Fermer le modal
                this.getModal("modal_axe").hide();
                this.message = data.message;
            } catch (e) {
                this.error = ["Erreur lors de l'enregistrement de l'axe."];
            } finally {
                this.isSaving = false;
            }
        },

        openSectionForm(axe) {
            this.error = null;
            this.message = null;
            this.formSection = { id: null, axe_analytique_id: axe.id, code: "", libelle: "", budget: null, actif: true };
            this.getModal("modal_section").show();
        },

        editSection(axe, s) {
            this.error = null;
            this.message = null;
            this.formSection = {...s, axe_analytique_id: axe.id };
            this.getModal("modal_section").show();
        },

        async saveSection() {
            if (this.isSaving) return;
            this.isSaving = true;
            this.error = null;
            try {
                const { data } = await postJson("/accounting/analytique/sections/save", this.formSection);
                if (data.errors) {
                    this.error = data.errors;
                    return;
                }
                // Rafraîchir les données
                await this.loadAxes();
                // Fermer le modal
                this.getModal("modal_section").hide();
                this.message = data.message;
            } catch (e) {
                this.error = ["Erreur lors de l'enregistrement du compte analytique."];
            } finally {
                this.isSaving = false;
            }
        },

        async saveConfig() {
            this.isLoading = true;
            try {
                await postJson("/accounting/analytique/config/save", { analytique_axes_restreints: this.axesRestreints });
                await this.loadAxes();
            } finally {
                this.isLoading = false;
            }
        },
    },
});
