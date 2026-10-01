<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin;

use CylleneDigital\SyliusTarteaucitronPlugin\DependencyInjection\Compiler\UniqueTrackerTypePass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class CylleneDigitalSyliusTarteaucitronPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new UniqueTrackerTypePass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
