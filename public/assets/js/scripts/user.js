import { get, postJson } from "../modules/http.js";
import { vuePageMixin } from "../modules/vue-page-mixin.js";
import { exportMixin } from "../modules/export-mixin.js";

new Vue({
    el: "#App",
    mixins: [vuePageMixin, exportMixin],

    data() {
        return {
            error: null,
            message: null,
            isLoading: false,
            search: "",
            searchRole: "",
            users: [],
            roles: [],
            actions: [],
            roleLabels: {},
            roleDescriptions: {},
            protectedRoles: ["super_admin"],
            permissionColumns: ["view", "create", "update", "validate", "delete", "export", "process"],
            columnLabels: {
                view: "Voir",
                create: "Créer",
                update: "Modifier",
                validate: "Valider",
                delete: "Supprimer",
                export: "Exporter",
                process: "Traiter",
            },
            logs: [],
            form: {
                name: "",
                email: "",
                password: "",
                role_id: "",
                user_id: "",
                actif: true,
                permissions: [],
            },
            formRole: {
                name: "",
                permissions: [],
                role_id: "",
            },
        };
    },

    computed: {
        errorList() {
            if (!this.error) return [];
            if (Array.isArray(this.error)) return this.error;
            if (typeof this.error === "object") return Object.values(this.error).flat();
            return [String(this.error)];
        },
        filteredUsers() {
            const q = (this.search || "").toLowerCase().trim();
            if (!q) return this.users;
            return this.users.filter(u => (u.name || "").toLowerCase().includes(q) || (u.email || "").toLowerCase().includes(q));
        },
        filteredRoles() {
            const q = (this.searchRole || "").toLowerCase().trim();
            if (!q) return this.roles;
            return this.roles.filter(r => (r.name || "").toLowerCase().includes(q) || (this.roleLabel(r.name)).toLowerCase().includes(q));
        },
        currentUserId() { return window.__CURRENT_USER_ID__ || null; },
        allActions() { return this.actions; },
        allRoles() { return this.roles; },
        allUsers() { return this.users; },
    },

    async mounted() {
        await this.bootPage(async () => {
            await this.loadMeta();
            if (location.pathname.includes("/admin/users")) {
                await Promise.all([this.viewAllUsers(), this.viewAllRoles()]);
            } else if (location.pathname.includes("/admin/roles")) {
                await this.viewAllRoles();
            } else if (location.pathname.includes("/admin/logs")) {
                await this.viewAuditLogs();
            }
        });
    },

    methods: {
        async loadMeta() {
            try {
                const { data } = await get("/actions");
                this.actions = data.modules || data || [];
                this.roleLabels = data.role_labels || {};
            } catch (e) {}
        },

        roleLabel(name) { return this.roleLabels[name] || name; },
        isProtectedRole(name) { return (this.protectedRoles || []).includes(name); },
        moduleHasAction(module, col) { return (module.actions || []).some((a) => a.action === col); },
        formatDateTime(dt) { return dt || "—"; },

        handleResponse(data) {
            if (data.errors) { this.error = data.errors; return false; }
            if (data.message) { this.message = data.message; this.error = null; }
            return true;
        },

        async viewAllUsers() {
            const { data } = await get("/users/all");
            this.users = data.users || [];
        },

        async viewAllRoles() {
            const { data } = await get("/roles/all");
            this.roles = data.roles || [];
        },

        async viewAuditLogs() {
            this.isLoading = true;
            try {
                const { data } = await get("/admin/audit/logs");
                this.logs = data.logs || [];

                // Appel de l'initialisation DataTable après le rendu Vue
                this.$nextTick(() => {
                    setTimeout(() => {
                        const tableEl = document.getElementById("audit-logs-table");
                        if (tableEl) this.initOrRefreshDatatable(tableEl);
                    }, 50);
                });
            } finally {
                this.isLoading = false;
            }
        },

        destroyDatatable(tableEl) {
            const $ = window.$;
            if (!tableEl || !$ || !$.fn || !$.fn.DataTable) return;

            if ($.fn.DataTable.isDataTable(tableEl)) {
                const dt = $(tableEl).DataTable();
                dt.destroy();
            }
        },

        initOrRefreshDatatable(tableEl) {
            const $ = window.$;
            if (!$ || !$.fn || !$.fn.DataTable) return;

            this.destroyDatatable(tableEl);

            $(tableEl).DataTable({
                bFilter: true,
                ordering: true,
                info: true,
                order: [[0, "desc"]],
                language: {
                    search: " ",
                    sLengthMenu: "Lignes par page _MENU_",
                    searchPlaceholder: "Rechercher",
                    info: "Affichage _START_ - _END_ sur _TOTAL_",
                    paginate: {
                        next: '<i class="ti ti-chevron-right"></i>',
                        previous: '<i class="ti ti-chevron-left"></i> ',
                    },
                },
            });
        },

        async saveUser() {
            this.isLoading = true;
            const payload = {
                name: this.form.name, email: this.form.email, password: this.form.password || null,
                role: this.roles.find(r => r.id === this.form.role_id)?.name,
                user_id: this.form.user_id || null, actif: this.form.actif
            };
            try {
                const { data } = await postJson("/user/create", payload);
                this.isLoading = false;
                if (!this.handleResponse(data)) return;
                await this.viewAllUsers();
                bootstrap.Modal.getInstance(document.getElementById("modal_user"))?.hide();
                this.reset();
            } catch (e) { this.isLoading = false; }
        },
        editUser(user) {
            const roleId = user.roles?.[0]?.id || "";
            this.form.name = user.name; this.form.email = user.email; this.form.role_id = roleId; this.form.user_id = user.id; this.form.password = ""; this.form.actif = !!user.actif;
            new bootstrap.Modal(document.getElementById("modal_user")).show();
        },
        manageAccess(user) {
            this.form.user_id = user.id;
            let combined = [...(user.permissions || []), ...(user.roles || []).flatMap(r => r.permissions || [])];
            this.form.permissions = [...new Set(combined.map(p => typeof p === "string" ? p : p.name))];
            new bootstrap.Modal(document.getElementById("access_users")).show();
        },
        async addAccess() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/user/access", { user_id: this.form.user_id, permissions: this.form.permissions });
                this.isLoading = false;
                if (!this.handleResponse(data)) return;
                await this.viewAllUsers();
                bootstrap.Modal.getInstance(document.getElementById("access_users"))?.hide();
            } catch (e) { this.isLoading = false; }
        },
        openRoleForm() { this.formRole = { name: "", permissions: [], role_id: "" }; new bootstrap.Modal(document.getElementById("role-modal")).show(); },
        async createRole() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/role/create", this.formRole);
                this.isLoading = false;
                if (!this.handleResponse(data)) return;
                await this.viewAllRoles();
                bootstrap.Modal.getInstance(document.getElementById("role-modal"))?.hide();
            } catch (e) { this.isLoading = false; }
        },
        editRole(role) {
            if (this.isProtectedRole(role.name)) return;
            this.formRole = { name: role.name, permissions: (role.permissions || []).map(p => typeof p === "string" ? p : p.name), role_id: role.id };
            new bootstrap.Modal(document.getElementById("role-modal")).show();
        },
        reset() { this.form = { name: "", email: "", password: "", role_id: "", user_id: "", actif: true, permissions: [], }; }
    }
});
