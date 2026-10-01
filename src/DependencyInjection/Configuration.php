<?php

declare(strict_types=1);

namespace CylleneDigital\SyliusTarteaucitronPlugin\DependencyInjection;

use CylleneDigital\SyliusTarteaucitronPlugin\Consent\Tracker\TrackerCategory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @internal
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('cyllene_digital_sylius_tarteaucitron');

        /** @var ArrayNodeDefinition $root */
        $root = $treeBuilder->getRootNode();
        $root
            ->children()
                ->scalarNode('script_nonce')
                    ->defaultNull()
                    ->info('Fixed CSP nonce for the <script> tags this plugin emits, the same for every response. For a per-response nonce use script_nonce_provider. Files tarteaucitron.js injects itself (lang/, services, advertising) are not covered.')
                ->end()
                ->scalarNode('script_nonce_provider')
                    ->defaultNull()
                    ->info('Per-response CSP nonce: "nelmio" (NelmioSecurityBundle) or the id of a service implementing ScriptNonceProviderInterface.')
                ->end()
                ->arrayNode('locale_aliases')
                    ->useAttributeAsKey('from')
                    ->normalizeKeys(false)
                    ->scalarPrototype()->end()
                    ->defaultValue(['nb' => 'no'])
                    ->info('Map Sylius locale subtags onto tarteaucitron.js availableLanguages (e.g. nb → no).')
                ->end()
                ->arrayNode('integration')
                    ->info('tarteaucitron.init() options owned by the theme integration, for every channel (not in the back office).')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('use_external_css')
                            ->defaultFalse()
                            ->info('True: the library no longer injects tarteaucitron.css; the theme must ship a complete stylesheet.')
                        ->end()
                        ->booleanNode('mandatory_cta')
                            ->defaultFalse()
                            ->info('True: shows disabled allow / deny buttons on the mandatory cookies line. The library stylesheet hides them: only useful with a theme stylesheet (use_external_css).')
                        ->end()
                        ->booleanNode('use_external_js')
                            ->defaultFalse()
                            ->info('True: the library no longer loads its language file and tarteaucitron.services.js; the theme must load them.')
                        ->end()
                        ->booleanNode('server_side')
                            ->defaultFalse()
                            ->info('True: accepted services are not loaded in the browser (server-side tagging).')
                        ->end()
                        ->booleanNode('adblocker')
                            ->defaultFalse()
                            ->info('True: shows a message when an ad blocker prevents tarteaucitron from working.')
                        ->end()
                        ->scalarNode('hashtag')
                            ->defaultValue('#tarteaucitron')
                            ->info('URL fragment that opens the preferences panel, e.g. a "#tarteaucitron" footer link.')
                            ->validate()
                                ->ifTrue(static fn (mixed $value): bool => !is_string($value) || 1 !== preg_match('/^#[\\w-]+$/', $value))
                                ->thenInvalid('The hashtag must be a URL fragment such as "#tarteaucitron", got %s.')
                            ->end()
                        ->end()
                        ->scalarNode('custom_closer_id')
                            ->defaultNull()
                            ->info('HTML id of the element focused when the panel closes (keyboard accessibility), e.g. your "Manage cookies" link.')
                            ->validate()
                                ->ifTrue(static fn (mixed $value): bool => null !== $value && (!is_string($value) || 1 !== preg_match('/^[A-Za-z][\\w:.-]*$/', $value)))
                                ->thenInvalid('The custom closer id must be an HTML id, got %s.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('trackers')
                    ->info('Vendor services to offer in the back office without writing a PHP class, keyed by tarteaucitron job key.')
                    ->useAttributeAsKey('type')
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->children()
                            ->enumNode('category')
                                ->values(array_map(static fn (TrackerCategory $category): string => $category->value, TrackerCategory::cases()))
                                ->isRequired()
                            ->end()
                            ->scalarNode('label')
                                ->defaultNull()
                                ->info('Back-office name (translation key or plain text). Defaults to the job key.')
                            ->end()
                            ->booleanNode('embed')
                                ->defaultFalse()
                                ->info('True for services that replace HTML placeholders in the theme (videos, widgets).')
                            ->end()
                            ->arrayNode('parameters')
                                ->info('tarteaucitron.user.* keys the service reads, keyed by that exact key.')
                                ->useAttributeAsKey('user_key')
                                ->normalizeKeys(false)
                                ->arrayPrototype()
                                    ->children()
                                        ->booleanNode('required')->defaultTrue()->end()
                                        ->scalarNode('placeholder')->defaultValue('')->end()
                                        ->scalarNode('label')->defaultNull()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->validate()
                ->ifTrue(static fn (array $config): bool => null !== $config['script_nonce'] && null !== $config['script_nonce_provider'])
                ->thenInvalid('Set either script_nonce (fixed) or script_nonce_provider (per response), not both.')
            ->end()
        ;

        return $treeBuilder;
    }
}
