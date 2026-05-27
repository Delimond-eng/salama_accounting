import { get } from "../../modules/http.js";
import { livresMixin } from "./livres-common.js";

new Vue({
    el: "#App",
    mixins: [livresMixin],
    data() {
        return {
            lignes: [],
            journalId: "",
            exportBase: "/accounting/export/livres/journal",
        };
    },
    methods: {
        async initPage() {
            await this.loadData();
        },
        async loadData() {
            this.isLoading = true;
            try {
                const { data } = await get(`/accounting/livres/journal/data?${this.queryParams()}`);
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
