import { get, postJson } from "../../modules/http.js";
import { livresMixin } from "./livres-common.js";

new Vue({
    el: "#App",
    mixins: [livresMixin],
    data() {
        return {
            lignes: [],
            search: "",
            journalId: "",
            exportBase: "/accounting/export/livres/journal",
            dtInstance: null,
        };
    },
    computed: {
        filteredLignes() {
            const q = (this.search || "").toLowerCase().trim();
            if (!q) return this.lignes;
            return this.lignes.filter(l =>
                (l.num_piece || "").toLowerCase().includes(q) ||
                (l.num_compte || "").toLowerCase().includes(q) ||
                (l.libelle_ligne || l.libelle_ecriture || "").toLowerCase().includes(q) ||
                (l.nom_tiers || "").toLowerCase().includes(q)
            );
        },
        totaux() {
            return this.filteredLignes.reduce((acc, l) => {
                acc.debit += Number(l.debit) || 0;
                acc.credit += Number(l.credit) || 0;
                return acc;
            }, { debit: 0, credit: 0 });
        }
    },
    methods: {
        async initPage() {
            await this.loadData();
        },
        async loadData() {
            this.isLoading = true;
            try {
                const { data } = await get(`/accounting/livres/journal/data?${this.queryParams()}`);
                if (!this.handleResponse(data)) {
                    return;
                }
                this.lignes = data.lignes || [];
                this.initDataTable();
            } finally {
                this.isLoading = false;
            }
        },
        initDataTable() {
            this.$nextTick(() => {
                const tableEl = document.getElementById("journal-list");
                if (!tableEl) return;

                if ($.fn.DataTable.isDataTable(tableEl)) {
                    $(tableEl).DataTable().destroy();
                }

                this.dtInstance = $(tableEl).DataTable({
                    language: {
                        url: "https://cdn.datatables.net/plug-ins/1.10.24/i18n/French.json",
                    },
                    order: [[1, "desc"]], // Sort by Date by default
                    pageLength: 50,
                    columnDefs: [
                        { targets: "no-sort", orderable: false }
                    ]
                });
            });
        },
        async deleteLigne(l) {
            const result = await Swal.fire({
                title: 'Supprimer cette ligne ?',
                text: "Cette action est irréversible !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler'
            });

            if (result.isConfirmed) {
                this.isLoading = true;
                const { data } = await postJson(`/accounting/saisie/ecritures/${l.ecriture_id}/delete`);
                this.isLoading = false;
                if (data.status === 'success') {
                    Swal.fire('Supprimé !', 'L\'écriture a été supprimée.', 'success');
                    this.loadData();
                } else {
                    this.handleResponse(data);
                }
            }
        }
    },
    watch: {
        lignes() {
            this.initDataTable();
        }
    }
});
