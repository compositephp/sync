<?php declare(strict_types=1);

namespace Composite\Sync\Generator;

use Composite\Sync\Helpers\ClassHelper;
use Nette\PhpGenerator\EnumCase;
use Nette\PhpGenerator\PhpFile;
use Doctrine\Inflector\Rules\English\InflectorFactory;

class EnumClassBuilder
{
    public function __construct(
        private readonly string $enumClass,
        private readonly array $cases,
    ) {}

    /**
     * @throws \Exception
     */
    public function getFileContent(): string
    {
        $enumCases = [];
        foreach ($this->cases as $case) {
            $enumCases[] = new EnumCase($case);
        }
        $file = new PhpFile();
        $file
            ->setStrictTypes()
            ->addEnum($this->enumClass)
            ->setCases($enumCases);

        return (string)$file;
    }

    public static function getProposedEnumClass(string $entityClass, string $enumName): string
    {
        $enumShortClassName = ucfirst((new InflectorFactory())->build()->camelize($enumName));
        $entityNamespace = ClassHelper::extractNamespace($entityClass);
        return $entityNamespace . '\\Enums\\' . $enumShortClassName;
    }
}