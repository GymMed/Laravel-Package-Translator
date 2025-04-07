<?php

namespace GymMed\LaravelPackageTranslator;

use Exception;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Illuminate\Support\Facades\File;

class LaravelPackageTranslator
{
    protected static $langPath = "/src/Resources/lang/";
    protected static $phpExtension = '.php';
    protected $packageName = '';

    public function uncommentTranslation($languageCodeAndFile)
    {
        $fullLangPath = $this->getPackagePath() . self::$langPath;

        $fullTranslationPath = $fullLangPath . $languageCodeAndFile . self::$phpExtension;

        $translationHandle = fopen($fullTranslationPath, "r");
        $tempFile = tempnam(sys_get_temp_dir(), 'temp_translation_');
        $tempHandle = fopen($tempFile, "w");

        if (!$translationHandle) {
            throw new Exception("Couldn't open directory: {$fullTranslationPath} . Make sure it exists!");
        }

        $pattern = "~(\"(?:\\\\.|[^\"])*\""         # double-quoted strings
            . "| '(?:\\\\.|[^\'\\\\])*'"            # single-quoted strings
            . "| `(?:\\\\.|[^`\\\\])*`"             # backtick strings
            . "| //.*?$"                            # single-line comments (//)
            . "| \#.*?$"                            # single-line comments (#)
            . "| /\*[\s\S]*?\*/"                    # multi-line comments
            . ")~xm";


        while (($line = fgets($translationHandle)) !== false) {
            $newLine = preg_replace_callback($pattern, function ($matches) {
                // If it's a comment, remove it
                if (strpos($matches[0], '//') === 0 || strpos($matches[0], '#') === 0 || strpos($matches[0], '/*') === 0) {
                    return '';
                }
                // Otherwise, return the match as is (e.g., strings or other code)
                return $matches[0];
            }, $line);
            fwrite($tempHandle, $newLine);
        }
        fclose($translationHandle);
        fclose($tempHandle);

        rename($tempFile, $fullTranslationPath);
    }

    public function translatePackage($pathFrom, $pathTo, $withComments = true)
    {
        //self::$langPath = base_path("packages/LikimoZinia/MagicShop/src/Resources/lang/");
        $fullLangPath = $this->getPackagePath() . self::$langPath;
        $fromInformation = new PackageLanguageInformation($pathFrom);
        $toInformation = new PackageLanguageInformation($pathTo);

        $fullFromPath = $fullLangPath . $pathFrom . self::$phpExtension;
        $fullToPath = $fullLangPath . $pathTo . self::$phpExtension;

        $inputHandle = fopen($fullFromPath, "r");

        if (!$inputHandle) {
            throw new Exception("Couldn't open directory: {$fullFromPath} . Make sure it exists!");
        }

        if (!File::exists($fullToPath)) {
            $directoryPath = dirname($fullToPath);

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0755, true);
            }

            File::put($fullToPath, '');
        }

        $outputHandle = fopen($fullToPath, "w");

        if (!$outputHandle) {
            throw new Exception("Couldn't open directory: {$fullToPath} . Make sure it exists!");
        }

        $pattern = '/(?P<key>[\'"`](.*?(?<!\\\\))[\'"`])(?P<arrayEquals>\s*=>\s*)(?P<value>([`"\'])(.*?(?<!\\\\))\5)[ \t]*(?P<comma>,?)[ \t]*/';

        $translator = new GoogleTranslate();
        $translator->setSource($fromInformation->getLanguageCode());
        $translator->setTarget($toInformation->getLanguageCode());
        $pregCallbackFunction = '';

        if ($withComments) {
            $pregCallbackFunction = function ($matches) use (&$translator) {
                return self::translateAndReplaceCallbackWithComments($matches, $translator);
            };
        } else {
            $pregCallbackFunction =  function ($matches) use (&$translator) {
                return self::translateAndReplaceCallback($matches, $translator);
            };
        }

        while (($line = fgets($inputHandle)) !== false) {
            $newLine = preg_replace_callback($pattern, $pregCallbackFunction, $line);

            fwrite($outputHandle, $newLine);
        }

        fclose($inputHandle);
        fclose($outputHandle);
    }

    public static function translateAndReplaceCallbackWithComments($matches, &$translator)
    {
        if (!isset($matches['key'], $matches['arrayEquals'], $matches['value'])) {
            return null;
        }
        $key = $matches['key'];
        $arrayEquals = $matches['arrayEquals'];
        $value = $matches['value'];
        $quotesType = $value[0];

        $valueWithoutQuotes = substr($value, 1, -1);
        list($valueWithPlaceholders, $placeholderMapping) = self::replaceVariablesWithPlaceholders($valueWithoutQuotes);
        $translatedValueWithPlaceholders = $translator->translate($valueWithPlaceholders);
        $finalTranslatedValue = self::restoreVariables($translatedValueWithPlaceholders, $placeholderMapping);

        $newValue = "{$quotesType}{$finalTranslatedValue}{$quotesType}, /* {$valueWithoutQuotes} */";

        return $key . $arrayEquals . $newValue;
    }

    public static function translateAndReplaceCallback($matches, &$translator)
    {
        if (!isset($matches['key'], $matches['arrayEquals'], $matches['value'])) {
            return null;
        }
        $key = $matches['key'];
        $arrayEquals = $matches['arrayEquals'];
        $value = $matches['value'];
        $quotesType = $value[0];

        list($valueWithPlaceholders, $placeholderMapping) = self::replaceVariablesWithPlaceholders(substr($value, 1, -1));
        $translatedValueWithPlaceholders = $translator->translate($valueWithPlaceholders);
        $finalTranslatedValue = self::restoreVariables($translatedValueWithPlaceholders, $placeholderMapping);

        $newValue = "{$quotesType}{$finalTranslatedValue}{$quotesType},";

        return $key . $arrayEquals . $newValue;
    }

    public static function replaceVariablesWithPlaceholders($value)
    {
        $variablePattern = '/:\w+/';

        preg_match_all($variablePattern, $value, $matches);

        $variables = $matches[0];  // Array of variables (e.g., [":var1", ":var2", ":varX"])
        $placeholderMapping = [];  // To store the placeholder-to-variable mapping

        foreach ($variables as $index => $variable) {
            $placeholder = '_' . ($index + 1) . '_';
            $value = str_replace($variable, $placeholder, $value);
            $placeholderMapping[$placeholder] = $variable;
        }

        return [$value, $placeholderMapping];
    }

    public static function restoreVariables($translatedValue, $placeholderMapping)
    {
        // Replace each placeholder (e.g., _1_, _2_) with the original variable
        foreach ($placeholderMapping as $placeholder => $originalVariable) {
            //translator usually removes spaces after translating
            $originalVariableWithSpaces = ' ' . $originalVariable . ' ';
            $translatedValue = preg_replace('/\s*' . preg_quote($placeholder, '/') . '\s*/', $originalVariableWithSpaces, $translatedValue);
        }
        return $translatedValue;
    }

    public static function getLanguagePathInPackage()
    {
        return self::$langPath;
    }

    public function setPackageName($packageName)
    {
        $this->packageName = $packageName;
    }

    public function getPackagePath()
    {
        return base_path('packages/' . $this->packageName);
    }
}
