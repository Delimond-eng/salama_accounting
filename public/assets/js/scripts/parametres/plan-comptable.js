import { get, post, postJson } from "../../modules/http.js";
import { parametresMixin } from "./parametres-common.js";

let searchTimer = null;

new Vue({
    el: "#App",
    mixins: [parametresMixin],
    data() {
        return {
            comptes: [],
            classes: {},
            search: "",
            filtreClasse: null,
            form: this.emptyForm(),
            exportBase: "/accounting/export/parametres/plan-comptable",

            // Import state
            importFile: null,
            importClasse: 1,
            isDragging: false,
            isImporting: false,
            isParsing: false,
        };
    },

    methods: {
        queryParams() {
            const p = new URLSearchParams();
            if (this.search) {
                p.set("search", this.search);
            }
            if (this.filtreClasse) {
                p.set("classe", this.filtreClasse);
            }
            return p.toString();
        },

        async initPage() {
            await this.loadData();
        },

        async onSocieteChanged() {
            await this.loadData();
        },

        emptyForm() {
            return {
                id: null,
                num_compte: "",
                libelle: "",
                classe: 4,
                type_compte_detail: "",
                est_compte_tiers: false,
                est_rapprochable: false,
            };
        },

        debounceSearch() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => this.loadData(), 350);
        },

        async loadData() {
            this.isLoading = true;
            try {
                const { data } = await get(
                    `/accounting/parametres/plan-comptable/all?${this.queryParams()}`
                );
                if (data.status === "success") {
                    this.comptes = data.comptes || [];
                    this.classes = data.classes || {};
                }
            } finally {
                this.isLoading = false;
            }
        },

        openForm() {
            this.form = this.emptyForm();
            if (this.filtreClasse) this.form.classe = this.filtreClasse;
            new bootstrap.Modal(document.getElementById("modal_compte")).show();
        },

        editCompte(c) {
            this.form = {
                id: c.id,
                num_compte: c.num_compte,
                libelle: c.libelle,
                classe: c.classe,
                type_compte_detail: c.type_compte_detail || "",
                est_compte_tiers: !!c.est_compte_tiers,
                est_rapprochable: !!c.est_rapprochable,
            };
            new bootstrap.Modal(document.getElementById("modal_compte")).show();
        },

        async saveCompte() {
            this.isLoading = true;
            const { data } = await postJson("/accounting/parametres/plan-comptable/save", this.form);
            this.isLoading = false;
            if (!this.handleResponse(data)) return;
            bootstrap.Modal.getInstance(document.getElementById("modal_compte"))?.hide();
            this.loadData();
        },

        // --- Import Methods ---
        openImportModal() {
            this.importFile = null;
            this.isImporting = false;
            this.isParsing = false;
            new bootstrap.Modal(document.getElementById("modal_import")).show();
        },

        handleDrop(e) {
            this.isDragging = false;
            const file = e.dataTransfer.files[0];
            this.validateAndSetFile(file);
        },

        handleFileSelect(e) {
            const file = e.target.files[0];
            this.validateAndSetFile(file);
        },

        async validateAndSetFile(file) {
            if (!file) return;
            const ext = file.name.split('.').pop().toLowerCase();
            if (!['xlsx', 'xls'].includes(ext)) {
                Swal.fire('Format invalide', 'Veuillez sélectionner un fichier Excel (.xlsx ou .xls)', 'error');
                return;
            }
            this.importFile = file;

            // Logic to detect class from the first digit of the first account number
            this.isParsing = true;
            try {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[firstSheetName];
                    const json = XLSX.utils.sheet_to_json(worksheet);

                    if (json.length > 0) {
                        // Find column that looks like NUMERO
                        const firstRow = json[0];
                        const numeroKey = Object.keys(firstRow).find(k => k.toUpperCase().includes('NUMERO'));
                        if (numeroKey) {
                            const numero = String(firstRow[numeroKey]).trim();
                            if (numero.length > 0) {
                                const firstDigit = parseInt(numero.charAt(0));
                                if (firstDigit >= 1 && firstDigit <= 9) {
                                    this.importClasse = firstDigit;
                                }
                            }
                        }
                    }
                    this.isParsing = false;
                };
                reader.readAsArrayBuffer(file);
            } catch (err) {
                console.error("Excel parsing error", err);
                this.isParsing = false;
            }
        },

        async processImport() {
            if (!this.importFile) return;

            this.isImporting = true;
            const formData = new FormData();
            formData.append('file', this.importFile);
            formData.append('classe', this.importClasse);

            try {
                const { data } = await post("/accounting/parametres/plan-comptable/import", formData);

                if (data.status === "success") {
                    Swal.fire('Importation réussie', data.message, 'success');
                    bootstrap.Modal.getInstance(document.getElementById("modal_import"))?.hide();
                    this.loadData();
                } else {
                    this.handleResponse(data);
                }
            } catch (error) {
                Swal.fire('Erreur', "Une erreur est survenue lors de l'importation.", 'error');
            } finally {
                this.isImporting = false;
            }
        },
        // --- End Import Methods ---

        indentClass(num) {
            const len = String(num).length;
            if (len <= 2) return 'fw-bold text-dark';
            if (len === 3) return 'ps-3 fw-semibold text-secondary';
            return 'ps-4 text-muted small';
        },

        typeBadgeClass(classe) {
            const c = parseInt(classe);
            if (c <= 5) return 'bg-soft-primary text-primary'; // Bilan
            if (c <= 7) return 'bg-soft-warning text-warning'; // Gestion
            return 'bg-soft-secondary text-secondary'; // Autres
        }
    },
});
