import { get, postJson } from "../../modules/http.js";
import { facturationMixin } from "./facturation-common.js";

new Vue({
    el: "#App",
    mixins: [facturationMixin],
    data() {
        return {
            fournisseurs: [],
            produits: [],
            bons: [],
            form: {
                tiers_id: null,
                devise: "CDF",
                date_commande: new Date().toISOString().slice(0, 10),
                lignes: [{ libelle: "", quantite: 1, prix_unitaire: 0, produit_id: null }],
            },
        };
    },
    async mounted() {
        await this.bootPage(async () => {
            const { data } = await get("/accounting/facturation/stock/metadata");
            if (data.status === "success") {
                this.fournisseurs = data.fournisseurs || [];
                this.produits = data.produits || [];
            }
            await this.loadBons();
        });
    },
    methods: {
        async loadBons() {
            const { data } = await get("/accounting/facturation/stock/bons-commande/list");
            if (data.status === "success") this.bons = data.bons || [];
        },
        prixProduit(p, devise) {
            return devise === "USD" ? Number(p.prix_unitaire_usd) || 0 : Number(p.prix_unitaire_cdf ?? p.prix_unitaire) || 0;
        },
        appliquerProduit(i) {
            const l = this.form.lignes[i];
            const p = this.produits.find((x) => x.id === l.produit_id);
            if (!p) return;
            l.libelle = p.libelle;
            l.prix_unitaire = this.prixProduit(p, this.form.devise);
        },
        onDeviseChange() {
            this.form.lignes.forEach((l, i) => {
                if (l.produit_id) this.appliquerProduit(i);
            });
        },
        async save() {
            const { data } = await postJson("/accounting/facturation/stock/bons-commande/save", this.form);
            if (this.handleResponse(data)) {
                this.form = {
                    tiers_id: null,
                    devise: "CDF",
                    date_commande: new Date().toISOString().slice(0, 10),
                    lignes: [{ libelle: "", quantite: 1, prix_unitaire: 0, produit_id: null }],
                };
                await this.loadBons();
            }
        },
    },
});
