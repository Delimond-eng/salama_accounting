import { get, post, postJson } from "../../modules/http.js";
import { parametresMixin } from "./parametres-common.js";

new Vue({
    el: "#App",
    mixins: [parametresMixin],
    data() {
        return {
            devises: [],
            exercices: [],
            formSociete: this.emptySociete(),
            formExercice: this.emptyExercice(),
            logoPreview: null,
            logoFile: null,
            banques: [],
        };
    },

    methods: {
        async initPage() {
            await this.loadDetail();
        },

        emptySociete() {
            return {
                id: null,
                code: "",
                raison_sociale: "",
                forme_juridique: "",
                sigle: "",
                adresse: "",
                ville: "",
                pays: "République Démocratique du Congo",
                telephone: "",
                email: "",
                rccm: "",
                num_contribuable: "",
                identification_nationale: "",
                num_cnps: "",
                regime_fiscal: "",
                devise_principale: "CDF",
                statut: "active",
            };
        },

        emptyBanque() {
            return { banque: "", numero_compte: "", devise: "CDF", est_defaut: false };
        },

        emptyExercice() {
            const y = new Date().getFullYear();
            return {
                id: null,
                libelle: `Exercice ${y}`,
                annee: y,
                date_debut: `${y}-01-01`,
                date_fin: `${y}-12-31`,
                statut: "ouvert",
                est_courant: false,
            };
        },

        async loadDetail() {
            this.isLoading = true;
            try {
                const { data } = await get("/accounting/parametres/societe/detail");
                if (data.status === "success") {
                    this.devises = data.devises || [];
                    this.exercices = data.exercices || [];
                    if (data.societe) {
                        this.formSociete = { ...data.societe };
                        this.banques = (data.societe.banques || []).map((b) => ({
                            banque: b.banque,
                            numero_compte: b.numero_compte,
                            devise: b.devise || "CDF",
                            est_defaut: !!b.est_defaut,
                        }));
                        this.logoPreview = data.societe.logo_url || null;
                    }
                }
            } finally {
                this.isLoading = false;
            }
        },

        onLogoSelected(e) {
            const file = e.target.files?.[0];
            this.logoFile = file || null;
            if (file) {
                this.logoPreview = URL.createObjectURL(file);
            }
        },

        async uploadLogo() {
            if (!this.logoFile) return;
            this.isLoading = true;
            const fd = new FormData();
            fd.append("logo", this.logoFile);
            const { data } = await post("/accounting/parametres/societe/logo", fd);
            this.isLoading = false;
            if (!this.handleResponse(data)) return;
            if (data.logo_url) {
                this.logoPreview = data.logo_url;
                document.querySelectorAll(".app-logo-img").forEach((img) => {
                    img.src = data.logo_url;
                });
            }
            this.logoFile = null;
        },

        ajouterBanque() {
            this.banques.push(this.emptyBanque());
        },

        definirBanqueDefaut(index) {
            if (!this.banques[index]?.est_defaut) return;
            this.banques.forEach((b, i) => {
                if (i !== index) b.est_defaut = false;
            });
        },

        async saveSociete() {
            this.isLoading = true;
            const payload = { ...this.formSociete, banques: this.banques };
            const { data } = await postJson("/accounting/parametres/societe/save", payload);
            this.isLoading = false;
            if (!this.handleResponse(data)) return;
            if (data.societe) {
                this.formSociete = { ...data.societe };
                this.banques = (data.societe.banques || []).map((b) => ({
                    banque: b.banque,
                    numero_compte: b.numero_compte,
                    devise: b.devise || "CDF",
                    est_defaut: !!b.est_defaut,
                }));
            }
            await this.loadContext();
            this.loadDetail();
        },

        openExerciceForm() {
            this.formExercice = this.emptyExercice();
            new bootstrap.Modal(document.getElementById("modal_exercice")).show();
        },

        editExercice(ex) {
            this.formExercice = { ...ex };
            new bootstrap.Modal(document.getElementById("modal_exercice")).show();
        },

        async saveExercice() {
            this.isLoading = true;
            const { data } = await postJson("/accounting/parametres/exercice/save", this.formExercice);
            this.isLoading = false;
            if (!this.handleResponse(data)) return;
            bootstrap.Modal.getInstance(document.getElementById("modal_exercice"))?.hide();
            this.loadDetail();
            await this.loadContext();
        },

        async setCourant(ex) {
            const { data } = await postJson("/accounting/parametres/exercice/courant", {
                exercice_id: ex.id,
            });
            if (!this.handleResponse(data)) return;
            this.loadDetail();
            await this.loadContext();
        },
    },
});
