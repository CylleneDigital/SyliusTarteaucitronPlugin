<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

/**
 * @internal
 */
final class AdminMenuListener
{
    public function buildMenu(MenuBuilderEvent $menuBuilderEvent): void
    {
        $menu = $menuBuilderEvent->getMenu();
        $configurationMenu = $menu->getChild('configuration');

        if (null === $configurationMenu) {
            return;
        }

        $configurationMenu
            ->addChild('tarteaucitron', [
                'route' => 'cyllene_digital_sylius_tarteaucitron_admin_configuration',
            ])
            ->setLabel('cyllene_digital_sylius_tarteaucitron.ui.tarteaucitron')
            ->setExtra('translation_domain', 'messages')
            ->setLabelAttribute('icon', 'cookie')
        ;
    }
}
