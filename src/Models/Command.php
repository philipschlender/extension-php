<?php

namespace Extension\Models;

use Cli\Enumerations\ArgumentType;
use Cli\Enumerations\OptionType;
use Cli\Exceptions\CliException;
use Cli\Models\Argument;
use Cli\Models\Command as BaseCommand;
use Cli\Models\Option;
use Cli\Services\InputServiceInterface;
use Extension\Exceptions\ExtensionException;
use Extension\Services\ExtensionServiceInterface;
use Io\Services\OutputServiceInterface;

class Command extends BaseCommand
{
    /**
     * @throws CliException
     */
    public function __construct(
        InputServiceInterface $inputService,
        protected OutputServiceInterface $standardOutputService,
        protected OutputServiceInterface $standardErrorService,
        protected ExtensionServiceInterface $extensionService,
    ) {
        $options = [
            new Option('help', 'h', OptionType::NoValue),
        ];

        $arguments = [
            new Argument('path', ArgumentType::Required),
        ];

        parent::__construct($inputService, $options, $arguments);
    }

    protected function execute(): int
    {
        try {
            $helpOption = $this->inputService->getOption('help');

            if ($helpOption->isPassed()) {
                $output = <<<OUTPUT
                Usage: php ./src/script.php [OPTION]... [PATH]
                Check the extensions being used in the project.

                Options:
                  -h, --help\tdisplay this help and exit

                OUTPUT;

                $this->standardOutputService->write($output);

                return 0;
            }

            $pathArgument = $this->inputService->getArgument('path');

            if (!$pathArgument->isPassed() && ArgumentType::Required === $pathArgument->getArgumentType()) {
                throw new ExtensionException('The PATH argument is required.');
            }

            $path = rtrim($pathArgument->getValue() ?? '', '/');

            $extensions = $this->sortExtensions($this->extensionService->getExtensions());

            $usedExtensions = $this->sortExtensions($this->extensionService->getUsedExtensions($extensions, $path));

            $extensionNames = [];
            $usedExtensionNames = [];
            $usedNonCoreExtensionNames = [];

            foreach ($extensions as $extension) {
                $extensionNames[] = $extension->getName();
            }

            foreach ($usedExtensions as $usedExtension) {
                $usedExtensionNames[] = $usedExtension->getName();

                if (!$usedExtension->isCore()) {
                    $usedNonCoreExtensionNames[] = $usedExtension->getName();
                }
            }

            $this->standardOutputService->write(
                sprintf(
                    "Loaded extensions:\n%s\n\nUsed extensions:\n%s\n\nUsed extensions (non core):\n%s\n",
                    !empty($extensionNames) ? implode(', ', $extensionNames) : '-',
                    !empty($usedExtensionNames) ? implode(', ', $usedExtensionNames) : '-',
                    !empty($usedNonCoreExtensionNames) ? implode(', ', $usedNonCoreExtensionNames) : '-'
                )
            );
        } catch (\Throwable $throwable) {
            $this->standardErrorService->write(sprintf("%s\n", $throwable->getMessage()));

            return 1;
        }

        return 0;
    }

    /**
     * @param array<int,ExtensionInterface> $extensions
     *
     * @return array<int,ExtensionInterface>
     */
    protected function sortExtensions(array $extensions): array
    {
        usort($extensions, function (ExtensionInterface $a, ExtensionInterface $b) {
            return strcmp(strtolower($a->getName()), strtolower($b->getName()));
        });

        return $extensions;
    }
}
