import { get, postJson } from "../../modules/http.js";
import { facturationMixin } from "./facturation-common.js";

new Vue({
    el: "#App",
    mixins: [facturationMixin],
    data() {
        return {
            tiers: [],
            produits: [],
            form: {
                id: window.__FACTURE_ID__ || null,
                type_document: window.__TYPE_DOCUMENT__ || "vente_client",
                tiers_id: null,
                date_facture: new Date().toISOString().slice(0, 10),
                date_echeance: "",
                objet: "",
                notes: "",
                tva_active: false,
                taux_tva: 16,
                devise: "CDF",
                lignes: [
                    { est_rubrique: true, rubrique: "", libelle: "", quantite: 0, prix_unitaire: 0, produit_id: null },
                    { est_rubrique: false, rubrique: "", libelle: "", quantite: 1, prix_unitaire: 0, produit_id: null },
                ],
            },
            totaux: { ht: 0, tva: 0, ttc: 0 },
            statut: "brouillon",
        };
    },
    computed: {
        lectureSeule() {
            return this.statut && this.statut !== "brouillon";
        },
        devisesFacture() {
            const list = this.meta?.devises || ["CDF", "USD"];
            return Array.isArray(list) ? list : ["CDF", "USD"];
        },
    },
    async mounted() {
        await this.bootPage(async () => {
            await this.loadMeta();
            if (this.meta?.taux_tva_defaut) this.form.taux_tva = this.meta.taux_tva_defaut;
            await Promise.all([this.loadTiers(), this.loadProduits()]);
            if (this.form.id) await this.loadFacture();
            this.recalc();
        });
    },
    methods: {
        async loadTiers() {
            const cible = this.form.type_document.includes("achat") || this.form.type_document.includes("fournisseur") ? "fournisseur" : "client";
            const { data } = await get(`/accounting/facturation/tiers?cible=${cible}`);
            if (data.status === "success") this.tiers = data.tiers || [];
        },
        async loadProduits() {
            const { data } = await get("/accounting/facturation/produits/list");
            if (data.status === "success") this.produits = data.produits || [];
        },
        async loadFacture() {
            const { data } = await get(`/accounting/facturation/factures/${this.form.id}`);
            if (data.status !== "success") return;
            const f = data.facture;
            this.statut = f.statut || "brouillon";
            this.form = {
                id: f.id,
                type_document: f.type_document,
                tiers_id: f.tiers_id,
                date_facture: String(f.date_facture).slice(0, 10),
                date_echeance: f.date_echeance ? String(f.date_echeance).slice(0, 10) : "",
                objet: f.objet || "",
                notes: f.notes || "",
                tva_active: !!f.tva_active,
                taux_tva: Number(f.taux_tva) || 16,
                devise: f.devise || "CDF",
                lignes: (f.lignes || []).map((l) => {
                    const estRubrique = !!l.est_rubrique;
                    return {
                        est_rubrique: estRubrique,
                        rubrique: estRubrique ? (l.rubrique || l.libelle || "") : "",
                        libelle: estRubrique ? "" : l.libelle,
                        quantite: estRubrique ? 0 : Number(l.quantite),
                        prix_unitaire: estRubrique ? 0 : Number(l.prix_unitaire),
                        compte_comptable: l.compte_comptable,
                        produit_id: l.produit_id,
                    };
                }),
            };
            if (!this.form.lignes.length) {
                this.addRubrique();
                this.addLigne();
            }
        },
        prixProduit(p, devise) {
            if (!p) return 0;
            return devise === "USD" ? Number(p.prix_unitaire_usd) || 0 : Number(p.prix_unitaire_cdf ?? p.prix_unitaire) || 0;
        },
        appliquerProduit(index) {
            const l = this.form.lignes[index];
            if (l.est_rubrique) return;
            const p = this.produits.find((x) => x.id === l.produit_id);
            if (!p) return;
            l.libelle = p.libelle;
            l.prix_unitaire = this.prixProduit(p, this.form.devise);
            this.recalc();
        },
        onDeviseChange() {
            this.form.lignes.forEach((l, i) => {
                if (!l.est_rubrique && l.produit_id) this.appliquerProduit(i);
            });
            this.recalc();
        },
        ligneHt(l) {
            if (l.est_rubrique) return 0;
            return (Number(l.quantite) || 0) * (Number(l.prix_unitaire) || 0);
        },
        numeroLigneArticle(index) {
            let n = 0;
            for (let i = 0; i <= index; i++) {
                if (!this.form.lignes[i]?.est_rubrique) n++;
            }
            return n;
        },
        recalc() {
            let ht = 0;
            this.form.lignes.forEach((l) => {
                if (!l.est_rubrique) ht += this.ligneHt(l);
            });
            ht = Math.round(ht * 100) / 100;
            const tva = this.form.tva_active ? Math.round(ht * this.form.taux_tva) / 100 : 0;
            this.totaux = { ht, tva, ttc: Math.round((ht + tva) * 100) / 100 };
        },
        addRubrique() {
            this.form.lignes.push({
                est_rubrique: true,
                rubrique: "",
                libelle: "",
                quantite: 0,
                prix_unitaire: 0,
                produit_id: null,
            });
        },
        addLigne() {
            this.form.lignes.push({
                est_rubrique: false,
                rubrique: "",
                libelle: "",
                quantite: 1,
                prix_unitaire: 0,
                produit_id: null,
            });
        },
        lignesPourSave() {
            return this.form.lignes
                .map((l) => {
                    if (l.est_rubrique) {
                        const titre = (l.rubrique || "").trim();
                        if (!titre) return null;
                        return {
                            est_rubrique: true,
                            rubrique: titre,
                            libelle: titre,
                            quantite: 0,
                            prix_unitaire: 0,
                        };
                    }
                    const libelle = (l.libelle || "").trim();
                    if (!libelle) return null;
                    return {
                        est_rubrique: false,
                        libelle,
                        quantite: l.quantite,
                        prix_unitaire: l.prix_unitaire,
                        compte_comptable: l.compte_comptable,
                        produit_id: l.produit_id,
                    };
                })
                .filter(Boolean);
        },
        async save(etValider) {
            const lignes = this.lignesPourSave();
            const articles = lignes.filter((l) => !l.est_rubrique);
            if (!articles.length) {
                this.error = ["Ajoutez au moins un article (ligne avec libellé et montant)."];
                return;
            }
            this.isLoading = true;
            try {
                const payload = { ...this.form, lignes };
                const { data } = await postJson("/accounting/facturation/factures/save", payload);
                if (!this.handleResponse(data)) return;
                if (etValider && data.facture?.id) {
                    const v = await postJson(`/accounting/facturation/factures/${data.facture.id}/valider`, {});
                    this.handleResponse(v);
                }
                if (data.facture?.id) {
                    const base = this.form.type_document.includes("achat") ? "fournisseurs" : "clients";
                    window.location.href = `/accounting/facturation/${base}`;
                }
            } finally {
                this.isLoading = false;
            }
        },
    },
});
