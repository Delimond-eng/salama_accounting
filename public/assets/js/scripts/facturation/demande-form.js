import { get, postJson } from "../../modules/http.js";
import { compteSelectMixin } from "../../modules/compte-select-mixin.js";
import { facturationMixin } from "./facturation-common.js";

new Vue({
    el: "#App",
    mixins: [facturationMixin, compteSelectMixin],
    data() {
        return {
            demandeId: window.__DEMANDE_ID__ || null,
            demande: null,
            form: { montant: 0, devise: "CDF", motif: "" },
            traitement: {
                compte_debit: "",
                compte_credit: "",
                compte_tresorerie: "",
                methode: "caisse",
                commentaire: "",
            },
            comptesTreso: [],
        };
    },
    computed: {
        peutTraiter() {
            return this.demande && ["en_validation", "approuvee"].includes(this.demande.statut);
        },
    },
    async mounted() {
        await this.bootPage(async () => {
            await this.loadMeta();
            if (this.demandeId) await this.loadDemande();
        });
    },
    methods: {
        async loadDemande() {
            const { data } = await get(`/accounting/facturation/demandes/${this.demandeId}/detail`);
            if (data.status !== "success") return;
            this.demande = data.demande;
            this.traitement.compte_debit = data.demande.compte_debit || "";
            this.traitement.compte_credit = data.demande.compte_credit || "";
            await this.prefetchCompteLabel(this.traitement.compte_debit);
            await this.prefetchCompteLabel(this.traitement.compte_credit);
            if (this.demande.etape_courante?.execution_paiement) {
                await this.loadComptesTreso();
            }
        },
        async loadComptesTreso() {
            const { data } = await get(
                `/accounting/facturation/comptes-tresorerie?type=${this.traitement.methode}`
            );
            if (data.status === "success") {
                this.comptesTreso = data.comptes || [];
                const defaut = this.meta?.comptes_tresorerie_defaut?.[this.traitement.methode];
                if (defaut && this.comptesTreso.some((c) => c.num_compte === defaut)) {
                    this.traitement.compte_tresorerie = defaut;
                } else if (this.comptesTreso.length === 1) {
                    this.traitement.compte_tresorerie = this.comptesTreso[0].num_compte;
                }
            }
        },
        async creer() {
            const { data } = await postJson("/accounting/facturation/demandes/save", this.form);
            if (this.handleResponse(data) && data.demande?.id) {
                window.location.href = `/accounting/facturation/demandes/${data.demande.id}`;
            }
        },
        async traiter(decision) {
            const payload = { decision, commentaire: this.traitement.commentaire };
            if (this.demande?.etape_courante?.imputation_comptable) {
                payload.compte_debit = this.traitement.compte_debit;
                payload.compte_credit = this.traitement.compte_credit;
            }
            if (this.demande?.etape_courante?.execution_paiement) {
                payload.executer_paiement = true;
                payload.methode = this.traitement.methode;
                payload.compte_tresorerie = this.traitement.compte_tresorerie;
            }
            const { data } = await postJson(`/accounting/facturation/demandes/${this.demandeId}/traiter`, payload);
            if (this.handleResponse(data)) await this.loadDemande();
        },
    },
});
