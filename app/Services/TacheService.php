<?php

namespace App\Services;

use App\Models\Tache;
use App\Models\TacheEtape;
use App\Models\TacheFichier;
use App\Models\TacheRapport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class TacheService
{
    public function peutVoirToutes(User $user): bool
    {
        return $user->estSuperAdmin();
    }

    public function peutVoir(Tache $tache, User $user): bool
    {
        if ($this->peutVoirToutes($user)) {
            return true;
        }
        if ((int) $tache->cree_par === (int) $user->id) {
            return true;
        }

        return $tache->etapes()->where('user_id', $user->id)->exists();
    }

    public function estCreateur(Tache $tache, User $user): bool
    {
        return (int) $tache->cree_par === (int) $user->id;
    }

    public function estAssigne(Tache $tache, User $user): bool
    {
        return $tache->etapes()->where('user_id', $user->id)->exists();
    }

    public function listePourUser(int $societeId, User $user): \Illuminate\Database\Eloquent\Collection
    {
        $query = Tache::parSociete($societeId)
            ->with(['createur:id,name', 'etapes.assigne:id,name'])
            ->orderByDesc('updated_at');

        if (! $this->peutVoirToutes($user)) {
            $query->where(function ($q) use ($user): void {
                $q->where('cree_par', $user->id)
                    ->orWhereHas('etapes', fn ($e) => $e->where('user_id', $user->id));
            });
        }

        return $query->get()->map(function (Tache $t) {
            $t->setAttribute('progression', $t->progression());
            $t->setAttribute('assignes', $t->etapes->pluck('assigne.name')->unique()->filter()->values());

            return $t;
        });
    }

    public function detail(int $societeId, int $tacheId, User $user): Tache
    {
        $tache = Tache::parSociete($societeId)
            ->with([
                'createur:id,name,email',
                'etapes.assigne:id,name,email',
                'etapes.terminePar:id,name',
                'rapports.auteur:id,name',
                'rapports.fichiers',
                'fichiers.auteur:id,name',
            ])
            ->findOrFail($tacheId);

        if (! $this->peutVoir($tache, $user)) {
            throw new InvalidArgumentException('Accès refusé à cette tâche.');
        }

        $tache->setAttribute('progression', $tache->progression());
        $tache->setAttribute('peut_modifier', $this->estCreateur($tache, $user) || $user->estSuperAdmin());
        $tache->setAttribute('est_assigne', $this->estAssigne($tache, $user));

        return $tache;
    }

    /**
     * @param  array<int, array{user_id: int, libelle: string, ordre?: int}>  $etapes
     */
    public function enregistrer(int $societeId, array $data, ?int $tacheId = null): Tache
    {
        $user = Auth::user();
        if (! $user) {
            throw new InvalidArgumentException('Utilisateur non connecté.');
        }

        return DB::transaction(function () use ($societeId, $data, $tacheId, $user) {
            if ($tacheId) {
                $tache = Tache::parSociete($societeId)->findOrFail($tacheId);
                if (! $this->estCreateur($tache, $user) && ! $user->estSuperAdmin()) {
                    throw new InvalidArgumentException('Seul le créateur peut modifier la tâche.');
                }
                $tache->update([
                    'titre' => $data['titre'],
                    'description' => $data['description'] ?? null,
                    'date_echeance' => $data['date_echeance'] ?? null,
                    'statut' => $data['statut'] ?? $tache->statut,
                ]);
                $tache->etapes()->delete();
            } else {
                $tache = Tache::create([
                    'societe_id' => $societeId,
                    'titre' => $data['titre'],
                    'description' => $data['description'] ?? null,
                    'date_echeance' => $data['date_echeance'] ?? null,
                    'statut' => 'ouverte',
                    'cree_par' => $user->id,
                ]);
            }

            $ordre = 1;
            foreach ($data['etapes'] ?? [] as $e) {
                $libelle = trim((string) ($e['libelle'] ?? ''));
                if ($libelle === '' || empty($e['user_id'])) {
                    continue;
                }
                TacheEtape::create([
                    'tache_id' => $tache->id,
                    'user_id' => (int) $e['user_id'],
                    'libelle' => $libelle,
                    'ordre' => $e['ordre'] ?? $ordre++,
                ]);
            }

            $this->rafraichirStatut($tache->fresh(['etapes']));

            return $tache->fresh(['createur', 'etapes.assigne']);
        });
    }

    public function basculerEtape(int $societeId, int $etapeId, User $user): TacheEtape
    {
        $etape = TacheEtape::with('tache')->findOrFail($etapeId);
        $tache = $etape->tache;
        if ((int) $tache->societe_id !== $societeId) {
            throw new InvalidArgumentException('Tâche introuvable.');
        }
        if (! $this->peutVoir($tache, $user)) {
            throw new InvalidArgumentException('Accès refusé.');
        }
        if ((int) $etape->user_id !== (int) $user->id && ! $user->estSuperAdmin()) {
            throw new InvalidArgumentException('Vous ne pouvez cocher que vos propres étapes.');
        }

        $etape->est_terminee = ! $etape->est_terminee;
        $etape->terminee_le = $etape->est_terminee ? now() : null;
        $etape->terminee_par = $etape->est_terminee ? $user->id : null;
        $etape->save();

        $this->rafraichirStatut($tache->fresh(['etapes']));

        return $etape->fresh(['assigne']);
    }

    public function ajouterRapport(int $societeId, int $tacheId, string $contenu, User $user): TacheRapport
    {
        $tache = Tache::parSociete($societeId)->findOrFail($tacheId);
        if (! $this->estAssigne($tache, $user) && ! $user->estSuperAdmin()) {
            throw new InvalidArgumentException('Seuls les assignés peuvent rédiger un rapport.');
        }
        $contenu = trim($contenu);
        if ($contenu === '') {
            throw new InvalidArgumentException('Le rapport ne peut pas être vide.');
        }

        return TacheRapport::create([
            'tache_id' => $tache->id,
            'user_id' => $user->id,
            'contenu' => $contenu,
        ]);
    }

    public function ajouterFichier(
        int $societeId,
        int $tacheId,
        UploadedFile $file,
        User $user,
        ?int $rapportId = null
    ): TacheFichier {
        $tache = Tache::parSociete($societeId)->findOrFail($tacheId);
        if (! $this->estAssigne($tache, $user) && ! $this->estCreateur($tache, $user) && ! $user->estSuperAdmin()) {
            throw new InvalidArgumentException('Accès refusé pour joindre un fichier.');
        }

        $dir = "taches/{$societeId}/{$tache->id}";
        $nom = $file->getClientOriginalName();
        $chemin = $file->storeAs($dir, uniqid().'_'.$nom, 'local');

        return TacheFichier::create([
            'tache_id' => $tache->id,
            'rapport_id' => $rapportId,
            'user_id' => $user->id,
            'chemin' => $chemin,
            'nom_fichier' => $nom,
            'mime' => $file->getClientMimeType(),
            'taille' => $file->getSize(),
        ]);
    }

    public function telechargerFichier(int $societeId, int $fichierId, User $user): array
    {
        $fichier = TacheFichier::with('tache')->findOrFail($fichierId);
        if ((int) $fichier->tache->societe_id !== $societeId || ! $this->peutVoir($fichier->tache, $user)) {
            throw new InvalidArgumentException('Fichier introuvable.');
        }
        if (! Storage::disk('local')->exists($fichier->chemin)) {
            throw new InvalidArgumentException('Fichier absent du stockage.');
        }

        return [
            'path' => Storage::disk('local')->path($fichier->chemin),
            'nom' => $fichier->nom_fichier,
            'mime' => $fichier->mime,
        ];
    }

    protected function rafraichirStatut(Tache $tache): void
    {
        $prog = $tache->progression();
        if ($prog['total'] === 0) {
            return;
        }
        if ($prog['pourcent'] >= 100) {
            $tache->update(['statut' => 'terminee']);
        } elseif ($prog['faites'] > 0) {
            $tache->update(['statut' => 'en_cours']);
        } else {
            $tache->update(['statut' => 'ouverte']);
        }
    }
}
