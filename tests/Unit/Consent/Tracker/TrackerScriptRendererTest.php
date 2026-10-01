<?php

declare(strict_types=1);

namespace Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Consent\Tracker;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerRuntimeState;
use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerScriptRenderer;
use PHPUnit\Framework\TestCase;
use Tests\CylleneDigital\SyliusTarteaucitronPlugin\Unit\Tracker\TrackerTestKit;

final class TrackerScriptRendererTest extends TestCase
{
    private TrackerScriptRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new TrackerScriptRenderer(TrackerTestKit::registry());
    }

    public function testMissingRequiredParameterRendersNothing(): void
    {
        $state = new TrackerRuntimeState('gtag', ['gtag_ua' => '']);

        self::assertNull($this->renderer->render($state));
    }

    public function testUnknownTypeRendersNothing(): void
    {
        $state = new TrackerRuntimeState('not-a-tracker', []);

        self::assertNull($this->renderer->render($state));
    }

    public function testEmbedWithoutParametersPushesJob(): void
    {
        $script = $this->renderer->render(new TrackerRuntimeState('youtube', []));

        self::assertNotNull($script);
        self::assertStringContainsString('(tarteaucitron.job = tarteaucitron.job || []).push("youtube");', $script);
        self::assertStringNotContainsString('tarteaucitron.user.', $script);
    }

    public function testValuesAreJsonEncoded(): void
    {
        $state = new TrackerRuntimeState('gtag', ['gtag_ua' => '</script><script>alert(1)']);
        $script = $this->renderer->render($state);

        self::assertNotNull($script);
        self::assertStringNotContainsString('</script><script>', $script);
        self::assertStringContainsString('\u003C/script\u003E', $script);
        self::assertStringContainsString('tarteaucitron.user.gtagUa =', $script);
        self::assertStringContainsString('.push("gtag");', $script);
    }

    public function testRenderAllJoinsSnippets(): void
    {
        $script = $this->renderer->renderAll([
            new TrackerRuntimeState('gtag', ['gtag_ua' => 'G-1']),
            new TrackerRuntimeState('youtube', []),
        ]);

        self::assertStringContainsString('gtagUa', $script);
        self::assertStringContainsString('push("youtube")', $script);
        self::assertSame(1, substr_count($script, 'push("gtag")'));
    }
}
