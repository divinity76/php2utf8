<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('memory_limit', '2G');
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
if (PHP_MAJOR_VERSION < 8) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return substr_compare($haystack, $needle, 0, strlen($needle), false) === 0;
    }
}
if (!extension_loaded('mbstring')) {
    echo "mbstring extension is not loaded\n";
    exit(1);
}
global $argc, $argv;
if ($argc !== 3) {
    echo "Usage: php php2utf8.php " . escapeshellarg('<path_to_project_dir>') . " 0" . PHP_EOL;
    echo "the last digit is 0 for simulation, 1 for actual replacement\n";
    exit(1);
}
$dir = $argv[1];
$isSimulation = $argv[2] === '1';
$files = [];
foreach ((new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS))) as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $ext = strtolower($file->getExtension());
    if (in_array($ext, array(
        'php',
        'php3',
        'php4',
        'php5',
        'php7', // sadfact, i'm involved with a legacy proprietary codebase where .php runs php5 and .php7 runs php7 x.x
    ), true)) {
        $files[] = $file->getRealPath();
    }
}
echo "Found " . count($files) . " files to scan\n";
$replacements = [];
$encodingHandlers = array(
    'UTF-8' => function (string $input, string &$output = null, array &$messageStrings = null): bool {
        $messageStrings = array();
        $output = "";
        $ret = false;
        $contents = $input;
        $utf8BOM = "\xEF\xBB\xBF";
        if (str_starts_with($contents, $utf8BOM)) {
            $contents = substr($contents, strlen($utf8BOM));
            $messageStrings[] = "Removed UTF-8 BOM";
            $ret = true;
        }
        if (preg_match("/^\s*declare\s*\(\s*encoding\s*\=\s*(?:\"|\')UTF-8(?:\"|\')\s*\)/im", $contents, $matches)) {
            $contents = preg_replace("/^\s*declare\s*\(\s*encoding\s*\=\s*(?:\"|\')UTF-8(?:\"|\')\s*\)/im", '', $contents);
            $messageStrings[] = "Removed declare(encoding='UTF-8')";
            $ret = true;
        }
        if ($ret) {
            $output = $contents;
        }
        return $ret;
    },
    'UTF16LE' => function (string $input, string &$output = null, array &$messageStrings = null): bool {
        $messageStrings = array();
        $output = "";
        $ret = false;
        $contents = $input;
        $utf16LEBOM = "\xFF\xFE";
        if (str_starts_with($contents, $utf16LEBOM)) {
            $contents = substr($contents, strlen($utf16LEBOM));
            $messageStrings[] = "Removed UTF-16 LE BOM, and convert UTF16-LE to UTF-8";
            $contents = mb_convert_encoding($contents, 'UTF-8', 'UTF-16LE');
            $ret = true;
        }
        $asUTF8 = $ret ? $contents : (string)@mb_convert_encoding($contents, 'UTF-8', 'UTF-16LE');
        if (preg_match("/^\s*declare\s*\(\s*encoding\s*\=\s*(?:\"|\')UTF-16LE(?:\"|\')\s*\)/im", $asUTF8, $matches)) {
            $contents = preg_replace("/^\s*declare\s*\(\s*encoding\s*\=\s*(?:\"|\')UTF-16LE(?:\"|\')\s*\)/im", '', $contents);
            $messageStrings[] = "Removed declare(encoding='UTF-16LE')";
            $ret = true;
        }
        if ($ret) {
            $output = $contents;
        }
        return $ret;
    },
    'UTF16BE' => function (string $input, string &$output = null, array &$messageStrings = null): bool {
        $messageStrings = array();
        $output = "";
        $ret = false;
        $contents = $input;
        $utf16BEBOM = "\xFE\xFF";
        if (str_starts_with($contents, $utf16BEBOM)) {
            $contents = substr($contents, strlen($utf16BEBOM));
            $messageStrings[] = "Removed UTF-16 BE BOM, and convert UTF16-BE to UTF-8";
            $contents = mb_convert_encoding($contents, 'UTF-8', 'UTF-16BE');
            $ret = true;
        }
        $asUTF8 = $ret ? $contents : (string)@mb_convert_encoding($contents, 'UTF-8', 'UTF-16BE');
        if (preg_match("/^\s*declare\s*\(\s*encoding\s*\=\s*(?:\"|\')UTF-16BE(?:\"|\')\s*\)/im", $asUTF8, $matches)) {
            $contents = preg_replace("/^\s*declare\s*\(\s*encoding\s*\=\s*(?:\"|\')UTF-16BE(?:\"|\')\s*\)/im", '', $contents);
            $messageStrings[] = "Removed declare(encoding='UTF-16BE')";
            $ret = true;
        }
        if ($ret) {
            $output = $contents;
        }
        return $ret;
    },
    'UTF32LE' => function (string $input, string &$output = null, array &$messageStrings = null): bool {
        $messageStrings = array();
        $output = "";
        $ret = false;
        $contents = $input;
        $utf32LEBOM = "\xFF\xFE\x00\x00";
        if (str_starts_with($contents, $utf32LEBOM)) {
            $contents = substr($contents, strlen($utf32LEBOM));
            $messageStrings[] = "Removed UTF-32 LE BOM, and convert UTF32-LE to UTF-8";
            $contents = mb_convert_encoding($contents, 'UTF-8', 'UTF-32LE');
            $ret = true;
        }
        $asUTF8 = $ret ? $contents : (string)@mb_convert_encoding($contents, 'UTF-8', 'UTF-32LE');
        if (preg_match("/^\s*declare\s*\(\s*encoding\s*\=\s*(?:\"|\')UTF-32LE(?:\"|\')\s*\)/im", $asUTF8, $matches)) {
            $contents = preg_replace("/^\s*declare\s*\(\s*encoding\s*\=\s*(?:\"|\')UTF-32LE(?:\"|\')\s*\)/im", '', $contents);
            $messageStrings[] = "Removed declare(encoding='UTF-32LE')";
            $ret = true;
        }
        if ($ret) {
            $output = $contents;
        }
        return $ret;
    },
    'UTF32BE' => function (string $input, string &$output = null, array &$messageStrings = null): bool {
        $messageStrings = array();
        $output = "";
        $ret = false;
        $contents = $input;
        $utf32BEBOM = "\x00\x00\xFE\xFF";
        if (str_starts_with($contents, $utf32BEBOM)) {
            $contents = substr($contents, strlen($utf32BEBOM));
            $messageStrings[] = "Removed UTF-32 BE BOM, and convert UTF32-BE to UTF-8";
            $contents = mb_convert_encoding($contents, 'UTF-8', 'UTF-32BE');
            $ret = true;
        }
        $asUTF8 = $ret ? $contents : (string)@mb_convert_encoding($contents, 'UTF-8', 'UTF-32BE');
        if (preg_match("/^\s*declare\s*\(\s*encoding\s*\=\s*(?:\"|\')UTF-32BE(?:\"|\')\s*\)/im", $asUTF8, $matches)) {
            $contents = preg_replace("/^\s*declare\s*\(\s*encoding\s*\=\s*(?:\"|\')UTF-32BE(?:\"|\')\s*\)/im", '', $contents);
            $messageStrings[] = "Removed declare(encoding='UTF-32BE')";
            $ret = true;
        }
        if ($ret) {
            $output = $contents;
        }
        return $ret;
    },
    'ISO-8859-1' => function (string $input, string &$output = null, array &$messageStrings = null): bool {
        $messageStrings = array();
        $output = "";
        $ret = false;
        $contents = $input;
        $asUTF8 = $contents;
        if (preg_match("/^\s*declare\s*\(\s*encoding\s*\=\s*(?:\"|\')ISO-8859-1(?:\"|\')\s*\)/im", $asUTF8, $matches)) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'ISO-8859-1');
            $contents = preg_replace("/^\s*declare\s*\(\s*encoding\s*\=\s*(?:\"|\')ISO-8859-1(?:\"|\')\s*\)/im", '', $contents);
            $messageStrings[] = "Removed declare(encoding='ISO-8859-1') and converted ISO-8859-1 to UTF-8";
            $ret = true;
        }
        if ($ret) {
            $output = $contents;
        }
        return $ret;
    },
);
foreach ($files as $fileno => $file) {
    echo ($fileno + 1) . "/" . count($files) . ": " . $file . PHP_EOL;
    $contents = file_get_contents($file);
    foreach ($encodingHandlers as $encoding => $handler) {
        $messageStrings = array();
        $output = "";
        if ($handler($contents, $output, $messageStrings)) {
            print_r($messageStrings);
            $replacements[$file] = $messageStrings;
            if (!$isSimulation) {
                file_put_contents($file, $output, LOCK_EX);
            }
            break;
        }
    }
}
print_r($replacements);
echo "Total files scanned: " . count($files) . PHP_EOL;
echo "Total files modified: " . count($replacements) . PHP_EOL;
