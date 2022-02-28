<?php
namespace Codificar\Finance;
use Illuminate\Support\ServiceProvider;

class FinanceServiceProvider extends ServiceProvider {

    public function boot()
    {

        // Load routes (carrega as rotas)
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/routes/postbackBillet.php');
        $this->loadRoutesFrom(__DIR__.'/routes/postbackPix.php');

        // Load laravel views (Carregas as views do Laravel, blade)
        $this->loadViewsFrom(__DIR__.'/resources/views', 'finance');

        // Load Migrations (Carrega todas as migrations)
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations');

        // Load trans files (Carrega tos arquivos de traducao) 
        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'financeTrans');

        // Publish the VueJS files inside public folder of main project (Copia os arquivos do vue minificados dessa biblioteca para pasta public do projeto que instalar essa lib)
        $this->publishes([
            __DIR__.'/../public/js' => public_path('vendor/codificar/finance'),
            __DIR__.'/../public/files' => public_path('vendor/codificar/finance')
        ], 'public_vuejs_libs');

        // Load seeds
        $this->publishes([
            __DIR__.'/Database/seeders' => database_path('seeders')
        ], 'public_vuejs_libs');

    }

    public function register()
    {

    }
}
?>