import { get, postJson } from "../../modules/http.js";
import { parametresMixin } from "./parametres-common.js";

new Vue({
    el: "#App",
    mixins: [parametresMixin],
    data() {
        return {
            devises: [],
            taux: [],
            devisePrincipale: "CDF",
            formTaux: {
                devise_code: "USD",
                date_taux: new Date().toISOString().slice(0, 10),
                taux: null,
                taux_achat: null,
                taux_vente: null,
            },
            exportBase: "/accounting/export/parametres/devises",
        };
    },

    computed: {
        devisesEtrangeres() {
            const ref = (this.devisePrincipale || "CDF").toUpperCase();
            return (this.devises || []).filter((d) => d.code_iso !== ref);
        },

        formEquivLabel() {
            return this.equivLabel(this.formTaux.devise_code, this.formTaux.taux);
        },

        formEquivAchat() {
            if (!this.formTaux.taux_achat) return "";
            return this.equivLabel(this.formTaux.devise_code, this.formTaux.taux_achat, "achat");
        },

        formEquivVente() {
            if (!this.formTaux.taux_vente) return "";
            return this.equivLabel(this.formTaux.devise_code, this.formTaux.taux_vente, "vente");
        },
    },

    methods: {
        async initPage() {
            await this.loadData();
        },

        async onSocieteChanged() {
            await this.loadData();
        },

        fmtMontant(n) {
            const v = Number(n);
            if (!Number.isFinite(v)) return "—";
            return v.toLocaleString("fr-FR", { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        },

        fmtDate(d) {
            if (!d) return "—";
            const s = String(d).slice(0, 10);
            const [y, m, j] = s.split("-");
            return y && m && j ? `${j}/${m}/${y}` : s;
        },

        /**
         * 1 unité de devise étrangère = X unités de la devise principale (ex. 1 USD = 2 200 CDF).
         */
        equivLabel(deviseCode, taux, kind = "moyen") {
            const code = (deviseCode || "").toUpperCase();
            const ref = (this.devisePrincipale || "CDF").toUpperCase();
            const v = Number(taux);
            if (!code || code === ref || !Number.isFinite(v) || v <= 0) {
                return "";
            }
            const sym = this.symboleDevise(ref);
            const prefix = kind === "achat" ? "Achat : " : kind === "vente" ? "Vente : " : "";
            return `${prefix}1 ${code} = ${this.fmtMontant(v)} ${ref}${sym ? ` (${sym})` : ""}`;
        },

        symboleDevise(code) {
            const d = (this.devises || []).find((x) => x.code_iso === code);
            return d?.symbole || "";
        },

        async loadData() {
            this.isLoading = true;
            try {
                const { data } = await get("/accounting/parametres/devises/all");
                if (data.status === "success") {
                    this.devises = data.devises || [];
                    this.taux = data.taux || [];
                    if (data.devise_principale) {
                        this.devisePrincipale = data.devise_principale;
                    } else if (this.societe?.devise_principale) {
                        this.devisePrincipale = this.societe.devise_principale;
                    }
                    const first = this.devisesEtrangeres[0];
                    if (first && !this.devisesEtrangeres.some((d) => d.code_iso === this.formTaux.devise_code)) {
                        this.formTaux.devise_code = first.code_iso;
                    }
                }
            } finally {
                this.isLoading = false;
            }
        },

        openTauxForm() {
            const first = this.devisesEtrangeres[0];
            this.formTaux = {
                devise_code: first?.code_iso || "USD",
                date_taux: new Date().toISOString().slice(0, 10),
                taux: null,
                taux_achat: null,
                taux_vente: null,
            };
            new bootstrap.Modal(document.getElementById("modal_taux")).show();
        },

        async saveTaux() {
            if (!this.formTaux.taux || this.formTaux.taux <= 0) {
                this.error = ["Indiquez combien vaut 1 unité de la devise en " + this.devisePrincipale + " (ex. 2200 pour 1 USD)."];
                return;
            }
            this.isLoading = true;
            const { data } = await postJson("/accounting/parametres/taux-change/save", this.formTaux);
            this.isLoading = false;
            if (!this.handleResponse(data)) return;
            bootstrap.Modal.getInstance(document.getElementById("modal_taux"))?.hide();
            this.loadData();
        },
    },
});
