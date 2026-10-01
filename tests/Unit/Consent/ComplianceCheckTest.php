<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Tests\Unit\Consent;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\ComplianceCheck;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptionCatalog;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init\InitOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ComplianceCheckTest extends TestCase
{
    public function testCatalogDefaultsRaiseNothing(): void
    {
        self::assertSame([], $this->fields([], 180));
    }

    /**
     * @param array<string, mixed> $options
     * @param list<string>         $expected
     */
    #[DataProvider('riskySettings')]
    public function testRiskySettingIsReported(array $options, int $lifetime, array $expected): void
    {
        self::assertSame($expected, $this->fields($options, $lifetime));
    }

    /** @return iterable<string, array{array<string, mixed>, int, list<string>}> */
    public static function riskySettings(): iterable
    {
        yield 'implied consent' => [['high_privacy' => false], 180, ['high_privacy']];
        yield 'no deny button next to accept' => [['deny_all_cta' => false], 180, ['deny_all_cta']];
        yield 'no deny button, implied consent shows accept anyway' => [
            ['high_privacy' => false, 'accept_all_cta' => false, 'deny_all_cta' => false],
            180,
            ['high_privacy', 'deny_all_cta'],
        ];
        yield 'services accepted by default' => [['service_default_state' => 'true'], 180, ['service_default_state']];
        yield 'choice kept more than 6 months' => [[], 181, ['consent_lifetime_days']];
    }

    public function testNoDenyButtonIsFineWhenTheBannerHasNoAcceptButtonEither(): void
    {
        self::assertSame([], $this->fields(['accept_all_cta' => false, 'deny_all_cta' => false], 180));
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return list<string>
     */
    private function fields(array $options, int $lifetime): array
    {
        $findings = (new ComplianceCheck())->check(InitOptions::fromArray($options, new InitOptionCatalog()), $lifetime);

        return array_map(static fn (array $finding): string => $finding['field'], $findings);
    }
}
