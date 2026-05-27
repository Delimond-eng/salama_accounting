import { analytiqueMixin } from "./analytique-common.js";

new Vue({
    el: "#App",
    mixins: [analytiqueMixin],
    created() {
        this.dataUrl = "/accounting/analytique/grand-livre/data";
    },
});
