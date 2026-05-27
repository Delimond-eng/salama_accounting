import { get } from "../../modules/http.js";
import { facturationMixin } from "./facturation-common.js";

new Vue({
    el: "#App",
    mixins: [facturationMixin],
    data() {
        return {
            produitId: window.__PRODUIT_ID__,
            produit: null,
            mouvements: [],
            stats: {},
            error: null,
        };
    },
    async mounted() {
        await this.bootPage(async () => {
            await this.loadFiche();
        });
    },
    methods: {
        async loadFiche() {
            this.isLoading = true;
            this.error = null;
            try {
                const { data } = await get(`/accounting/facturation/stock/fiche/${this.produitId}/data`);
                if (data.status === "success") {
                    this.produit = data.produit;
                    this.mouvements = data.mouvements || [];
                    this.stats = data.stats || {};
                } else if (data.errors) {
                    this.error = data.errors[0];
                }
            } catch {
                this.error = "Impossible de charger la fiche.";
            } finally {
                this.isLoading = false;
            }
        },
        typeBadge(t) {
            return {
                entree: "badge-soft-success",
                sortie: "badge-soft-danger",
                ajustement: "badge-soft-info",
                inventaire: "badge-soft-primary",
            }[t] || "badge-soft-secondary";
        },
        fmtDate(d) {
            if (!d) return "—";
            const s = String(d).slice(0, 10);
            const [y, m, j] = s.split("-");
            return y && m && j ? `${j}/${m}/${y}` : s;
        },
    },
});
