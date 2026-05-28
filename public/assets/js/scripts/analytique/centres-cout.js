import { analytiqueMixin } from "./analytique-common.js";

new Vue({
    el: "#App",
    mixins: [analytiqueMixin],
    created() {
        this.dataUrl = "/accounting/analytique/centres-cout/data";
        this.exportBase = "/accounting/export/analytique/centres-cout";
    },
});
