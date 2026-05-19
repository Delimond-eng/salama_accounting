<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class ParametreSysteme
{
    public static function get(string $cle, mixed $default = null): mixed
    {
        $valeur = DB::table('parametres_systeme')->where('cle', $cle)->value('valeur');

        return $valeur ?? $default;
    }
}
