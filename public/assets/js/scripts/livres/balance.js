import { get } from "../../modules/http.js";
import { livresMixin } from "./livres-common.js";

new Vue({
    el: "#App",
    mixins: [livresMixin],
    data() {
        return {
            lignes: [],
            totaux: null,
            classe: "",
            exportBase: "/accounting/export/livres/balance",
        };
    },
    methods: {
        queryParams(extra = {}) {
            const e = { ...extra };
            if (this.classe) {
                e.classe = this.classe;
            }
            return livresMixin.methods.queryParams.call(this, e);
        },

        async initPage() {
            await this.loadData();
        },
        async loadData() {
            this.isLoading = true;
            try {
                const { data } = await get(`/accounting/livres/balance/data?${this.queryParams()}`);
                if (!this.handleResponse(data)) {
                    return;
                }
                this.lignes = data.data?.lignes || [];
                this.totaux = data.data?.totaux || null;
            } finally {
                this.isLoading = false;
            }
        },
    },
});
