<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Csp;

/**
 * `script_nonce_provider: nelmio` — the per-response nonce of NelmioSecurityBundle, which also adds
 * it to the script-src directive of the CSP header. Typed loosely so the bundle stays optional.
 *
 * @internal
 */
final readonly class NelmioScriptNonceProvider implements ScriptNonceProviderInterface
{
    /**
     * @param object $listener `nelmio_security.csp_listener` (ContentSecurityPolicyListener)
     */
    public function __construct(
        private object $listener,
    ) {
    }

    public function getScriptNonce(): ?string
    {
        if (!method_exists($this->listener, 'getNonce')) {
            return null;
        }

        $nonce = $this->listener->getNonce('script');

        return is_string($nonce) ? $nonce : null;
    }
}
