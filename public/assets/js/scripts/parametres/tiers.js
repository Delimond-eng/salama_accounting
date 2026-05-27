import { get, postJson } from "../../modules/http.js";
import { compteSelectMixin } from "../../modules/compte-select-mixin.js";
import { parametresMixin } from "./parametres-common.js";

let searchTimer = null;

new Vue({
    el: "#App",
    mixins: [parametresMixin, compteSelectMixin],
    data() {
        return {
            liste: [],
            search: "",
            filtreType: "",
            form: this.emptyForm(),
            exportBase: "/accounting/export/parametres/tiers",
        };
    },

    methods: {
        queryParams() {
            const p = new URLSearchParams();
            if (this.search) {
                p.set("search", this.search);
            }
            if (this.filtreType) {
                p.set("type", this.filtreType);
            }
            return p.toString();
        },

        async initPage() {
            await this.loadData();
        },

        async onSocieteChanged() {
            await this.loadData();
        },

        labelType(type) {
            const map = {
                client: "Client",
                fournisseur: "Fournisseur",
                client_fournisseur: "Client / Fournisseur",
                salarie: "Salarié",
                banque: "Banque",
                autre: "Autre",
            };
            return map[type] || type;
        },

        emptyForm() {
            return {
                id: null,
                code: "",
                nom: "",
                type: "client",
                num_compte_collectif: "",
                email: "",
                telephone: "",
                ville: "",
                actif: true,
            };
        },

        debounceSearch() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => this.loadData(), 350);
        },

        async loadData() {
            this.isLoading = true;
            const params = new URLSearchParams();
            if (this.search) params.set("search", this.search);
            if (this.filtreType) params.set("type", this.filtreType);
            try {
                const { data } = await get(`/accounting/parametres/tiers/all?${params}`);
                if (data.status === "success") {
                    this.liste = data.tiers || [];
                } else if (data.errors) {
                    this.error = data.errors;
                }
            } catch (e) {
                console.error(e);
                this.error = ["Impossible de charger les tiers. Vérifiez qu'une société est active (Paramètres > Société)."];
                this.liste = [];
            } finally {
                this.isLoading = false;
            }
        },

        openForm() {
            this.form = this.emptyForm();
            new bootstrap.Modal(document.getElementById("modal_tiers")).show();
        },

        async editTiers(t) {
            this.form = { ...t };
            if (t.num_compte_collectif) {
                await this.prefetchCompteLabel(t.num_compte_collectif);
            }
            new bootstrap.Modal(document.getElementById("modal_tiers")).show();
        },

        async saveTiers() {
            this.isLoading = true;
            const { data } = await postJson("/accounting/parametres/tiers/save", this.form);
            this.isLoading = false;
            if (!this.handleResponse(data)) return;
            bootstrap.Modal.getInstance(document.getElementById("modal_tiers"))?.hide();
            this.loadData();
        },
    },
});
