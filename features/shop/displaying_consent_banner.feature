@displaying_consent_banner
Feature: Displaying the consent banner on the shop
    In order to collect cookie consent
    As a Visitor
    I want to see tarteaucitron when it is enabled for the channel

    @ui
    Scenario: Banner scripts are injected when consent is enabled
        Given the store operates on a single channel in "United States"
        And tarteaucitron is enabled for the current channel
        When I open the shop homepage
        Then the page should contain the tarteaucitron init script
        And the visitor's choice should be kept for 180 days
        And the plugin stylesheet URL should carry its content version
        And the tarteaucitron.js URL should carry its content version

    @ui
    Scenario: No banner when consent is disabled
        Given the store operates on a single channel in "United States"
        And tarteaucitron is disabled for the current channel
        When I open the shop homepage
        Then the page should not contain the tarteaucitron init script

    @ui @javascript
    Scenario: Banner is displayed in the browser
        Given the store operates on a single channel in "United States"
        And tarteaucitron is enabled for the current channel
        And the "youtube" service is enabled
        When I open the shop homepage
        Then I should see the consent banner with a way to accept cookies
        And the consent banner should be in "en"
        And the close cross should sit in the top corner of the banner

    @ui @javascript
    Scenario: Banner is displayed on a shop whose locale is neither English nor French
        Given the store operates on a single channel in "es_ES" locale
        And tarteaucitron is enabled for the current channel
        And the "youtube" service is enabled
        When I open the shop homepage
        Then I should see the consent banner with a way to accept cookies
        And the consent banner should be in "es"

    # The test application enables `integration.adblocker` in the test environment
    # (tests/TestApplication/config/config.yaml).
    @ui @javascript
    Scenario: Banner stays reachable with the adblocker detection enabled
        Given the store operates on a single channel in "United States"
        And tarteaucitron is enabled for the current channel
        And the "youtube" service is enabled
        When I open the shop homepage
        Then the tarteaucitron init options should enable the adblocker detection
        And I should see the consent banner with a way to accept cookies

    @ui @javascript
    Scenario: Banner uses the texts set for the shop locale
        Given the store operates on a single channel in "es_ES" locale
        And tarteaucitron is enabled for the current channel
        And the "youtube" service is enabled
        And the "accept_all" banner text is "Vale, acepto" in the "es_ES" locale
        When I open the shop homepage
        Then I should see the consent banner with a way to accept cookies
        And the "accept all" button should read "Vale, acepto"

    # With no service the library shows no banner, only its icon; the adblocker detection (test
    # application) builds the panel late, which once left every button of it dead.
    @ui @javascript
    Scenario: The tarteaucitron icon opens the preferences panel when no service is enabled
        Given the store operates on a single channel in "United States"
        And tarteaucitron is enabled for the current channel
        When I open the shop homepage
        And I open the preferences panel from the tarteaucitron icon
        Then the preferences panel should be open

    @ui @javascript
    Scenario: Banner follows the shop locale on a channel with two locales
        Given the store operates on a single channel in "United States"
        And the store has locale "fr_FR"
        And the channel is also available in the "fr_FR" locale
        And tarteaucitron is enabled for the current channel
        And the "youtube" service is enabled
        And the "accept_all" banner text is "J’accepte" in the "fr_FR" locale
        When I open the shop homepage in the "fr_FR" locale
        Then I should see the consent banner with a way to accept cookies
        And the consent banner should be in "fr"
        And the "accept all" button should read "J’accepte"
        When I open the shop homepage in the "en_US" locale
        Then I should see the consent banner with a way to accept cookies
        And the consent banner should be in "en"
        And the "accept all" button should read "OK, accept all"

    @ui @javascript
    Scenario: Personalizing opens the preferences panel in front of the banner
        Given the store operates on a single channel in "United States"
        And tarteaucitron is enabled for the current channel
        And the "youtube" service is enabled
        When I open the shop homepage
        And I choose to personalize my consent
        Then the preferences panel should be in front of the banner
        And the banner should be hidden behind the preferences panel
        When I click outside the preferences panel
        Then the preferences panel should be closed
        And the banner should be shown again
