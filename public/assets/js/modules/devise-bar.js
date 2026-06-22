import { get, postJson } from "./http.js";
import { deviseFiltreMixin } from "./devise-filtre-mixin.js";

/**
 * Barre globale devise : sélecteur unifié des 6 modes (header).
 */
if (document.getElementById("DeviseBar")) {
    new Vue({
        el: "#DeviseBar",
        mixins: [deviseFiltreMixin],
        data() {
            return {
                loaded: false,
                options: { modes_devise: [], mode_devise: "cdf_consolide" },
                filtres: {
                    mode_devise: "cdf_consolide",
                    devise_affichage: "CDF",
                    scope_devise: "consolide",
                    mode_conversion: "origine",
                },
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
                        this.applyDeviseOptionsFromPayload({ options: data.options });
                        this.loaded = true;
                    }
                } catch (e) {
                    console.error(e);
                }
            },
            async save() {
                this.syncDeviseFromMode();
                const { data } = await postJson("/accounting/livres/preferences", {
                    mode_devise: this.queryParamModeDevise(),
                });
                if (data.status === "success") {
                    if (data.options) {
                        this.applyDeviseOptionsFromPayload({ options: data.options });
                    }
                    window.dispatchEvent(
                        new CustomEvent("devise-preferences-changed", {
                            detail: data.options || this.filtres,
                        })
                    );
                }
            },
        },
    });
}
