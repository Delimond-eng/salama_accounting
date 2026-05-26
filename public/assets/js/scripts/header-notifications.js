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
        // Refresh every 5 minutes
        setInterval(() => this.loadNotifications(), 300000);
    },
    methods: {
        async loadNotifications() {
            try {
                const { data } = await get("/accounting/notifications");
                if (data.status === "success") {
                    this.alertes = data.alertes.items || [];
                    this.count = data.alertes.count || 0;
                }
            } catch (e) {
                console.error("Erreur notifications:", e);
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
