<?php

namespace GymMed\LaravelPackageTranslator\Providers;

use GymMed\LaravelPackageTranslator\Console\Command\PackageTranslateCommand;
use GymMed\LaravelPackageTranslator\Console\Command\TranslationUncommentCommand;
use Illuminate\Support\ServiceProvider;

class LaravelPackageTranslatorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void {}

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerCommands();
    }

    /**
     * Register the console commands of this package
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PackageTranslateCommand::class,
                TranslationUncommentCommand::class,
            ]);
        }
    }
}
