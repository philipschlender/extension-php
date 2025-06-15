<?php

namespace Tests;

use Cli\Enumerations\ArgumentType;
use Cli\Enumerations\OptionType;
use Cli\Models\Argument;
use Cli\Models\CommandInterface;
use Cli\Models\Option;
use Cli\Services\InputServiceInterface;
use Extension\Models\Command;
use Extension\Models\Extension;
use Extension\Services\ExtensionServiceInterface;
use Io\Services\OutputServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;

class CommandTest extends TestCase
{
    protected MockObject&InputServiceInterface $inputService;

    protected MockObject&OutputServiceInterface $standardOutputService;

    protected MockObject&OutputServiceInterface $standardErrorService;

    protected MockObject&ExtensionServiceInterface $extensionService;

    protected CommandInterface $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inputService = $this->getMockBuilder(InputServiceInterface::class)->getMock();

        $this->standardOutputService = $this->getMockBuilder(OutputServiceInterface::class)->getMock();

        $this->standardErrorService = $this->getMockBuilder(OutputServiceInterface::class)->getMock();

        $this->extensionService = $this->getMockBuilder(ExtensionServiceInterface::class)->getMock();

        $this->inputService->expects($this->once())
            ->method('initialize')
            ->with(
                $this->callback(function ($options) {
                    return !empty($options);
                }),
                $this->callback(function ($arguments) {
                    return !empty($arguments);
                })
            )
            ->willReturnSelf();

        $this->command = new Command(
            $this->inputService,
            $this->standardOutputService,
            $this->standardErrorService,
            $this->extensionService,
        );
    }

    public function testHandle(): void
    {
        $helpOption = new Option('help', 'h', OptionType::NoValue);

        $pathArgument = new Argument('path', ArgumentType::Required);
        $pathArgument->setIsPassed(true);
        $pathArgument->setValue('.');

        $extensions = [
            new Extension('Core', true, [], [], []),
            new Extension('curl', false, [], [], []),
        ];

        $usedExtensions = $extensions;

        $this->inputService->expects($this->once())
            ->method('parse');

        $this->inputService->expects($this->once())
            ->method('getOption')
            ->with('help')
            ->willReturn($helpOption);

        $this->inputService->expects($this->once())
            ->method('getArgument')
            ->with('path')
            ->willReturn($pathArgument);

        $this->extensionService->expects($this->once())
            ->method('getExtensions')
            ->willReturn($extensions);

        $this->extensionService->expects($this->once())
            ->method('getUsedExtensions')
            ->with($extensions, $pathArgument->getValue())
            ->willReturn($usedExtensions);

        $this->standardOutputService->expects($this->once())
            ->method('write')
            ->with("Loaded extensions:\nCore, curl\n\nUsed extensions:\nCore, curl\n\nUsed extensions (non core):\ncurl\n");

        $this->standardErrorService->expects($this->never())
            ->method('write');

        $this->assertEquals(0, $this->command->handle());
    }

    public function testHandleEmptyUsedExtensions(): void
    {
        $helpOption = new Option('help', 'h', OptionType::NoValue);

        $pathArgument = new Argument('path', ArgumentType::Required);
        $pathArgument->setIsPassed(true);
        $pathArgument->setValue('.');

        $extensions = [
            new Extension('Core', true, [], [], []),
            new Extension('curl', false, [], [], []),
        ];

        $usedExtensions = [];

        $this->inputService->expects($this->once())
            ->method('parse');

        $this->inputService->expects($this->once())
            ->method('getOption')
            ->with('help')
            ->willReturn($helpOption);

        $this->inputService->expects($this->once())
            ->method('getArgument')
            ->with('path')
            ->willReturn($pathArgument);

        $this->extensionService->expects($this->once())
            ->method('getExtensions')
            ->willReturn($extensions);

        $this->extensionService->expects($this->once())
            ->method('getUsedExtensions')
            ->with($extensions, $pathArgument->getValue())
            ->willReturn($usedExtensions);

        $this->standardOutputService->expects($this->once())
            ->method('write')
            ->with("Loaded extensions:\nCore, curl\n\nUsed extensions:\n-\n\nUsed extensions (non core):\n-\n");

        $this->standardErrorService->expects($this->never())
            ->method('write');

        $this->assertEquals(0, $this->command->handle());
    }

    public function testHandleHelpOptionIsPassed(): void
    {
        $helpOption = new Option('help', 'h', OptionType::NoValue);
        $helpOption->setIsPassed(true);

        $this->inputService->expects($this->once())
            ->method('parse');

        $this->inputService->expects($this->once())
            ->method('getOption')
            ->with('help')
            ->willReturn($helpOption);

        $this->inputService->expects($this->never())
            ->method('getArgument');

        $this->extensionService->expects($this->never())
            ->method('getExtensions');

        $this->extensionService->expects($this->never())
            ->method('getUsedExtensions');

        $this->standardOutputService->expects($this->once())
            ->method('write')
            ->with("Usage: php ./src/script.php [OPTION]... [PATH]\nCheck the extensions being used in the project.\n\nOptions:\n  -h, --help\tdisplay this help and exit\n");

        $this->standardErrorService->expects($this->never())
            ->method('write');

        $this->assertEquals(0, $this->command->handle());
    }

    public function testHandlePathArgumentIsNotPassed(): void
    {
        $helpOption = new Option('help', 'h', OptionType::NoValue);

        $pathArgument = new Argument('path', ArgumentType::Required);

        $this->inputService->expects($this->once())
            ->method('parse');

        $this->inputService->expects($this->once())
            ->method('getOption')
            ->with('help')
            ->willReturn($helpOption);

        $this->inputService->expects($this->once())
            ->method('getArgument')
            ->with('path')
            ->willReturn($pathArgument);

        $this->extensionService->expects($this->never())
            ->method('getExtensions');

        $this->extensionService->expects($this->never())
            ->method('getUsedExtensions');

        $this->standardOutputService->expects($this->never())
            ->method('write');

        $this->standardErrorService->expects($this->once())
            ->method('write')
            ->with("The PATH argument is required.\n");

        $this->assertEquals(1, $this->command->handle());
    }
}
