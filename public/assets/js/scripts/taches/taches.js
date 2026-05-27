import { get, postJson, post } from "../../modules/http.js";
import { vuePageMixin } from "../../modules/vue-page-mixin.js";

new Vue({
    el: "#App",
    mixins: [vuePageMixin],
    data() {
        return {
            meta: null,
            users: [],
            liste: [],
            detail: null,
            form: this.emptyForm(),
            rapportTexte: "",
            error: null,
            message: null,
            isLoading: false,
        };
    },
    computed: {
        etapesParUser() {
            if (!this.detail?.etapes) return [];
            const map = {};
            this.detail.etapes.forEach((e) => {
                const uid = e.user_id;
                if (!map[uid]) {
                    map[uid] = { user_id: uid, nom: e.assigne?.name || "Utilisateur", etapes: [] };
                }
                map[uid].etapes.push(e);
            });
            return Object.values(map);
        },
    },
    async mounted() {
        await this.bootPage(async () => {
            await this.loadMeta();
            await this.loadListe();
        });
    },
    methods: {
        emptyForm() {
            return {
                id: null,
                titre: "",
                description: "",
                date_echeance: "",
                etapes: [{ user_id: null, libelle: "", ordre: 1 }],
            };
        },
        async loadMeta() {
            const { data } = await get("/accounting/taches/metadata");
            if (data.status === "success") {
                this.meta = data;
                this.users = data.users || [];
            }
        },
        async loadListe() {
            this.isLoading = true;
            try {
                const { data } = await get("/accounting/taches/list");
                if (data.status === "success") this.liste = data.taches || [];
            } finally {
                this.isLoading = false;
            }
        },
        async openDetail(id) {
            const { data } = await get(`/accounting/taches/${id}`);
            if (data.status === "success") {
                this.detail = data.tache;
                this.rapportTexte = "";
            } else if (data.errors) {
                this.error = data.errors;
            }
        },
        openCreate() {
            this.form = this.emptyForm();
            if (this.users.length) this.form.etapes[0].user_id = this.users[0].id;
            new bootstrap.Modal(document.getElementById("modal_tache")).show();
        },
        editFromDetail() {
            if (!this.detail) return;
            this.form = {
                id: this.detail.id,
                titre: this.detail.titre,
                description: this.detail.description || "",
                date_echeance: this.detail.date_echeance ? String(this.detail.date_echeance).slice(0, 10) : "",
                etapes: this.detail.etapes.map((e, i) => ({
                    user_id: e.user_id,
                    libelle: e.libelle,
                    ordre: e.ordre || i + 1,
                })),
            };
            new bootstrap.Modal(document.getElementById("modal_tache")).show();
        },
        addEtapeForm() {
            this.form.etapes.push({ user_id: null, libelle: "", ordre: this.form.etapes.length + 1 });
        },
        async saveTache() {
            this.isLoading = true;
            this.error = null;
            const payload = {
                ...this.form,
                etapes: this.form.etapes.filter((e) => e.user_id && (e.libelle || "").trim()),
            };
            const { data } = await postJson("/accounting/taches/save", payload);
            this.isLoading = false;
            if (data.errors) {
                this.error = data.errors;
                return;
            }
            this.message = data.message;
            bootstrap.Modal.getInstance(document.getElementById("modal_tache"))?.hide();
            await this.loadListe();
            if (data.tache?.id) await this.openDetail(data.tache.id);
        },
        peutCocherEtape(e) {
            if (!this.meta) return false;
            return (
                this.meta.utilisateur.est_super_admin ||
                e.user_id === this.meta.utilisateur.id
            );
        },
        async toggleEtape(etapeId) {
            const { data } = await postJson(`/accounting/taches/etapes/${etapeId}/toggle`, {});
            if (data.errors) {
                this.error = data.errors;
                return;
            }
            if (data.tache) this.detail = data.tache;
            await this.loadListe();
        },
        async envoyerRapport() {
            if (!this.detail || !this.rapportTexte.trim()) return;
            this.isLoading = true;
            const { data } = await postJson(`/accounting/taches/${this.detail.id}/rapport`, {
                contenu: this.rapportTexte,
            });
            this.isLoading = false;
            if (data.errors) {
                this.error = data.errors;
                return;
            }
            this.rapportTexte = "";
            this.message = data.message;
            await this.openDetail(this.detail.id);
        },
        async joindreFichier(ev) {
            const file = ev.target.files?.[0];
            if (!file || !this.detail) return;
            const fd = new FormData();
            fd.append("fichier", file);
            this.isLoading = true;
            const { data } = await post(`/accounting/taches/${this.detail.id}/fichier`, fd);
            this.isLoading = false;
            ev.target.value = "";
            if (data.errors) {
                this.error = data.errors;
                return;
            }
            this.message = data.message;
            await this.openDetail(this.detail.id);
        },
        progressClass(t) {
            const p = t.progression?.pourcent || 0;
            if (p >= 100) return "bg-success";
            if (p >= 50) return "bg-info";
            return "bg-warning";
        },
        statutLabel(s) {
            return { ouverte: "Ouverte", en_cours: "En cours", terminee: "Terminée", annulee: "Annulée" }[s] || s;
        },
        statutBadge(s) {
            return {
                ouverte: "badge-soft-secondary",
                en_cours: "badge-soft-primary",
                terminee: "badge-soft-success",
                annulee: "badge-soft-danger",
            }[s] || "badge-soft-secondary";
        },
        fmtDate(d) {
            if (!d) return "—";
            const s = String(d).slice(0, 10);
            const [y, m, j] = s.split("-");
            return y && m && j ? `${j}/${m}/${y}` : s;
        },
        fmtDateTime(d) {
            if (!d) return "";
            return String(d).replace("T", " ").slice(0, 16);
        },
    },
});
