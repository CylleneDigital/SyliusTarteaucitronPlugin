<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\Consent\Init;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\ConsentMode;

/**
 * Official tarteaucitron.init() options (vendor jsKeys, including irregular names).
 *
 * @internal
 */
final class InitOptionCatalog
{
    /** @var list<InitOption>|null */
    private ?array $options = null;

    /**
     * @return list<InitOption>
     */
    public function all(): array
    {
        return $this->options();
    }

    /**
     * @return list<InitOption>
     */
    public function bySection(InitOptionSection $section): array
    {
        return array_values(array_filter(
            $this->options(),
            static fn (InitOption $option): bool => $option->section === $section,
        ));
    }

    /**
     * @return list<InitOption>
     */
    private function options(): array
    {
        return $this->options ??= $this->build();
    }

    /**
     * @return list<InitOption>
     */
    private function build(): array
    {
        $ui = static fn (string $suffix): string => 'cyllene_digital_sylius_tarteaucitron.ui.' . $suffix;
        $essential = InitOptionSection::Essential;
        $compliance = InitOptionSection::Compliance;
        $consentMode = InitOptionSection::ConsentMode;
        $display = InitOptionSection::Display;
        $advanced = InitOptionSection::Advanced;

        return [
            new InitOption('privacy_url', 'privacyUrl', InitOptionType::String, '', $essential, url: true),
            new InitOption('readmore_link', 'readmoreLink', InitOptionType::String, '', $essential, url: true),

            new InitOption('high_privacy', 'highPrivacy', InitOptionType::Bool, true, $compliance),
            new InitOption('accept_all_cta', 'AcceptAllCta', InitOptionType::Bool, true, $compliance),
            new InitOption('deny_all_cta', 'DenyAllCta', InitOptionType::Bool, true, $compliance),
            new InitOption(
                'service_default_state',
                'serviceDefaultState',
                InitOptionType::Choice,
                'wait',
                $compliance,
                [
                    $ui('service_default_state_true') => 'true',
                    $ui('service_default_state_wait') => 'wait',
                    $ui('service_default_state_false') => 'false',
                ],
            ),
            new InitOption('close_popup', 'closePopup', InitOptionType::Bool, true, $compliance),
            new InitOption('always_need_consent', 'alwaysNeedConsent', InitOptionType::Bool, false, $compliance),
            new InitOption('mandatory', 'mandatory', InitOptionType::Bool, true, $compliance),
            new InitOption('handle_browser_dnt_request', 'handleBrowserDNTRequest', InitOptionType::Bool, false, $compliance),

            new InitOption(
                'google_consent_mode',
                'googleConsentMode',
                InitOptionType::Bool,
                true,
                $consentMode,
                relatedConsentMode: ConsentMode::Google,
            ),
            new InitOption(
                'bing_consent_mode',
                'bingConsentMode',
                InitOptionType::Bool,
                true,
                $consentMode,
                relatedConsentMode: ConsentMode::Bing,
            ),
            new InitOption('piano_consent_mode', 'pianoConsentMode', InitOptionType::Bool, true, $consentMode),
            new InitOption('piano_consent_mode_essential', 'pianoConsentModeEssential', InitOptionType::Bool, false, $consentMode),
            new InitOption('piwik_consent_mode', 'piwikConsentMode', InitOptionType::Bool, true, $consentMode),
            new InitOption('soft_consent_mode', 'softConsentMode', InitOptionType::Bool, false, $consentMode),

            new InitOption(
                'orientation',
                'orientation',
                InitOptionType::Choice,
                'middle',
                $display,
                [
                    $ui('orientation_top') => 'top',
                    $ui('orientation_middle') => 'middle',
                    $ui('orientation_bottom') => 'bottom',
                    $ui('orientation_popup') => 'popup',
                ],
            ),
            new InitOption(
                'body_position',
                'bodyPosition',
                InitOptionType::Choice,
                'top',
                $display,
                [
                    $ui('body_position_top') => 'top',
                    $ui('body_position_bottom') => 'bottom',
                ],
            ),
            new InitOption('show_alert_small', 'showAlertSmall', InitOptionType::Bool, false, $display),
            new InitOption('show_title_banner', 'showTitleBanner', InitOptionType::Bool, false, $display),
            new InitOption('show_icon', 'showIcon', InitOptionType::Bool, true, $display),
            new InitOption(
                'icon_position',
                'iconPosition',
                InitOptionType::Choice,
                'BottomRight',
                $display,
                [
                    $ui('icon_bottom_right') => 'BottomRight',
                    $ui('icon_bottom_left') => 'BottomLeft',
                    $ui('icon_top_right') => 'TopRight',
                    $ui('icon_top_left') => 'TopLeft',
                ],
            ),
            new InitOption('icon_src', 'iconSrc', InitOptionType::String, '', $display, omitIfEmpty: true, url: true, allowRasterDataUri: true),
            new InitOption('group_services', 'groupServices', InitOptionType::Bool, true, $display),
            new InitOption('show_details_on_click', 'showDetailsOnClick', InitOptionType::Bool, true, $display),
            new InitOption('partners_list', 'partnersList', InitOptionType::Bool, true, $display),
            new InitOption('more_info_link', 'moreInfoLink', InitOptionType::Bool, true, $display),
            new InitOption('cookies_list', 'cookieslist', InitOptionType::Bool, false, $display),
            new InitOption('cookieslist_embed', 'cookieslistEmbed', InitOptionType::Bool, false, $display),
            new InitOption('remove_credit', 'removeCredit', InitOptionType::Bool, false, $display),

            // RFC 6265 cookie-name token; the library writes `name=value; domain=…` verbatim.
            new InitOption('cookie_name', 'cookieName', InitOptionType::String, 'tarteaucitron', $advanced, pattern: '/^[A-Za-z0-9!#$%&\'*+.^_`|~-]+$/D'),
            new InitOption('cookie_domain', 'cookieDomain', InitOptionType::String, '', $advanced, omitIfEmpty: true, pattern: '/^\.?[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?)*$/D'),
            new InitOption('data_layer', 'dataLayer', InitOptionType::Bool, false, $advanced),
        ];
    }
}
