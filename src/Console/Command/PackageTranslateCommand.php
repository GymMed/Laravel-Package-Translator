<?php

namespace GymMed\LaravelPackageTranslator\Console\Command;

use Illuminate\Filesystem\Filesystem;
use GymMed\LaravelPackageTranslator\Console\Command\MakeCommand;
use GymMed\LaravelPackageTranslator\LaravelPackageTranslator;
use Illuminate\Support\Facades\File;
use Exception;

class PackageTranslateCommand extends MakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package-translator:translate {package} {translateFrom} {translateTo} {--comments} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new translated file.';

    public function handle()
    {
        try {
            $laravelTranslator = new LaravelPackageTranslator();

            $laravelTranslator->setPackageName($this->argument('package'));
            $fullLangPath = $laravelTranslator->getPackagePath() . LaravelPackageTranslator::getLanguagePathInPackage();

            if (!File::exists($laravelTranslator->getPackagePath())) {
                $this->error("Provided path to package: [{$fullLangPath}] doesn't exist!");
                return;
            }

            $phpExtension = '.php';
            $fullFromPath = $fullLangPath . $this->argument('translateFrom') . $phpExtension;

            if (!File::exists($fullFromPath)) {
                $this->error("Provided path to language document: [{$fullFromPath}] doesn't exist!");
                return;
            }

            $fullToPath = $fullLangPath . $this->argument('translateTo') . $phpExtension;

            if (File::exists($fullToPath) && !$this->option('force')) {
                $this->comment("Provided path to translated language document: [{$fullToPath}] already exist! Use --force option to overwrite it!");
                return;
            }

            $this->comment("Translating . . .");
            $laravelTranslator->translatePackage($this->argument('translateFrom'), $this->argument('translateTo'), $this->option('comments'));
            $this->info("Successfully translated document: [{$fullToPath}]");
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
