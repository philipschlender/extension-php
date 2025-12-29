<?php

use Cli\Services\InputService;
use Extension\Models\Command;
use Extension\Services\ExtensionService;
use Fs\Services\FsService;
use Io\Services\StandardErrorService;
use Io\Services\StandardOutputService;

require_once realpath(sprintf('%s/../vendor/autoload.php', __DIR__));

$command = new Command(
    new InputService(),
    new StandardOutputService(),
    new StandardErrorService(),
    new ExtensionService(new FsService())
);

exit($command->handle());
