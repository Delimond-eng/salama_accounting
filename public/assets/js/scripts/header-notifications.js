import { get } from "../modules/http.js";

new Vue({
    el: "#HeaderNotifications",
    data() {
        return {
            alertes: [],
            count: 0,
            isLoading: false
        };
    },
    mounted() {
        this.loadNotifications();
        // Rafraîchissement automatique toutes les 5 minutes
        setInterval(() => this.loadNotifications(), 300000);

        // Écouteur global pour afficher le loader quand une page charge
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
        }
    }
});
