/**
 * Mixin partagé pour le sélecteur unifié des 6 modes devise
 * (USD/CDF natif, natif converti, consolidé).
 *
 * Attend `options.modes_devise` et `filtres.mode_devise`
 * (ou `filtresDevise.mode_devise` pour le dashboard).
 */
export const deviseFiltreMixin = {
    computed: {
        modesDeviseListe() {
            if (Array.isArray(this.modesDevise) && this.modesDevise.length) {
                return this.modesDevise;
            }
            return (this.options && this.options.modes_devise) || [];
        },

        modeDeviseIdCourant() {
            if (this.filtres && this.filtres.mode_devise) {
                return this.filtres.mode_devise;
            }
            if (this.filtresDevise && this.filtresDevise.mode_devise) {
                return this.filtresDevise.mode_devise;
            }
            return null;
        },

        modeDeviseCourant() {
            const id = this.modeDeviseIdCourant;
            return this.modesDeviseListe.find((m) => m.id === id) || null;
        },

        libelleModeDevise() {
            return this.modeDeviseCourant ? this.modeDeviseCourant.label : "";
        },

        noteModeDevise() {
            return this.modeDeviseCourant ? this.modeDeviseCourant.note : "";
        },

        deviseAffichageCourante() {
            if (this.modeDeviseCourant) {
                return this.modeDeviseCourant.devise_affichage;
            }
            if (this.filtres && this.filtres.devise_affichage) {
                return this.filtres.devise_affichage;
            }
            if (this.filtresDevise && this.filtresDevise.devise_affichage) {
                return this.filtresDevise.devise_affichage;
            }
            return "CDF";
        },
    },

    methods: {
        /** Aligne devise_affichage / scope_devise / mode_conversion sur le mode choisi. */
        syncDeviseFromMode() {
            const m = this.modeDeviseCourant;
            if (!m) {
                return;
            }

            const cible = this.filtres || this.filtresDevise;
            if (!cible) {
                return;
            }

            cible.devise_affichage = m.devise_affichage;
            cible.scope_devise = m.scope_devise;
            cible.mode_conversion = m.mode_conversion;
        },

        /** Applique les options devise renvoyées par l'API (metadata, dashboard, prefs). */
        applyDeviseOptionsFromPayload(payload) {
            const o = payload?.options_devise || payload?.options || payload;
            if (!o) {
                return;
            }

            if (Array.isArray(o.modes_devise) && o.modes_devise.length) {
                if (this.options) {
                    this.options = { ...this.options, modes_devise: o.modes_devise };
                }
                if (Object.prototype.hasOwnProperty.call(this, "modesDevise")) {
                    this.modesDevise = o.modes_devise;
                }
            }

            const cible = this.filtres || this.filtresDevise;
            if (!cible) {
                return;
            }

            if (o.mode_devise) {
                cible.mode_devise = o.mode_devise;
            }
            if (o.devise_affichage) {
                cible.devise_affichage = o.devise_affichage;
            }
            if (o.scope_devise) {
                cible.scope_devise = o.scope_devise;
            }
            if (o.mode_conversion) {
                cible.mode_conversion = o.mode_conversion;
            }

            this.syncDeviseFromMode();
        },

        /** Handler standard du sélecteur de mode. */
        onModeDeviseChange() {
            this.syncDeviseFromMode();
            if (typeof this.onFiltreChange === "function") {
                this.onFiltreChange();
            } else if (typeof this.loadData === "function") {
                this.loadData(true);
            }
        },

        /** Paramètre query string unique pour les requêtes API. */
        queryParamModeDevise() {
            return this.modeDeviseIdCourant || "cdf_consolide";
        },
    },
};
