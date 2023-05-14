<?php declare(strict_types=1);

namespace Composite\Sync\Helpers;

use Composer\Autoload\ClassLoader;

class ClassHelper
{
    public static function extractNamespace(string $name): string
    {
        return ($pos = strrpos($name, '\\')) ? substr($name, 0, $pos) : '';
    }

    public static function extractShortName(string $name): string
    {
        return ($pos = strrpos($name, '\\')) === false
            ? $name
            : substr($name, $pos + 1);
    }

    public static function normalizeString(string $input): string
    {
        $input = trim($input);
        if ($input === '') {
            return '';
        }
        $numberWords = [
            '0' => 'zero',
            '1' => 'one',
            '2' => 'two',
            '3' => 'three',
            '4' => 'four',
            '5' => 'five',
            '6' => 'six',
            '7' => 'seven',
            '8' => 'eight',
            '9' => 'nine'
        ];

        $output = preg_replace_callback('/[^a-zA-Z0-9_]/u', function ($matches) {
            $char = $matches[0];
            $ascii = ord($char);
            if ($ascii >= 32 && $ascii <= 127) {
                return '_';
            }
            $trans = @strtr(
                $char,
                "ÀÁÂÃÄÅàáâãäåĀāĂăĄąÇçĆćĈĉĊċČčĎďĐđÈÉÊËèéêëĒēĔĕĖėĘęĚěĜĝĞğĠġĢģĤĥĦħÌÍÎÏìíîïĨĩĪīĬĭĮįİıĲĳĴĵĶķĸĹĺĻļĽľĿŀŁłÑñŃńŅņŇňŉŊŋÖöÒÓÔÕØòóôõöøŌōŎŏŐőŒœŔŕŖŗŘřŚśŜŝŞşŠšŢţŤťŦŧùúûüŨũŪūŬŭŮůŰűŲųŴŵŶŷÿŸŹźŻżŽžАаБбВвГгДдЕеЁёЖжЗзИиЙйКкЛлМмНнОоПпРрСсТтУуФфХхЦцЧчШшЩщъыьЭэЮюЯя",
                "AAAAAAaaaaaaAaAaAaCcCcCcCcCcCcDdDdEEEEeeeeEeEeEeEeEeGgGgGgGgHhHhIIIIiiiiIiIiIiIiIiIiIjIjJjKkkLlLlLlLlLlllNnNnNnNnnNnOoOOOOOOooooooOoOoOoOEoeRrRrRrSsSsSsSsTtTtTtUuuuUuUuUuUuUuUuUuWwYyyYyZzZzZzAaBbVvGgDdEeEeZzIiIiKkLlMmNnOoPpRrSsTtUuFfHhCcCcSsSsYyYyEeYyYy"
            );

            if ($trans !== false && $trans !== $char) {
                return $trans;
            }
            return '_';
        }, $input);

        $firstChar = substr($output, 0, 1);

        if (isset($numberWords[$firstChar])) {
            $output = $numberWords[$firstChar] . substr($output, 1);
        }
        $output = preg_replace('/_{2,}/', '_', strtolower($output));
        return trim($output, '_');
    }

    public static function getClassFilePath(string $class): string
    {
        $class = trim($class, '\\');
        $namespaceParts = explode('\\', $class);

        $loaders = ClassLoader::getRegisteredLoaders();
        $matchedPrefixes = $matchedDirs = [];
        foreach ($loaders as $loader) {
            foreach ($loader->getPrefixesPsr4() as $prefix => $dir) {
                $prefixParts = explode('\\', trim($prefix, '\\'));
                foreach ($namespaceParts as $i => $namespacePart) {
                    if (!isset($prefixParts[$i]) || $prefixParts[$i] !== $namespacePart) {
                        break;
                    }
                    if (!isset($matchedPrefixes[$prefix])) {
                        $matchedPrefixes[$prefix] = 0;
                        $matchedDirs[$prefix] = $dir;
                    }
                    $matchedPrefixes[$prefix] += 1;
                }
            }
        }
        if (empty($matchedPrefixes)) {
            throw new \Exception("Failed to determine directory for class `$class` from psr4 autoloading");
        }
        arsort($matchedPrefixes);
        $prefix = key($matchedPrefixes);
        $dirs = $matchedDirs[$prefix];

        $namespaceParts = explode('\\', str_replace($prefix, '', $class));
        $filename = array_pop($namespaceParts) . '.php';

        $relativeDir = implode(
            DIRECTORY_SEPARATOR,
            array_merge(
                $dirs,
                $namespaceParts,
            )
        );
        if (!$realDir = realpath($relativeDir)) {
            $dirCreateResult = mkdir($relativeDir, 0755, true);
            if (!$dirCreateResult) {
                throw new \Exception("Directory `$relativeDir` not exists and failed to create it, please create it manually.");
            }
            $realDir = realpath($relativeDir);
        }
        return $realDir . DIRECTORY_SEPARATOR . $filename;
    }
}