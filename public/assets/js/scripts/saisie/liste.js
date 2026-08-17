import { get, postJson } from "../../modules/http.js";
import { saisieMixin } from "./saisie-common.js";
import { rebrouillonMixin } from "../../modules/rebrouillon-mixin.js";

let timer = null;

new Vue({
    el: "#App",
    mixins: [saisieMixin, rebrouillonMixin],
    data() {
        return {
            ecritures: [],
            search: "",
            filtreStatut: "",
            pageCourante: 1,
            dernierePage: 1,
            totalEcritures: 0,
            parPage: 50,
        };
    },

    computed: {
        createUrl() {
            let url = `/accounting/saisie/${this.page}/ecriture`;
            if (this.filtres.journal_id) {
                url += (url.includes('?') ? '&' : '?') + `journal_id=${this.filtres.journal_id}`;
            }
            return url;
        },
        pagesAffichees() {
            const last = this.dernierePage;
            const cur = this.pageCourante;
            const delta = 2;
            const pages = [];
            const debut = Math.max(1, cur - delta);
            const fin = Math.min(last, cur + delta);
            if (debut > 1) {
                pages.push(1);
                if (debut > 2) pages.push("...");
            }
            for (let i = debut; i <= fin; i++) pages.push(i);
            if (fin < last) {
                if (fin < last - 1) pages.push("...");
                pages.push(last);
            }
            return pages;
        },
        intervalleAffiche() {
            if (!this.totalEcritures) return "0";
            const debut = (this.pageCourante - 1) * this.parPage + 1;
            const fin = Math.min(this.pageCourante * this.parPage, this.totalEcritures);
            return `${debut}–${fin} sur ${this.totalEcritures}`;
        },
    },

    methods: {
        async initPage() {
            // Lecture des paramètres de l'URL pour filtrage initial (ex: notifications)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('statut')) {
                this.filtreStatut = urlParams.get('statut');
            }
            if (urlParams.has('search')) {
                this.search = urlParams.get('search');
            }
            if (urlParams.has('date_debut')) {
                this.filtres.date_debut = urlParams.get('date_debut');
            }
            if (urlParams.has('date_fin')) {
                this.filtres.date_fin = urlParams.get('date_fin');
            }
            if (urlParams.has('journal_id')) {
                this.filtres.journal_id = parseInt(urlParams.get('journal_id'));
            }

            await this.loadList();
        },

        debounceLoad() {
            clearTimeout(timer);
            timer = setTimeout(() => this.loadList(), 350);
        },

        async loadList(resetPage = true) {
            if (resetPage) this.pageCourante = 1;
            this.isLoading = true;
            try {
                const params = this.queryParams({ p: this.pageCourante, per_page: this.parPage });
                const { data } = await get(`/accounting/saisie/ecritures?${params}`);
                if (data.status === "success") {
                    this.ecritures = data.ecritures || [];
                    this.totalEcritures = data.total || 0;
                    this.dernierePage = data.last_page || 1;
                    this.pageCourante = data.page || 1;
                }
            } finally {
                this.isLoading = false;
            }
        },

        allerPage(n) {
            if (n === "..." || n < 1 || n > this.dernierePage || n === this.pageCourante) return;
            this.pageCourante = n;
            this.loadList(false);
        },

        changerParPage() {
            this.loadList(true);
        },

        dupliquer(e) {
            window.location.href = `${this.createUrl}${this.createUrl.includes('?') ? '&' : '?'}copy=${e.id}`;
        },

        async valider(e) {
            if (!confirm(`Valider l'écriture ${e.num_piece} ?`)) return;
            const { data } = await postJson(`/accounting/saisie/ecritures/${e.id}/validate`, {});
            if (this.handleResponse(data)) this.loadList();
        },

        async supprimer(e) {
            if (!confirm(`Supprimer le brouillon ${e.num_piece} ?`)) return;
            const { data } = await postJson(`/accounting/saisie/ecritures/${e.id}/delete`, {});
            if (this.handleResponse(data)) this.loadList();
        },

        async onRebrouillonSuccess() {
            this.message = "Écriture remise en brouillon.";
            await this.loadList();
        },
    },
});
