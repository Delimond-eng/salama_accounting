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
        setInterval(() => this.loadNotifications(), 300000);
    },
    methods: {
        async loadNotifications() {
            try {
                const { data } = await get("/accounting/notifications");
                if (data.status === "success" && data.alertes) {
                    const rawItems = data.alertes.items || [];
                    // Force en tableau pour garantir la réactivité et le .length
                    this.alertes = Array.isArray(rawItems) ? rawItems : Object.values(rawItems);
                    this.count = this.alertes.length;
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
