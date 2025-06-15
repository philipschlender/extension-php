<?php

namespace Tests;

use Extension\Models\Extension;
use Extension\Models\ExtensionInterface;

class ExtensionTest extends TestCase
{
    protected string $name;

    protected bool $isCore;

    /**
     * @var array<int,string>
     */
    protected array $classes;

    /**
     * @var array<int,string>
     */
    protected array $constants;

    /**
     * @var array<int,string>
     */
    protected array $functions;

    protected ExtensionInterface $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->name = $this->fakerService->getDataTypeGenerator()->randomString();

        $this->isCore = $this->fakerService->getDataTypeGenerator()->randomBoolean();

        $this->classes = [
            $this->fakerService->getDataTypeGenerator()->randomString(),
        ];

        $this->constants = [
            $this->fakerService->getDataTypeGenerator()->randomString(),
        ];

        $this->functions = [
            $this->fakerService->getDataTypeGenerator()->randomString(),
        ];

        $this->extension = new Extension(
            $this->name,
            $this->isCore,
            $this->classes,
            $this->constants,
            $this->functions
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals($this->name, $this->extension->getName());
    }

    public function testIsCore(): void
    {
        $this->assertEquals($this->isCore, $this->extension->isCore());
    }

    public function testGetClasses(): void
    {
        $this->assertEquals($this->classes, $this->extension->getClasses());
    }

    public function testGetConstants(): void
    {
        $this->assertEquals($this->constants, $this->extension->getConstants());
    }

    public function testGetFunctions(): void
    {
        $this->assertEquals($this->functions, $this->extension->getFunctions());
    }
}
