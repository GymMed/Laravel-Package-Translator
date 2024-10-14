<?php

namespace GymMed\LaravelPackageTranslator\Console\Command;

use Illuminate\Filesystem\Filesystem;
use GymMed\LaravelPackageTranslator\Console\Command\MakeCommand;
use GymMed\LaravelPackageTranslator\LaravelPackageTranslator;
use Illuminate\Support\Facades\File;
use Exception;

class TranslationUncommentCommand extends MakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package-translator:uncomment {package} {languageCodeAndFile}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove comments in translated file.';

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
            $fullLangPath = $fullLangPath . $this->argument('languageCodeAndFile') . $phpExtension;

            if (!File::exists($fullLangPath)) {
                $this->error("Provided path to language document: [{$fullLangPath}] doesn't exist!");
                return;
            }

            $this->comment("Removing comments . . .");
            $laravelTranslator->uncommentTranslation($this->argument('languageCodeAndFile'));
            $this->info("Successfully removed comments from document: [{$fullLangPath}]");
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
