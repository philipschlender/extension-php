<?php

namespace Extension\Services;

use Extension\Exceptions\ExtensionException;
use Extension\Models\Extension;
use Extension\Models\ExtensionInterface;
use Fs\Exceptions\FsException;
use Fs\Services\FsServiceInterface;

class ExtensionService implements ExtensionServiceInterface
{
    public function __construct(
        protected FsServiceInterface $fsService,
    ) {
    }

    /**
     * @return array<int,ExtensionInterface>
     *
     * @throws ExtensionException
     */
    public function getExtensions(): array
    {
        $extensions = [];

        foreach ($this->getLoadedExtensions() as $name) {
            $isCore = $this->isCoreExtension($name);
            $classes = $this->getExtensionClasses($name);
            $constants = $this->getExtensionConstants($name);
            $functions = $this->getExtensionFunctions($name);

            $extensions[] = new Extension($name, $isCore, $classes, $constants, $functions);
        }

        return $extensions;
    }

    /**
     * @param array<int,ExtensionInterface> $extensions
     *
     * @return array<int,ExtensionInterface>
     *
     * @throws ExtensionException
     */
    public function getUsedExtensions(array $extensions, string $path): array
    {
        if (empty($extensions)) {
            return [];
        }

        $usedExtensions = [];

        try {
            $subPaths = $this->fsService->list($path, true);
        } catch (FsException $exception) {
            throw new ExtensionException($exception->getMessage(), 0, $exception);
        }

        foreach ($subPaths as $subPath) {
            $subPath = sprintf('%s/%s', $path, $subPath);

            if (!str_ends_with($subPath, '.php')) {
                continue;
            }

            try {
                $content = $this->fsService->readFile($subPath);
            } catch (FsException $exception) {
                throw new ExtensionException($exception->getMessage(), 0, $exception);
            }

            foreach ($extensions as $extension) {
                $types = [
                    $extension->getClasses(),
                    $extension->getConstants(),
                    $extension->getFunctions(),
                ];

                foreach ($types as $entities) {
                    foreach ($entities as $entity) {
                        if (
                            1 === preg_match(sprintf('/\b%s\b/', preg_quote($entity, '/')), $content)
                            && !in_array($extension, $usedExtensions)
                        ) {
                            $usedExtensions[] = $extension;
                        }
                    }
                }
            }
        }

        return $usedExtensions;
    }

    /**
     * @return array<int,string>
     */
    protected function getCoreExtensions(): array
    {
        return [
            'Core',
            'date',
            'filter',
            'hash',
            'json',
            'libxml',
            'pcre',
            'random',
            'readline',
            'Reflection',
            'SPL',
            'standard',
            'zlib',
        ];
    }

    /**
     * @return array<int,string>
     */
    protected function getLoadedExtensions(): array
    {
        return get_loaded_extensions();
    }

    protected function isCoreExtension(string $name): bool
    {
        return in_array($name, $this->getCoreExtensions());
    }

    /**
     * @throws ExtensionException
     */
    protected function getReflectionExtension(string $name): \ReflectionExtension
    {
        try {
            return new \ReflectionExtension($name);
        } catch (\ReflectionException $exception) {
            throw new ExtensionException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @return array<int,string>
     *
     * @throws ExtensionException
     */
    protected function getExtensionClasses(string $name): array
    {
        $reflectionExtension = $this->getReflectionExtension($name);

        $classes = [];

        foreach ($reflectionExtension->getClasses() as $class) {
            $classes[] = $class->getName();
        }

        return $classes;
    }

    /**
     * @return array<int,string>
     *
     * @throws ExtensionException
     */
    protected function getExtensionConstants(string $name): array
    {
        $reflectionExtension = $this->getReflectionExtension($name);

        $constants = [];

        foreach ($reflectionExtension->getConstants() as $constant => $value) {
            $constants[] = $constant;
        }

        return $constants;
    }

    /**
     * @return array<int,string>
     *
     * @throws ExtensionException
     */
    protected function getExtensionFunctions(string $name): array
    {
        $reflectionExtension = $this->getReflectionExtension($name);

        $functions = [];

        foreach ($reflectionExtension->getFunctions() as $function) {
            $functions[] = $function->getName();
        }

        return $functions;
    }
}
