<?php

namespace Extension\Models;

class Extension implements ExtensionInterface
{
    /**
     * @param array<int,string> $classes
     * @param array<int,string> $constants
     * @param array<int,string> $functions
     */
    public function __construct(
        protected string $name,
        protected bool $isCore,
        protected array $classes,
        protected array $constants,
        protected array $functions,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isCore(): bool
    {
        return $this->isCore;
    }

    /**
     * @return array<int,string>
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * @return array<int,string>
     */
    public function getConstants(): array
    {
        return $this->constants;
    }

    /**
     * @return array<int,string>
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }
}
