<?php declare(strict_types=1);

namespace Composite\Sync\Migration;

use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;

class MigrationClassBuilder
{
    public readonly string $className;
    public function __construct(
        private readonly string $connectionName,
        private readonly array $summaryParts,
        private readonly array $upQueries,
        private readonly array $downQueries,
    ) {
        $this->className = MigrationsManager::buildMigrationName($this->connectionName, $this->summaryParts);
    }

    public function getFileContent(): string
    {
        $fullClassName = MigrationsManager::getMigrationFullClassName($this->className);

        $file = new PhpFile();
        $class = $file
            ->setStrictTypes()
            ->addClass($fullClassName)
            ->setExtends(AbstractMigration::class);

        $class
            ->addConstant('CONNECTION_NAME', $this->connectionName)
            ->setPublic();

        $class->setMethods([
            $this->generateMethod('up', $this->upQueries),
            $this->generateMethod('down', $this->downQueries),
        ]);
        return (string)$file;
    }

    public function save(): string
    {
        $content = $this->getFileContent();
        $filePath = MigrationsManager::getMigrationsDirectory() . $this->className . '.php';
        if (!file_put_contents($filePath, $content)) {
            throw new \Exception("Failed to save `{$filePath}`, please check write permissions");
        }
        return $filePath;
    }

    protected function generateMethod(string $name, array $queries): ?Method
    {
        $bodyLines = [];
        foreach ($queries as $query) {
            if (!str_starts_with($query, ' ') && !str_starts_with($query, "\t")) {
                $query = "\t" . $query;
            }
            $bodyLines[] = "\$this->query(\"\n{$query}\n\");";
        }
        $body = implode("\n", $bodyLines);;
        return (new Method($name))
            ->setPublic()
            ->setReturnType('void')
            ->setBody($body);
    }
}