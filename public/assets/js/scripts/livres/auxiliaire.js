import { get } from "../../modules/http.js";
import { livresMixin } from "./livres-common.js";

new Vue({
    el: "#App",
    mixins: [livresMixin],
    data() {
        return {
            lignes: [],
            typeTiers: "",
            exportBase: "/accounting/export/livres/auxiliaire",
        };
    },
    methods: {
        queryParams(extra = {}) {
            const e = { ...extra };
            if (this.typeTiers) {
                e.type_tiers = this.typeTiers;
            }
            return livresMixin.methods.queryParams.call(this, e);
        },

        async initPage() {
            await this.loadData();
        },
        async loadData() {
            this.isLoading = true;
            try {
                const { data } = await get(`/accounting/livres/auxiliaire/data?${this.queryParams()}`);
                if (!this.handleResponse(data)) {
                    return;
                }
                this.lignes = data.lignes || [];
            } finally {
                this.isLoading = false;
            }
        },
    },
});
