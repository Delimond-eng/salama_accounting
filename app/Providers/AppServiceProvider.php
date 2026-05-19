<?php

namespace App\Providers;

use App\Support\SocieteContext;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        DB::statement("SET time_zone = '+01:00'");

        View::composer(['components.header', 'components.sidebar'], function ($view): void {
            $societe = SocieteContext::societe();
            $logoUrl = null;
            if ($societe?->logo_path) {
                $logoUrl = asset('storage/'.$societe->logo_path);
            }
            $view->with('appLogoUrl', $logoUrl);
        });

        Blade::directive('active', function ($expression) {
            return "<?php echo (function(\$routes){
                foreach ((array)\$routes as \$route) {
                    if (request()->routeIs(\$route)) return 'active subdrop';
                }
                return '';
            })($expression); ?>";
        });
    }
}
