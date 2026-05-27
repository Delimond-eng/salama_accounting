import { get, postJson } from "./http.js";

/**
 * Barre globale devise : CDF natif, USD natif, consolidé CDF, consolidé USD.
 */
if (document.getElementById("DeviseBar")) {
    new Vue({
        el: "#DeviseBar",
        data() {
            return {
                loaded: false,
                options: { devises: [], devise_principale: "CDF" },
                prefs: {
                    devise_affichage: "CDF",
                    scope_devise: "consolide",
                    mode_conversion: "origine",
                },
                libelle: "",
            };
        },
        async mounted() {
            await this.load();
            window.addEventListener("societe-changed", () => this.load());
        },
        methods: {
            async load() {
                try {
                    const { data } = await get("/accounting/devise-options");
                    if (data.status === "success" && data.options) {
                        this.options = data.options;
                        this.prefs = {
                            devise_affichage: data.options.devise_affichage || "CDF",
                            scope_devise: data.options.scope_devise || "consolide",
                            mode_conversion: data.options.mode_conversion || "origine",
                        };
                        this.updateLibelle();
                        this.loaded = true;
                    }
                } catch (e) {
                    console.error(e);
                }
            },
            updateLibelle() {
                const d = this.prefs.devise_affichage;
                this.libelle =
                    this.prefs.scope_devise === "natif"
                        ? `Natif ${d}`
                        : `Consolidé ${d}`;
            },
            async save() {
                this.updateLibelle();
                const { data } = await postJson("/accounting/livres/preferences", this.prefs);
                if (data.status === "success") {
                    window.dispatchEvent(
                        new CustomEvent("devise-preferences-changed", { detail: data.options || this.prefs })
                    );
                }
            },
        },
    });
}
