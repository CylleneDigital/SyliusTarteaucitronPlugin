@managing_tarteaucitron_configuration
Feature: Configuring tarteaucitron in the back office
    In order to manage cookie consent
    As an Administrator
    I want to open and save the tarteaucitron configuration page

    Background:
        Given the store operates on a single channel in "United States"
        And I am logged in as an administrator

    @ui
    Scenario: Opening the configuration page
        When I go to the tarteaucitron configuration page
        Then I should see the tarteaucitron configuration form
        And I should see that I am configuring the "United States" channel
        And no setting should depart from the CNIL guidance
        And I should be able to enable the "Google Analytics (GA4)" service
        And I should be able to enable the "Smartsupp" service

    @ui
    Scenario: Enabling tarteaucitron and saving
        When I go to the tarteaucitron configuration page
        And I enable tarteaucitron
        And I save my changes
        Then I should be notified that the tarteaucitron configuration has been saved

    @ui
    Scenario: Refusing a consent lifetime tarteaucitron.js would ignore
        When I go to the tarteaucitron configuration page
        And I keep the visitor's choice for 400 days
        And I save my changes
        Then I should be notified that the consent lifetime cannot exceed 364 days

    @ui
    Scenario: Being warned about a setting that departs from the CNIL guidance
        When I go to the tarteaucitron configuration page
        And I keep the visitor's choice for 200 days
        And I save my changes
        Then I should be notified that the tarteaucitron configuration has been saved
        And I should be warned that 1 setting departs from the CNIL guidance
        And the consent lifetime should be flagged

    @ui
    Scenario: Refusing a banner text that would break out of the library markup
        When I go to the tarteaucitron configuration page
        And I set the "accept_all" banner text to 'OK" onclick="alert(1)' in the "en_US" locale
        And I save my changes
        Then I should be notified that the "accept_all" banner text in the "en_US" locale cannot contain '< > "'

    @ui @javascript
    Scenario: Service settings stay folded until the service is switched on
        When I go to the tarteaucitron configuration page
        Then the "Google Analytics (GA4)" service settings should be folded
        When I switch on the "Google Analytics (GA4)" service
        Then the "Google Analytics (GA4)" service settings should be shown
        When I search the "smart" service
        Then I should only see the "Smartsupp" service

    @ui
    Scenario: Configuring each channel separately
        Given the store also operates on another channel named "Mobile Store"
        When I go to the tarteaucitron configuration page of the "Mobile Store" channel
        Then I should see that I am configuring the "Mobile Store" channel
        And I should be able to switch to the "United States" channel
        When I enable tarteaucitron
        And I save my changes
        Then I should be notified that the tarteaucitron configuration has been saved
        And I should see that I am configuring the "Mobile Store" channel
        And tarteaucitron should be enabled
        When I go to the tarteaucitron configuration page of the "United States" channel
        Then tarteaucitron should be disabled

    @ui
    Scenario: Saving an already saved configuration again
        When I go to the tarteaucitron configuration page
        And I enable tarteaucitron
        And I save my changes
        Then I should be notified that the tarteaucitron configuration has been saved
        When I keep the visitor's choice for 90 days
        And I save my changes
        Then I should be notified that the tarteaucitron configuration has been saved
        And the visitor's choice should be kept for 90 days
