import { get, postJson } from "../../modules/http.js";
import { facturationMixin } from "./facturation-common.js";

new Vue({
    el: "#App",
    mixins: [facturationMixin],
    data() {
        return {
            mouvements: [],
            produitsStock: [],
            mvt: {
                produit_id: null,
                type_mouvement: "entree",
                quantite: 1,
                libelle: "",
                date_mouvement: new Date().toISOString().slice(0, 10),
            },
        };
    },
    async mounted() {
        await this.bootPage(async () => {
            await Promise.all([this.loadMouvements(), this.loadProduits()]);
        });
    },
    methods: {
        async loadProduits() {
            const { data } = await get("/accounting/facturation/stock/metadata");
            if (data.status === "success") {
                this.produitsStock = data.produits || [];
            }
        },
        async loadMouvements() {
            this.isLoading = true;
            try {
                const { data } = await get("/accounting/facturation/stock/mouvements/list");
                if (data.status === "success") this.mouvements = data.mouvements || [];
            } finally {
                this.isLoading = false;
            }
        },
        badgeType(t) {
            return {
                entree: "badge-soft-success",
                sortie: "badge-soft-danger",
                ajustement: "badge-soft-warning",
                inventaire: "badge-soft-info",
            }[t] || "badge-soft-secondary";
        },
        async enregistrerMouvement() {
            if (!this.mvt.produit_id) {
                this.error = ["Sélectionnez un produit avec gestion de stock activée."];
                return;
            }
            if (!String(this.mvt.libelle || "").trim()) {
                this.error = ["Le libellé du mouvement est obligatoire."];
                return;
            }
            if (!this.mvt.quantite || Number(this.mvt.quantite) <= 0) {
                this.error = ["La quantité doit être supérieure à zéro."];
                return;
            }
            this.isLoading = true;
            this.error = null;
            try {
                const { data } = await postJson("/accounting/facturation/stock/mouvement", this.mvt);
                if (this.handleResponse(data)) {
                    if (data.pdf_url) {
                        window.open(data.pdf_url, "_blank");
                    }
                    this.mvt = {
                        produit_id: null,
                        type_mouvement: "entree",
                        quantite: 1,
                        libelle: "",
                        date_mouvement: new Date().toISOString().slice(0, 10),
                    };
                    await this.loadMouvements();
                }
            } finally {
                this.isLoading = false;
            }
        },
    },
});
