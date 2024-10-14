<?php

namespace GymMed\LaravelPackageTranslator;

use Exception;
use Stichoza\GoogleTranslate\GoogleTranslate;

class PackageLanguageInformation
{
    protected $languageCode = "";
    protected $languageDocument = "";

    public function __construct($path)
    {
        $languageInformation = self::getLanguageInfoFromPath($path);
        $this->setLanguageCode($languageInformation[0]);
        $this->setLanguageDocument($languageInformation[1]);
    }

    public static function getLanguageInfoFromPath($path)
    {
        $pathInformation = explode('/', $path);

        if (count($pathInformation) !== 2)
            throw new Exception("Provided incorrect path:{$path} path should be languageCode/document.php for example: en/app.php");

        return $pathInformation;
    }

    public function setLanguageCode(string $languageCode)
    {
        $this->languageCode = $languageCode;
    }

    public function setLanguageDocument(string $languageDocument)
    {
        $this->languageDocument = $languageDocument;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function getLanguageDocument(): string
    {
        return $this->languageDocument;
    }
}
