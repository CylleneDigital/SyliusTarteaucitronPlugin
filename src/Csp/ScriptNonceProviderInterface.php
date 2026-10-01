<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Csp;

/**
 * Nonce for the <script> tags the plugin emits, asked on every render so it can follow the
 * Content-Security-Policy of the current response. Point `script_nonce_provider` at your service.
 */
interface ScriptNonceProviderInterface
{
    /** Null or empty: the tags carry no nonce attribute. */
    public function getScriptNonce(): ?string;
}
