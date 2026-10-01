<?php

declare(strict_types=1);

// Sylius Behat DI still references Sylius\Behat\Page\SymfonyPage while the class is SyliusPage.
class_alias(
    Sylius\Behat\Page\SyliusPage::class,
    'Sylius\Behat\Page\SymfonyPage',
);
