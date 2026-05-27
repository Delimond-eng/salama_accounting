import { get, postJson } from "../../modules/http.js";
import { facturationMixin } from "./facturation-common.js";

let timer = null;

new Vue({
    el: "#App",
    mixins: [facturationMixin],
    data() {
        return {
            page: window.__FACTURATION_PAGE__ || "clients",
            typeDocument: window.__FACTURATION_TYPE__ || "vente_client",
            factures: [],
            search: "",
            filtreStatut: "",
            filtreDevise: "",
            comptesTreso: [],
            paiementForm: {
                facture: null,
                methode: "banque",
                compte_tresorerie: "",
                montant: 0,
                devise: "CDF",
                date_paiement: new Date().toISOString().slice(0, 10),
                notes: "",
            },
        };
    },
    computed: {
        createUrl() {
            if (this.typeDocument.includes("avoir")) {
                const segment = this.page.includes("fournisseur") ? "avoirs-fournisseurs" : "avoirs-clients";
                return `/accounting/facturation/${segment}/nouvelle`;
            }
            const base = this.page.includes("fournisseur") ? "fournisseurs" : "clients";
            return `/accounting/facturation/${base}/nouvelle`;
        },
    },
    async mounted() {
        await this.bootPage(async () => {
            await this.loadMeta();
            await this.loadList();
        });
    },
    methods: {
        debounceLoad() {
            clearTimeout(timer);
            timer = setTimeout(() => this.loadList(), 350);
        },
        async loadList() {
            this.isLoading = true;
            const p = new URLSearchParams({ type_document: this.typeDocument });
            if (this.search) p.set("search", this.search);
            if (this.filtreStatut) p.set("statut", this.filtreStatut);
            if (this.filtreDevise) p.set("devise", this.filtreDevise);
            try {
                const { data } = await get(`/accounting/facturation/factures?${p}`);
                if (data.status === "success") this.factures = data.factures || [];
            } finally {
                this.isLoading = false;
            }
        },
        editUrl(id) {
            if (this.typeDocument.includes("avoir")) {
                const segment = this.page.includes("fournisseur") ? "avoirs-fournisseurs" : "avoirs-clients";
                return `/accounting/facturation/${segment}/${id}`;
            }
            const base = this.page.includes("fournisseur") || this.typeDocument.includes("achat")
                ? "fournisseurs"
                : "clients";
            return `/accounting/facturation/${base}/${id}`;
        },
        async valider(f) {
            if (!confirm(`Valider la facture ${f.numero} ? Une écriture comptable sera générée.`)) return;
            const { data } = await postJson(`/accounting/facturation/factures/${f.id}/valider`, {});
            if (this.handleResponse(data)) this.loadList();
        },
        async loadComptesTreso() {
            const { data } = await get(
                `/accounting/facturation/comptes-tresorerie?type=${this.paiementForm.methode}`
            );
            if (data.status === "success") {
                this.comptesTreso = data.comptes || [];
                const defaut = this.meta?.comptes_tresorerie_defaut?.[this.paiementForm.methode];
                const existe = this.comptesTreso.some((c) => c.num_compte === defaut);
                if (existe) {
                    this.paiementForm.compte_tresorerie = defaut;
                } else if (this.comptesTreso.length === 1) {
                    this.paiementForm.compte_tresorerie = this.comptesTreso[0].num_compte;
                } else if (!this.comptesTreso.some((c) => c.num_compte === this.paiementForm.compte_tresorerie)) {
                    this.paiementForm.compte_tresorerie = defaut || (this.comptesTreso[0]?.num_compte ?? "");
                }
            }
        },
        ouvrirModalPaiement() {
            const el = document.getElementById("modal_paiement");
            if (!el) return;
            if (window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(el).show();
                return;
            }
            if (window.jQuery?.fn?.modal) {
                window.jQuery(el).modal("show");
            }
        },
        async preparePaiement(f) {
            this.error = null;
            this.paiementForm = {
                facture: f,
                methode: "banque",
                compte_tresorerie: "",
                montant: Number(f.montant_ttc) || 0,
                devise: f.devise || "CDF",
                date_paiement: new Date().toISOString().slice(0, 10),
                notes: "",
            };
            await this.loadComptesTreso();
            await this.$nextTick();
            this.ouvrirModalPaiement();
        },
        fermerModalPaiement() {
            const el = document.getElementById("modal_paiement");
            if (!el) return;
            if (window.bootstrap?.Modal) {
                const inst = window.bootstrap.Modal.getInstance(el) || new window.bootstrap.Modal(el);
                inst.hide();
                return;
            }
            if (window.jQuery) {
                window.jQuery(el).modal("hide");
            }
        },
        async confirmerPaiement() {
            const f = this.paiementForm.facture;
            if (!f?.id || !this.paiementForm.compte_tresorerie) {
                this.error = ["Sélectionnez un compte de trésorerie."];
                return;
            }
            this.isLoading = true;
            try {
                const { data } = await postJson(`/accounting/facturation/paiements/facture/${f.id}`, {
                    methode: this.paiementForm.methode,
                    compte_tresorerie: this.paiementForm.compte_tresorerie,
                    montant: this.paiementForm.montant,
                    date_paiement: this.paiementForm.date_paiement,
                    notes: this.paiementForm.notes || null,
                });
                if (this.handleResponse(data)) {
                    this.fermerModalPaiement();
                    if (data.paiement?.id) {
                        window.open(`/accounting/facturation/paiements/${data.paiement.id}/pdf`, "_blank");
                    }
                    await this.loadList();
                }
            } finally {
                this.isLoading = false;
            }
        },
    },
});
