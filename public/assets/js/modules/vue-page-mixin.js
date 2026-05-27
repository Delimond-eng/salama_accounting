/**
 * Évite l'affichage des moustaches Vue (@{{ }}) au chargement / refresh.
 * Gère également la communication avec le loader global du header.
 */
export const vuePageMixin = {
    data() {
        return {
            pageReady: false,
        };
    },

    watch: {
        // Surveille isLoading pour notifier le header
        isLoading(val) {
            if (val) {
                window.dispatchEvent(new CustomEvent('page-loading-start'));
            } else {
                window.dispatchEvent(new CustomEvent('page-loading-stop'));
            }
        }
    },

    methods: {
        async bootPage(initFn) {
            const splash = document.getElementById("vue-splash-loader");
            window.dispatchEvent(new CustomEvent('page-loading-start'));

            try {
                if (typeof initFn === "function") {
                    await initFn.call(this);
                }
            } catch (e) {
                console.error(e);
                this.error = this.error || ["Erreur de chargement de la page."];
            } finally {
                this.pageReady = true;
                if (splash) {
                    splash.style.display = "none";
                }
                const globalLoader = document.getElementById("global-loader");
                if (globalLoader) {
                    globalLoader.style.display = "none";
                }
                window.dispatchEvent(new CustomEvent('page-loading-stop'));
            }
        },
    },
};
