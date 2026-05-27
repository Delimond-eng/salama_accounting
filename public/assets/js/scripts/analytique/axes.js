import { get, postJson } from "../../modules/http.js";
import { vuePageMixin } from "../../modules/vue-page-mixin.js";

new Vue({
    el: "#App",
    mixins: [vuePageMixin],
    data() {
        return {
            axes: [],
            axesRestreints: false,
            formAxe: { id: null, code: "", libelle: "", description: "", actif: true, ordre_affichage: 10 },
            formSection: { id: null, axe_analytique_id: null, code: "", libelle: "", budget: null, actif: true },
            error: null,
            message: null,
            isLoading: false,
        };
    },
    async mounted() {
        await this.bootPage(() => this.loadAxes());
    },
    methods: {
        fmt(v) {
            return new Intl.NumberFormat("fr-FR", { minimumFractionDigits: 2 }).format(v || 0);
        },
        async loadAxes() {
            const { data } = await get("/accounting/analytique/axes/all");
            if (data.status === "success") {
                this.axes = data.axes || [];
                this.axesRestreints = !!data.analytique_axes_restreints;
            }
        },
        openAxeForm() {
            this.formAxe = { id: null, code: "", libelle: "", description: "", actif: true, ordre_affichage: 10 };
            new bootstrap.Modal(document.getElementById("modal_axe")).show();
        },
        editAxe(a) {
            this.formAxe = { ...a };
            new bootstrap.Modal(document.getElementById("modal_axe")).show();
        },
        async saveAxe() {
            const { data } = await postJson("/accounting/analytique/axes/save", this.formAxe);
            if (data.errors) { this.error = data.errors[0]; return; }
            this.message = data.message;
            bootstrap.Modal.getInstance(document.getElementById("modal_axe"))?.hide();
            this.loadAxes();
        },
        openSectionForm(axe) {
            this.formSection = { id: null, axe_analytique_id: axe.id, code: "", libelle: "", budget: null, actif: true };
            new bootstrap.Modal(document.getElementById("modal_section")).show();
        },
        editSection(axe, s) {
            this.formSection = { ...s, axe_analytique_id: axe.id };
            new bootstrap.Modal(document.getElementById("modal_section")).show();
        },
        async saveSection() {
            const { data } = await postJson("/accounting/analytique/sections/save", this.formSection);
            if (data.errors) { this.error = data.errors[0]; return; }
            this.message = data.message;
            bootstrap.Modal.getInstance(document.getElementById("modal_section"))?.hide();
            this.loadAxes();
        },
        async saveConfig() {
            await postJson("/accounting/analytique/config/save", { analytique_axes_restreints: this.axesRestreints });
        },
    },
});
