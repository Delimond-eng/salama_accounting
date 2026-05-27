import { etatsMixin } from "./etats-common.js";

new Vue({
    el: "#App",
    mixins: [etatsMixin],
    data() {
        return {
            exports: [
                { type: "globaux", ref: "BUNDLE", label: "États financiers globaux", desc: "Package complet : Bilan, CR, TFT, TVCP et Annexes dans un seul fichier.", icon: "ti-packages", color: "danger" },
                { type: "bilan", ref: "BI", label: "Bilan actif/passif", desc: "Export Excel/CSV du bilan SYSCOHADA.", icon: "ti-columns", color: "primary" },
                { type: "compte-resultat", ref: "CR", label: "Compte de résultat", desc: "Charges, produits et soldes intermédiaires.", icon: "ti-chart-bar", color: "success" },
                { type: "flux-tresorerie", ref: "TFT", label: "Flux de trésorerie", desc: "Tableau des flux par activité.", icon: "ti-arrows-shuffle", color: "info" },
                { type: "variation-kp", ref: "TVCP", label: "Variation capitaux propres", desc: "Mouvements des capitaux propres.", icon: "ti-trending-up", color: "warning" },
            ],
        };
    },
    methods: {
        async initPage() {},

        exportUrlFor(type, format = "excel") {
            return `/accounting/export/etats/${type}/${format}?${this.queryParams()}`;
        },
    },
});
