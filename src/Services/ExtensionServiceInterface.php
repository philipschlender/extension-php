<?php

namespace Extension\Services;

use Extension\Exceptions\ExtensionException;
use Extension\Models\ExtensionInterface;

interface ExtensionServiceInterface
{
    /**
     * @return array<int,ExtensionInterface>
     *
     * @throws ExtensionException
     */
    public function getExtensions(): array;

    /**
     * @param array<int,ExtensionInterface> $extensions
     *
     * @return array<int,ExtensionInterface>
     *
     * @throws ExtensionException
     */
    public function getUsedExtensions(array $extensions, string $path): array;
}
