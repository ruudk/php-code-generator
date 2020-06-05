<?php

declare(strict_types=1);

namespace Murtukov\PHPCodeGenerator;

use Murtukov\PHPCodeGenerator\OOP\PhpClass;
use function dirname;
use function file_put_contents;
use function implode;
use function ksort;
use function mkdir;

class PhpFile extends DependencyAwareGenerator
{
    protected string   $namespace = '';
    protected string   $name;
    protected ?Comment $comment;

    /** @var PhpClass[] */
    protected array $classes = [];

    /** @var string[] */
    protected array $declares = [];


    public function __construct(string $name = '')
    {
        $this->name = $name;
        $this->dependencyAwareChildren = [&$this->classes];
    }

    public static function new(string $name = ''): self
    {
        return new self($name);
    }

    public function generate(): string
    {
        $namespace = $this->namespace ? "\nnamespace $this->namespace;\n" : '';
        $classes = implode("\n\n", $this->classes);

        return <<<CODE
        <?php
        $namespace{$this->buildUseStatements()}
        $classes
        CODE;
    }

    public function __toString(): string
    {
        return $this->generate();
    }

    public function addClass(PhpClass $class): self
    {
        $this->classes[] = $class;

        return $this;
    }

    public function createClass(string $name): PhpClass
    {
        return $this->classes[] = new PhpClass($name);
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function buildUseStatements(): string
    {
        $code = '';

        $paths = $this->getUsePaths();

        if (empty($paths)) {
            return $code;
        }

        if (!empty(ksort($paths))) {
            $code = "\n";

            foreach ($paths as $path => $aliases) {
                $code .= "use $path";

                if ($aliases) {
                    $code .= " as $aliases";
                }

                $code .= ";\n";
            }
        }

        return $code;
    }

    public function getComment(): Comment
    {
        return $this->comment;
    }

    public function setComment(Comment $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function createComment(string $text): Comment
    {
        return $this->comment = Comment::block($text);
    }

    public function addComment(string $text): self
    {
        $this->comment = Comment::block($text);

        return $this;
    }

    /**
     * @return false|int
     */
    public function save(string $path, int $mask = 0777)
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, $mask, true);
        }

        return file_put_contents($path, $this);
    }
}
