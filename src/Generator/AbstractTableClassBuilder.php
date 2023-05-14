<?php declare(strict_types=1);

namespace Composite\Sync\Generator;

use Composite\Sync\Helpers\ClassHelper;
use Composite\DB\TableConfig;
use Composite\Entity\Columns\AbstractColumn;
use Composite\Entity\Schema;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;

abstract class AbstractTableClassBuilder
{
    protected readonly PhpFile $file;
    protected readonly TableConfig $tableConfig;
    protected readonly Schema $schema;
    protected readonly string $entityClassShortName;

    public function __construct(
        protected readonly string $tableClass,
        protected readonly string $entityClass,
    )
    {
        /** @var class-string<\Composite\Entity\AbstractEntity> $class */
        $class = $this->entityClass;
        $this->schema = $class::schema();
        $this->tableConfig = TableConfig::fromEntitySchema($this->schema);
        $this->entityClassShortName = ClassHelper::extractShortName($this->schema->class);
        $this->file = new PhpFile();

        $this->generate();
    }

    abstract public function getParentNamespace(): string;
    abstract public function generate(): void;

    final public function getFileContent(): string
    {
        return (string)$this->file;
    }

    protected function generateGetConfig(): Method
    {
        return (new Method('getConfig'))
            ->setProtected()
            ->setReturnType(TableConfig::class)
            ->setBody('return TableConfig::fromEntitySchema(' . $this->entityClassShortName . '::schema());');
    }

    protected function buildVarsList(array $vars): string
    {
        if (count($vars) === 1) {
            $var = current($vars);
            return '$' . $var;
        }
        $vars = array_map(
            fn ($var) => "'$var' => \$" . $var,
            $vars
        );
        return '[' . implode(', ', $vars) . ']';
    }

    /**
     * @param AbstractColumn[] $columns
     */
    protected function addMethodParameters(Method $method, array $columns): void
    {
        foreach ($columns as $column) {
            $method
                ->addParameter($column->name)
                ->setType($column->type);
        }
    }
}