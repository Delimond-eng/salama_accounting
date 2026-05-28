import { get } from "../modules/http.js";

/**
 * Gère les notifications et l'en-tête global.
 * Note: loaded, options et prefs sont inclus pour éviter les avertissements Vue
 * lors du rendu du composant DeviseBar imbriqué dans cet élément.
 */
new Vue({
    el: "#HeaderNotifications",
    data() {
        return {
            alertes: [],
            count: 0,
            isLoading: false,
            // Propriétés nécessaires pour le template imbriqué DeviseBar
            loaded: true,
            options: { devises: [] },
            prefs: {},
            libelle: ''
        };
    },
    mounted() {
        this.loadNotifications();
        setInterval(() => this.loadNotifications(), 300000);

        window.addEventListener('page-loading-start', () => { this.isLoading = true; });
        window.addEventListener('page-loading-stop', () => { this.isLoading = false; });
    },
    methods: {
        async loadNotifications() {
            this.isLoading = true;
            try {
                const { data } = await get("/accounting/notifications");
                if (data.status === "success" && data.alertes) {
                    const rawItems = data.alertes.items || [];
                    this.alertes = Array.isArray(rawItems) ? rawItems : Object.values(rawItems);
                    this.count = this.alertes.length;
                }
            } catch (e) {
                console.error("Erreur notifications:", e);
            } finally {
                this.isLoading = false;
            }
        },
        alerteBadgeClass(niveau) {
            return {
                danger: "badge-soft-danger",
                warning: "badge-soft-warning",
                info: "badge-soft-info",
                success: "badge-soft-success",
            }[niveau] || "badge-soft-secondary";
        },
        // Méthode fantôme pour éviter les erreurs si appelée via le template imbriqué
        save() {}
    }
});
