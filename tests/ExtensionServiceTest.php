<?php

namespace Tests;

use Extension\Exceptions\ExtensionException;
use Extension\Models\Extension;
use Extension\Models\ExtensionInterface;
use Extension\Services\ExtensionService;
use Extension\Services\ExtensionServiceInterface;
use Fs\Exceptions\FsException;
use Fs\Services\FsServiceInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

class ExtensionServiceTest extends TestCase
{
    protected MockObject&FsServiceInterface $fsService;

    protected ExtensionServiceInterface $extensionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fsService = $this->getMockBuilder(FsServiceInterface::class)->getMock();

        $this->extensionService = new ExtensionService($this->fsService);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testGetExtensions(): void
    {
        $extensions = $this->extensionService->getExtensions();

        $this->assertGreaterThanOrEqual(1, $extensions);

        $coreExtension = $this->getExtension($extensions, 'Core');

        $this->assertEquals('Core', $coreExtension->getName());
        $this->assertTrue($coreExtension->isCore());
        $this->assertNotEmpty($coreExtension->getClasses());
        $this->assertNotEmpty($coreExtension->getConstants());
        $this->assertNotEmpty($coreExtension->getFunctions());
    }

    public function testGetUsedExtensions(): void
    {
        $extensions = [
            new Extension(
                'test',
                true,
                [
                    'Test',
                ],
                [
                    'TEST',
                ],
                [
                    'test',
                ]
            ),
        ];

        $path = '.';

        $subPaths = [
            'Foobar.php',
            'README.md',
        ];

        $content = <<<CONTENT
        <?php

        namespace Tests;

        class Foobar
        {
            public function foobar(): void
            {
                new Test();

                Test::TEST;

                test();
            }
        }

        CONTENT;

        $this->fsService->expects($this->once())
            ->method('list')
            ->with($path, true)
            ->willReturnCallback(function () use ($subPaths) {
                foreach ($subPaths as $subPath) {
                    yield $subPath;
                }
            });

        $this->fsService->expects($this->once())
            ->method('readFile')
            ->with(sprintf('%s/%s', $path, $subPaths[0]))
            ->willReturn($content);

        $usedExtensions = $this->extensionService->getUsedExtensions($extensions, $path);

        $this->assertCount(1, $usedExtensions);

        $testExtension = $this->getExtension($usedExtensions, 'test');

        $this->assertEquals($extensions[0], $testExtension);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testGetUsedExtensionsEmptyExtensions(): void
    {
        $usedExtensions = $this->extensionService->getUsedExtensions([], '.');

        $this->assertEmpty($usedExtensions);
    }

    public function testGetUsedExtensionsFsServiceListThrowsException(): void
    {
        $this->expectException(ExtensionException::class);
        $this->expectExceptionMessage('nope.');

        $this->fsService->expects($this->once())
            ->method('list')
            ->willThrowException(new FsException('nope.'));

        $this->fsService->expects($this->never())
            ->method('readFile');

        $this->extensionService->getUsedExtensions(
            [
                new Extension(
                    'test',
                    true,
                    [
                        'Test',
                    ],
                    [
                        'TEST',
                    ],
                    [
                        'test',
                    ]
                ),
            ],
            '.'
        );
    }

    public function testGetUsedExtensionsFsServiceReadFileThrowsException(): void
    {
        $this->expectException(ExtensionException::class);
        $this->expectExceptionMessage('nope.');

        $path = '.';

        $subPaths = [
            'Foobar.php',
            'README.md',
        ];

        $this->fsService->expects($this->once())
            ->method('list')
            ->with($path, true)
            ->willReturnCallback(function () use ($subPaths) {
                foreach ($subPaths as $subPath) {
                    yield $subPath;
                }
            });

        $this->fsService->expects($this->once())
            ->method('readFile')
            ->willThrowException(new FsException('nope.'));

        $this->extensionService->getUsedExtensions(
            [
                new Extension(
                    'test',
                    true,
                    [
                        'Test',
                    ],
                    [
                        'TEST',
                    ],
                    [
                        'test',
                    ]
                ),
            ],
            $path
        );
    }

    /**
     * @param array<int,ExtensionInterface> $extensions
     *
     * @throws ExtensionException
     */
    protected function getExtension(array $extensions, string $name): ExtensionInterface
    {
        $extension = array_find(
            $extensions,
            function (ExtensionInterface $extension) use ($name) {
                return $extension->getName() === $name;
            }
        );

        if (!$extension instanceof ExtensionInterface) {
            throw new ExtensionException('Failed to find the extension.');
        }

        return $extension;
    }
}
