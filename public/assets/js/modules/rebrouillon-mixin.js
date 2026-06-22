/**
 * Remise en brouillon d'écritures validées (mot de passe + permission saisie.unvalidate).
 */
import { postJson } from "./http.js";

export const rebrouillonMixin = {
    data() {
        return {
            canUnvalidate: !!(window.__SAISIE_PERMISSIONS__?.unvalidate),
            rebrouillonCible: null,
            rebrouillonPassword: "",
            rebrouillonError: null,
            rebrouillonLoading: false,
            rebrouillonModal: null,
            rebrouillonModalBound: false,
        };
    },

    methods: {
        initRebrouillonModal() {
            if (this.rebrouillonModal) {
                return true;
            }
            const el = document.getElementById("modal_rebrouillon");
            if (!el || typeof bootstrap === "undefined") {
                return false;
            }
            this.rebrouillonModal = bootstrap.Modal.getOrCreateInstance(el);
            if (!this.rebrouillonModalBound) {
                el.addEventListener("hidden.bs.modal", () => this.resetRebrouillonModal());
                this.rebrouillonModalBound = true;
            }

            return true;
        },

        ouvrirRebrouillon(ecriture) {
            if (!this.canUnvalidate || !ecriture?.id) {
                return;
            }
            if (!this.initRebrouillonModal()) {
                console.error("Modal rebrouillon introuvable.");
                return;
            }
            this.rebrouillonCible = ecriture;
            this.rebrouillonPassword = "";
            this.rebrouillonError = null;
            this.rebrouillonModal.show();
            this.$nextTick(() => this.$refs.rebrouillonPasswordInput?.focus());
        },

        resetRebrouillonModal() {
            this.rebrouillonCible = null;
            this.rebrouillonPassword = "";
            this.rebrouillonError = null;
            this.rebrouillonLoading = false;
        },

        async confirmerRebrouillon() {
            if (!this.rebrouillonCible || !this.rebrouillonPassword) {
                return;
            }
            this.rebrouillonLoading = true;
            this.rebrouillonError = null;
            try {
                const { data } = await postJson(
                    `/accounting/saisie/ecritures/${this.rebrouillonCible.id}/unvalidate`,
                    { password: this.rebrouillonPassword }
                );
                if (data.status === "success") {
                    this.rebrouillonModal?.hide();
                    if (typeof this.onRebrouillonSuccess === "function") {
                        await this.onRebrouillonSuccess(data.ecriture);
                    } else if (this.handleResponse) {
                        this.handleResponse(data);
                    }
                } else {
                    this.rebrouillonError = (data.errors || ["Erreur"])[0];
                }
            } catch (err) {
                this.rebrouillonError = "Erreur réseau.";
            } finally {
                this.rebrouillonLoading = false;
            }
        },
    },
};
