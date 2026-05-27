import { get, postJson } from "../../modules/http.js";
import { facturationMixin } from "./facturation-common.js";

new Vue({
    el: "#App",
    mixins: [facturationMixin],
    data() {
        return {
            produits: [],
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
            await this.loadInventaire();
        });
    },
    methods: {
        async loadInventaire() {
            this.isLoading = true;
            try {
                const { data } = await get("/accounting/facturation/stock/inventaire");
                if (data.status === "success") {
                    this.produits = data.produits || [];
                    this.produitsStock = this.produits.filter((p) => p.gestion_stock);
                }
            } finally {
                this.isLoading = false;
            }
        },
        alerteStock(p) {
            return p.gestion_stock && Number(p.stock_actuel) <= Number(p.stock_minimum);
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
                    await this.loadInventaire();
                }
            } finally {
                this.isLoading = false;
            }
        },
        pdfMouvement(id) {
            window.open(`/accounting/facturation/stock/mouvements/${id}/pdf`, "_blank");
        },
    },
});
