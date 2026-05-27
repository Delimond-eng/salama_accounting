import { get } from "../../modules/http.js";
import { vuePageMixin } from "../../modules/vue-page-mixin.js";
import { exportMixin } from "../../modules/export-mixin.js";

export const saisieMixin = {
    mixins: [vuePageMixin, exportMixin],

    data() {
        const now = new Date();
        const year = now.getFullYear();
        const today = now.toLocaleDateString('en-CA'); // YYYY-MM-DD format local
        const startOfYear = `${year}-01-01`;

        return {
            page: window.__SAISIE_PAGE__ || "nouvelle",
            exercice: null,
            journal: null,
            journaux: [],
            error: null,
            message: null,
            warnings: [],
            isLoading: false,
            total_count: 0,
            filtres: {
                date_debut: startOfYear,
                date_fin: today,
                devise_affichage: "CDF",
                mode_conversion: "origine",
                taux: 1,
            },
            options: { devises: [] },
            tauxUsd: 1,
            exportBase: "/accounting/export/saisie",
        };
    },

    computed: {
        pageTitle() {
            const titles = {
                nouvelle: "Toutes les écritures",
                achats: "Journal des Achats",
                ventes: "Journal des Ventes",
                banque: "Journal de Banque",
                caisse: "Journal de Caisse",
                od: "Opérations Diverses",
                devises: "Écritures en Devises",
                import: "Import de Relevés"
            };
            return titles[this.page] || "Écritures comptables";
        },
        pageSubtitle() {
            let parts = [];
            if (this.filtres.date_debut && this.filtres.date_fin) {
                parts.push(`Période du ${this.fmtDate(this.filtres.date_debut)} au ${this.fmtDate(this.filtres.date_fin)}`);
            }
            if (this.filtreStatut) {
                const label = this.filtreStatut === 'brouillon' ? 'Brouillons' : 'Validées';
                parts.push(label);
            }
            if (this.search) {
                parts.push(`Recherche: "${this.search}"`);
            }
            return parts.join(' • ') || "Toutes les périodes";
        }
    },

    async mounted() {
        await this.bootPage(async () => {
            await this.loadMetadata();
            if (typeof this.initPage === "function") {
                await this.initPage();
            }
        });
    },

    methods: {
        async loadData() {
            if (typeof this.loadList === "function") {
                return await this.loadList();
            }
        },

        async loadMetadata(journalId = null) {
            const params = new URLSearchParams({ page: this.page });
            if (journalId) params.set("journal_id", journalId);
            const { data } = await get(`/accounting/saisie/metadata?${params}`);
            if (data.status === "success") {
                this.exercice = data.exercice;
                this.journal = data.journal;
                this.journaux = data.journaux || [];
                this.multiDevise = !!data.multi_devise;
                this.devisePrincipale = data.devise_principale || "CDF";
                this.deviseDefaut = data.devise_defaut || this.devisePrincipale;
                this.journalDeviseEtrangere = !!data.journal_devise_etrangere;
                this.template = data.template || [];
<<<<<<< HEAD
                this.tauxUsd = data.taux_usd || 1;
                this.filtres.taux = this.tauxUsd;
                if (data.societe?.parametres) {
                    const p = data.societe.parametres;
                    this.filtres.devise_affichage = p.devise_affichage || this.filtres.devise_affichage;
                    this.filtres.mode_conversion = p.mode_conversion || this.filtres.mode_conversion;
=======
                if (typeof this.analytiqueObligatoireJournal !== "undefined") {
                    this.analytiqueObligatoireJournal = !!data.analytique_obligatoire;
                }
                if (typeof this.axesAnalytiques !== "undefined") {
                    this.axesAnalytiques = data.axes_analytiques || [];
>>>>>>> 356d4919f7208489f8fadf9a5b1244abeb82c9b0
                }
            }
            return data;
        },

        queryParams(extra = {}) {
            const p = new URLSearchParams({
                page: this.page,
                date_debut: this.filtres.date_debut,
                date_fin: this.filtres.date_fin,
                devise_affichage: this.filtres.devise_affichage,
                mode_conversion: this.filtres.mode_conversion,
                taux: this.filtres.taux
            });
            if (this.search) p.set("search", this.search);
            if (this.filtreStatut) p.set("statut", this.filtreStatut);

            Object.entries(extra).forEach(([k, v]) => {
                if (v !== null && v !== undefined && v !== "") p.set(k, v);
            });
            return p.toString();
        },

        handleResponse(data) {
            if (data.errors) {
                this.error = data.errors;
                this.warnings = [];
                return false;
            }
            this.warnings = data.warnings || [];
            if (data.message) {
                this.message = data.message;
                this.error = null;
            }
            return true;
        },

        formatMontant(v) {
            return new Intl.NumberFormat("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v || 0);
        },

        suffixDevise(code) {
            const c = (code || "CDF").toUpperCase();
            if (c === "CDF") return "Fr";
            if (c === "USD") return "USD";
            return c;
        },

        formatMontantDevise(montant, devise) {
            return `${this.formatMontant(montant)} ${this.suffixDevise(devise)}`;
        },

        formatDateTime(dt) {
            if (!dt) return "—";
            const s = String(dt).replace("T", " ");
            return s.length >= 19 ? s.slice(0, 19) : s;
        },

        fmtDate(d) {
            if (!d) return "";
            const parts = d.split('-');
            if (parts.length !== 3) return d;
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        },

        journalSelectionne() {
            const id = this.entete?.journal_id;
            if (!id) return this.journal || null;
            return (this.journaux || []).find((j) => j.id === id) || this.journal;
        },

        devisePourJournal(journal) {
            const principale = (this.devisePrincipale || "CDF").toUpperCase();
            const d = journal?.devise_defaut ? String(journal.devise_defaut).toUpperCase() : principale;
            return d || principale;
        },

        appliquerDeviseJournal() {
            if (!this.entete) return;
            const j = this.journalSelectionne();
            const principale = (this.devisePrincipale || "CDF").toUpperCase();
            const devise = this.devisePourJournal(j);
            this.entete.devise = devise;
            this.journalDeviseEtrangere = devise !== principale;
            this.multiDevise = this.multiDevise || this.journalDeviseEtrangere;
            if (typeof this.fetchTaux === "function") {
                this.fetchTaux();
            }
        },

        badgeStatut(s) {
            return {
                brouillon: "badge-soft-warning",
                validee: "badge-soft-success",
                extournee: "badge-soft-danger",
                simulee: "badge-soft-secondary",
            }[s] || "badge-soft-secondary";
        },
    },
};
