<?php declare(strict_types=1);

namespace Composite\Sync\Generator;

use Composite\DB\AbstractTable;
use Composite\DB\TableConfig;
use Composite\Entity\Columns\AbstractColumn;
use Composite\Sync\Helpers\ClassHelper;
use Nette\PhpGenerator\Method;

class TableClassBuilder extends AbstractTableClassBuilder
{
    public function getParentNamespace(): string
    {
        return AbstractTable::class;
    }

    public function generate(): void
    {
        $this->file
            ->setStrictTypes()
            ->addNamespace(ClassHelper::extractNamespace($this->tableClass))
            ->addUse(AbstractTable::class)
            ->addUse(TableConfig::class)
            ->addUse($this->schema->class)
            ->addClass(ClassHelper::extractShortName($this->tableClass))
            ->setExtends(AbstractTable::class)
            ->setMethods($this->getMethods());
    }

    private function getMethods(): array
    {
        return array_filter([
            $this->generateGetConfig(),
            $this->generateFindOne(),
            $this->generateFindAll(),
            $this->generateCountAll(),
        ]);
    }

    protected function generateFindOne(): ?Method
    {
        $primaryColumns = array_map(
            fn(string $key): AbstractColumn => $this->schema->getColumn($key) ?? throw new \Exception("Primary key column `$key` not found in entity."),
            $this->tableConfig->primaryKeys
        );
        if (count($this->tableConfig->primaryKeys) === 1) {
            $body = 'return $this->_findByPk(' . $this->buildVarsList($this->tableConfig->primaryKeys) . ');';
        } else {
            $body = 'return $this->_findOne(' . $this->buildVarsList($this->tableConfig->primaryKeys) . ');';
        }
        $method = (new Method('findByPk'))
            ->setPublic()
            ->setReturnType($this->schema->class)
            ->setReturnNullable()
            ->setBody($body);
        $this->addMethodParameters($method, $primaryColumns);
        return $method;
    }

    protected function generateFindAll(): Method
    {
        return (new Method('findAll'))
            ->setPublic()
            ->setComment('@return ' . $this->entityClassShortName . '[]')
            ->setReturnType('array')
            ->setBody('return $this->_findAll();');
    }

    protected function generateCountAll(): Method
    {
        return (new Method('countAll'))
            ->setPublic()
            ->setReturnType('int')
            ->setBody('return $this->_countAll();');
    }
}