<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Societe extends Model
{
    use SoftDeletes;

    protected $table = 'societes';

    protected $fillable = [
        'code', 'raison_sociale', 'forme_juridique', 'sigle', 'adresse', 'ville', 'pays',
        'telephone', 'email', 'site_web', 'rccm', 'num_contribuable', 'identification_nationale',
        'num_cnps', 'regime_fiscal', 'devise_principale', 'logo_path', 'statut', 'parametres',
    ];

    protected $casts = ['parametres' => 'array'];

    protected $appends = ['logo_url'];

    public function exercices(): HasMany
    {
        return $this->hasMany(Exercice::class)->orderByDesc('annee');
    }

    public function journaux(): HasMany
    {
        return $this->hasMany(Journal::class);
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(Tiers::class);
    }

    public function banques(): HasMany
    {
        return $this->hasMany(SocieteBanque::class)->orderBy('ordre');
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'active');
    }

    public function getLogoUrlAttribute(): ?string
    {
        $path = trim($this->logo_path ?? '');
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'logos/')) {
            return asset($path);
        }

        return asset('storage/'.$path);
    }
}
