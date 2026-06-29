<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::share('authIsSuperadmin', (bool) session('auth_is_superadmin', false));

        Blade::directive('rupiah', function ($expression) {
            return "<?php echo 'Rp. ' . number_format($expression,0,',','.'); ?>";
        });

        View::composer('layouts.cashless', function ($view) {
            $namaKantin = session('user.kantin', '');
            if ($namaKantin === '' && session('user.username')) {
                try {
                    $result = DB::connection('DATA_MYSQL')
                        ->table('sm_kantin')
                        ->where('username', session('user.username'))
                        ->first();
                    if ($result) {
                        $namaKantin = $result->NamaKantin ?? '';
                    }
                } catch (\Exception $e) {
                    $namaKantin = '';
                }
            }
            $view->with('namaKantin', $namaKantin);
        });
    }
}
