import { get, postJson } from "../../modules/http.js";
import { facturationMixin } from "./facturation-common.js";

const emptyForm = () => ({
    id: null,
    code: "",
    libelle: "",
    type_article: "produit",
    unite: "U",
    prix_unitaire_cdf: 0,
    prix_unitaire_usd: 0,
    gestion_stock: false,
    stock_actuel: 0,
    stock_minimum: 0,
});

new Vue({
    el: "#App",
    mixins: [facturationMixin],
    data() {
        return {
            produits: [],
            form: emptyForm(),
        };
    },
    async mounted() {
        await this.bootPage(async () => {
            await this.loadProduits();
        });
    },
    methods: {
        async loadProduits() {
            const { data } = await get("/accounting/facturation/produits/list");
            if (data.status === "success") this.produits = data.produits || [];
        },
        edit(p) {
            this.form = {
                id: p.id,
                code: p.code || "",
                libelle: p.libelle,
                type_article: p.type_article || "produit",
                unite: p.unite || "U",
                prix_unitaire_cdf: Number(p.prix_unitaire_cdf ?? p.prix_unitaire) || 0,
                prix_unitaire_usd: Number(p.prix_unitaire_usd) || 0,
                gestion_stock: !!p.gestion_stock,
                stock_actuel: Number(p.stock_actuel) || 0,
                stock_minimum: Number(p.stock_minimum) || 0,
            };
        },
        resetForm() {
            this.form = emptyForm();
        },
        async save() {
            const { data } = await postJson("/accounting/facturation/produits/save", this.form);
            if (this.handleResponse(data)) {
                this.resetForm();
                await this.loadProduits();
            }
        },
    },
});
