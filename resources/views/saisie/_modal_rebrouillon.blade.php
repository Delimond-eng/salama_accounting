{{-- Modal confirmation mot de passe — remise en brouillon d'une écriture validée --}}
<div class="modal fade" id="modal_rebrouillon" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning py-3">
                <h5 class="modal-title fw-bold"><i class="ti ti-lock me-2"></i>Remettre en brouillon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form @submit.prevent="confirmerRebrouillon">
                <div class="modal-body p-4">
                    <div class="alert alert-warning mb-3 fs-13">
                        <strong>Attention :</strong> l'écriture
                        <span class="fw-bold" v-if="rebrouillonCible">@{{ rebrouillonCible.num_piece }}</span>
                        repassera en <strong>brouillon</strong> et pourra être modifiée ou supprimée.
                    </div>
                    <p class="text-muted fs-13 mb-3" v-if="rebrouillonCible">
                        @{{ rebrouillonCible.libelle }}
                    </p>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Confirmez avec votre mot de passe *</label>
                        <input type="password" class="form-control" v-model="rebrouillonPassword"
                               ref="rebrouillonPasswordInput" required autocomplete="current-password"
                               placeholder="Mot de passe de connexion">
                    </div>
                    <p v-if="rebrouillonError" class="text-danger fs-12 mt-2 mb-0">@{{ rebrouillonError }}</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning" :disabled="rebrouillonLoading || !rebrouillonPassword">
                        <span v-if="rebrouillonLoading">Traitement…</span>
                        <span v-else><i class="ti ti-arrow-back-up me-1"></i>Remettre en brouillon</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
