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
        exportBase() {
            if (location.pathname.includes("/admin/users")) return "/accounting/export/admin/users";
            if (location.pathname.includes("/admin/roles")) return "/accounting/export/admin/roles";
            if (location.pathname.includes("/admin/logs")) return "/accounting/export/admin/audit-logs";
            return "";
        },
        filteredUsers() {
            const q = (this.search || "").toLowerCase().trim();
            if (!q) return this.users;
            return this.users.filter(u =>
                u.name.toLowerCase().includes(q) ||
                u.email.toLowerCase().includes(q)
            );
        },
        filteredRoles() {
            const q = (this.searchRole || "").toLowerCase().trim();
            if (!q) return this.roles;
            return this.roles.filter(r =>
                r.name.toLowerCase().includes(q) ||
                (this.roleLabel(r.name)).toLowerCase().includes(q)
            );
        },
        currentUserId() {
            return window.__CURRENT_USER_ID__ || null;
        },
        allActions() {
            return this.actions;
        },
        allRoles() {
            return this.roles;
        },
        allUsers() {
            return this.users;
        },
        errorList() {
            return this.normalizeErrors(this.error);
        },
    },

    async mounted() {
        await this.bootPage(async () => {
            await this.loadMeta();
            if (location.pathname.includes("/admin/users")) {
                await Promise.all([this.viewAllUsers(), this.viewAllRoles()]);
            }
            if (location.pathname.includes("/admin/roles")) {
                await this.viewAllRoles();
            }
            if (location.pathname.includes("/admin/logs")) {
                await this.viewAuditLogs();
            }
        });
    },

    methods: {
        queryParams() {
            const p = new URLSearchParams();
            if (this.search) p.set("search", this.search);
            return p.toString();
        },

        async loadMeta() {
            const { data } = await get("/actions");
            this.actions = data.modules || data || [];
            this.roleLabels = data.role_labels || {};
            this.roleDescriptions = data.role_descriptions || {};
            this.protectedRoles = data.protected_roles || ["super_admin"];
            if (data.permission_columns) {
                this.permissionColumns = data.permission_columns;
            }
        },

        roleLabel(name) {
            return this.roleLabels[name] || name;
        },

        isProtectedRole(name) {
            return this.protectedRoles.includes(name);
        },

        moduleHasAction(module, col) {
            return (module.actions || []).some((a) => a.action === col);
        },

        formatDateTime(dt) {
            if (!dt) return "—";
            const s = String(dt).trim();
            if (/^\d{2}\/\d{2}\/\d{4}/.test(s)) return s;
            const d = new Date(s.includes("T") ? s : s.replace(" ", "T"));
            if (Number.isNaN(d.getTime())) return s;
            const pad = (n) => String(n).padStart(2, "0");
            return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        },

        normalizeErrors(errors) {
            if (!errors) return [];
            if (Array.isArray(errors)) return errors;
            if (typeof errors === "object") return Object.values(errors).flat();
            return [String(errors)];
        },

        handleResponse(data) {
            if (data.errors) {
                this.error = this.normalizeErrors(data.errors);
                this.message = null;
                return false;
            }
            if (data.message) {
                this.message = data.message;
                this.error = null;
            }
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
            const { data } = await get("/audit/logs");
            this.logs = data.logs || [];
        },

        openForm() {
            this.reset();
            const modal = new bootstrap.Modal(document.getElementById("modal_user"));
            modal.show();
        },

        openRoleForm() {
            this.formRole = { name: "", permissions: [], role_id: "" };
            const modal = new bootstrap.Modal(document.getElementById("role-modal"));
            modal.show();
        },

        async saveUser() {
            this.isLoading = true;
            const payload = {
                name: this.form.name,
                email: this.form.email,
                password: this.form.password || null,
                role: this.roles.find(r => r.id === this.form.role_id)?.name,
                user_id: this.form.user_id || null,
                actif: this.form.actif
            };

            try {
                const { data } = await postJson("/user/create", payload);
                this.isLoading = false;
                if (!this.handleResponse(data)) return;
                await this.viewAllUsers();
                bootstrap.Modal.getInstance(document.getElementById("modal_user"))?.hide();
                this.reset();
            } catch (e) {
                this.isLoading = false;
                this.error = ["Erreur lors de l'enregistrement."];
            }
        },

        async createRole() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/role/create", this.formRole);
                this.isLoading = false;
                if (!this.handleResponse(data)) return;
                await this.viewAllRoles();
                bootstrap.Modal.getInstance(document.getElementById("role-modal"))?.hide();
            } catch (e) {
                this.isLoading = false;
                this.error = ["Erreur lors de l'enregistrement du rôle."];
            }
        },

        manageAccess(user) {
            this.error = null;
            this.form.user_id = user.id;

            // On combine les permissions directes et celles héritées du rôle pour les afficher cochées
            let directPerms = user.permissions || [];
            let inheritedPerms = [];
            if (user.roles && user.roles.length > 0) {
                user.roles.forEach(role => {
                    if (role.permissions) {
                        inheritedPerms = [...inheritedPerms, ...role.permissions];
                    }
                });
            }

            const allCombined = [...directPerms, ...inheritedPerms];
            this.form.permissions = [...new Set(allCombined.map(p => typeof p === "string" ? p : p.name))];

            const modal = new bootstrap.Modal(document.getElementById("access_users"));
            modal.show();
        },

        async addAccess() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/user/access", {
                    user_id: this.form.user_id,
                    permissions: this.form.permissions,
                });
                this.isLoading = false;
                if (!this.handleResponse(data)) return;
                await this.viewAllUsers();
                bootstrap.Modal.getInstance(document.getElementById("access_users"))?.hide();
            } catch (e) {
                this.isLoading = false;
                this.error = ["Erreur lors de la mise à jour des accès."];
            }
        },

        editRole(role) {
            if (this.isProtectedRole(role.name)) return;
            this.error = null;
            this.formRole.name = role.name;
            this.formRole.permissions = (role.permissions || []).map((p) => typeof p === "string" ? p : p.name);
            this.formRole.role_id = role.id;
            const modal = new bootstrap.Modal(document.getElementById("role-modal"));
            modal.show();
        },

        editUser(user) {
            this.error = null;
            const roleId = user.roles?.[0]?.id || "";
            this.form.name = user.name;
            this.form.email = user.email;
            this.form.role_id = roleId;
            this.form.user_id = user.id;
            this.form.password = "";
            this.form.actif = !!user.actif;
            const modal = new bootstrap.Modal(document.getElementById("modal_user"));
            modal.show();
        },

        reset() {
            this.form = {
                name: "",
                email: "",
                password: "",
                role_id: "",
                user_id: "",
                actif: true,
                permissions: [],
            };
        },
    }
});
