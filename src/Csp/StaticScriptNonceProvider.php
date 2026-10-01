<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Csp;

/**
 * The `script_nonce` value: the same nonce for every response.
 *
 * @internal
 */
final readonly class StaticScriptNonceProvider implements ScriptNonceProviderInterface
{
    public function __construct(
        private ?string $nonce = null,
    ) {
    }

    public function getScriptNonce(): ?string
    {
        return $this->nonce;
    }
}
