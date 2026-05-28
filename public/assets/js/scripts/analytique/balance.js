import { analytiqueMixin } from "./analytique-common.js";

new Vue({
    el: "#App",
    mixins: [analytiqueMixin],
    created() {
        this.dataUrl = "/accounting/analytique/balance/data";
        this.exportBase = "/accounting/export/analytique/balance";
    },
});
