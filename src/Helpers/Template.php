<?php declare(strict_types=1);

namespace Composite\Sync\Helpers;

class Template
{
    public static function render(string $filePath, array $vars = []): string
    {
        //if file path is relative - try to use caller as a base file path
        if (!str_starts_with($filePath, DIRECTORY_SEPARATOR)) {
            $basePath = dirname(debug_backtrace()[0]['file']);
            if (!str_starts_with($filePath, DIRECTORY_SEPARATOR)) {
                $basePath .= DIRECTORY_SEPARATOR;
            }
            $filePath = $basePath . $filePath;
        }
        if (!str_ends_with($filePath, '.php')) {
            $filePath .= '.php';
        }
        if (!file_exists($filePath)) {
            throw new \Exception("File `$filePath` not found");
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        include $filePath;
        return ob_get_clean();
    }
}