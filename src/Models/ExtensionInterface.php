<?php

namespace Extension\Models;

interface ExtensionInterface
{
    public function getName(): string;

    public function isCore(): bool;

    /**
     * @return array<int,string>
     */
    public function getClasses(): array;

    /**
     * @return array<int,string>
     */
    public function getConstants(): array;

    /**
     * @return array<int,string>
     */
    public function getFunctions(): array;
}
