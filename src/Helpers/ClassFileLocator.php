<?php declare(strict_types=1);

namespace Composite\Sync\Helpers;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

class ClassFileLocator extends NodeVisitorAbstract
{
    /** @var array<class-string> */
    private array $classes;

    /** @var class-string */
    private string $parentClass;

    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Class_) {
            if (!$node->namespacedName) {
                return;
            }
            if ($this->getParentClassName($node) !== $this->parentClass) {
                return;
            }
            $this->classes[] = $node->namespacedName->toString();
        }
    }

    /**
     * @template T
     * @param class-string<T> $parentClass
     * @return array<class-string<T>>
     */
    public function findClasses(string $directory, string $parentClass): array
    {
        $this->parentClass = $parentClass;
        $this->classes = [];

        $parser = (new ParserFactory)->createForHostVersion();
        $traverser = new NodeTraverser();

        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($this);

        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        foreach ($rii as $file) {
            if (!$file->isDir() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'php') {
                $code = file_get_contents($file->getPathname());

                try {
                    $stmts = $parser->parse($code);
                    if ($stmts !== null) {
                        $traverser->traverse($stmts);
                    }
                } catch (Error) {}
            }
        }
        return $this->classes;
    }

    private function getParentClassName(Node\Stmt\Class_ $classNode): ?string
    {
        if (!$extendsNode = $classNode->extends) {
            return null;
        }
        if (!$extendsNode instanceof FullyQualified) {
            return null;
        }
        return $extendsNode->toString();
    }
}