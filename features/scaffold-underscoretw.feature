Feature: Scaffold a starter theme from underscoretw.com

  Scenario: Scaffold a starter theme
    Given a WP install
    And I run `wp theme path`
    And save STDOUT as {THEME_DIR}

    When I run `wp scaffold _tw starter-theme --theme_name="Starter Theme" --author="Jane Doe"`
    Then STDOUT should contain:
      """
      Success: Created theme 'Starter Theme'.
      """
    And the {THEME_DIR}/starter-theme/theme/style.css file should exist

  Scenario: Scaffold and activate a theme
    Given a WP install

    When I run `wp scaffold _tw starter-theme --activate`
    Then STDOUT should contain:
      """
      Success: Created theme 'Starter Theme'.
      """
    And STDOUT should contain:
      """
      Success: Switched to 'Starter Theme' theme.
      """

  Scenario: Scaffold and network enable a theme
    Given a WP multisite install

    When I run `wp scaffold _tw starter-theme --enable-network`
    Then STDOUT should contain:
      """
      Success: Created theme 'Starter Theme'.
      """
    And STDOUT should contain:
      """
      Success: Network enabled the 'Starter Theme' theme.
      """

  Scenario: Force overwrite an existing theme
    Given a WP install
    And I run `wp theme path`
    And save STDOUT as {THEME_DIR}

    When I run `wp scaffold _tw starter-theme`
    Then STDOUT should contain:
      """
      Success: Created theme 'Starter Theme'.
      """

    When I run `wp scaffold _tw starter-theme --force`
    Then STDOUT should contain:
      """
      Success: Created theme 'Starter Theme'.
      """
    And the {THEME_DIR}/starter-theme/theme/style.css file should exist

  Scenario: Force overwrite removes old content
    Given a WP install
    And I run `wp theme path`
    And save STDOUT as {THEME_DIR}

    When I run `wp scaffold _tw force-test-theme`
    Then STDOUT should contain:
      """
      Success: Created theme 'Force Test Theme'.
      """

    Given a force-test-marker.txt file:
      """
      This file should be removed by --force.
      """
    And I run `cp force-test-marker.txt {THEME_DIR}/force-test-theme/force-test-marker.txt`

    When I run `wp scaffold _tw force-test-theme --force`
    Then STDOUT should contain:
      """
      Removing existing theme directory 'force-test-theme'...
      """
    And STDOUT should contain:
      """
      Success: Created theme 'Force Test Theme'.
      """
    And the {THEME_DIR}/force-test-theme/force-test-marker.txt file should not exist
    And the {THEME_DIR}/force-test-theme/theme/style.css file should exist

  Scenario: Error when theme directory exists without --force
    Given a WP install

    When I run `wp scaffold _tw starter-theme`
    Then STDOUT should contain:
      """
      Success: Created theme 'Starter Theme'.
      """

    When I try `wp scaffold _tw starter-theme`
    Then STDERR should contain:
      """
      Error: The theme directory 'starter-theme' already exists. Use --force to overwrite.
      """
    And the return code should be 1

  Scenario: Error with invalid slug
    Given a WP install

    When I try `wp scaffold _tw .`
    Then STDERR should contain:
      """
      Error: Invalid theme slug. Theme slugs can contain only letters, numbers, underscores and hyphens, and must start with a letter or underscore.
      """
    And the return code should be 1

    When I try `wp scaffold _tw ../`
    Then STDERR should contain:
      """
      Error: Invalid theme slug. Theme slugs can contain only letters, numbers, underscores and hyphens, and must start with a letter or underscore.
      """
    And the return code should be 1

    When I try `wp scaffold _tw 1themestartingwithnumber`
    Then STDERR should contain:
      """
      Error: Invalid theme slug. Theme slugs can contain only letters, numbers, underscores and hyphens, and must start with a letter or underscore.
      """
    And the return code should be 1

  Scenario: Error with invalid prefix
    Given a WP install

    When I try `wp scaffold _tw valid-slug --prefix=123bad`
    Then STDERR should contain:
      """
      Error: Invalid function prefix. Prefixes can contain only letters, numbers, underscores and hyphens, and must start with a letter or underscore.
      """
    And the return code should be 1

  Scenario: Scaffold a theme with a single-character slug
    Given a WP install
    And I run `wp theme path`
    And save STDOUT as {THEME_DIR}

    When I run `wp scaffold _tw x --theme_name="X Theme"`
    Then STDOUT should contain:
      """
      Success: Created theme 'X Theme'.
      """
    And the {THEME_DIR}/x/theme/style.css file should exist

  Scenario: Alias works identically
    Given a WP install
    And I run `wp theme path`
    And save STDOUT as {THEME_DIR}

    When I run `wp scaffold underscoretw alias-theme`
    Then STDOUT should contain:
      """
      Success: Created theme 'Alias Theme'.
      """
    And the {THEME_DIR}/alias-theme/theme/style.css file should exist

  Scenario: Scaffold with all options combined
    Given a WP install
    And I run `wp theme path`
    And save STDOUT as {THEME_DIR}

    When I run `wp scaffold _tw full-theme --theme_name="Full Theme" --prefix=fulltheme --author="Jane Doe" --author_uri=https://example.com --theme_uri=https://fulltheme.example.com --description="A fully configured theme"`
    Then STDOUT should contain:
      """
      Success: Created theme 'Full Theme'.
      """
    And the {THEME_DIR}/full-theme/theme/style.css file should exist

  Scenario: Interactive wizard generates a theme
    Given a WP install
    And I run `wp theme path`
    And save STDOUT as {THEME_DIR}
    And a wizard-input file:
      """
      Starter Theme


      Jane Doe


      A starter theme built with _tw


      """

    When I run `wp scaffold _tw < wizard-input`
    Then STDOUT should contain:
      """
      About to generate a theme with the following settings:
      """
    And STDOUT should contain:
      """
      Theme Name:      Starter Theme
      """
    And STDOUT should contain:
      """
      Theme Slug:      starter-theme
      """
    And STDOUT should contain:
      """
      Success: Created theme 'Starter Theme'.
      """
    And the {THEME_DIR}/starter-theme/theme/style.css file should exist

  Scenario: Interactive wizard uses CLI arguments as defaults
    Given a WP install
    And I run `wp theme path`
    And save STDOUT as {THEME_DIR}
    And a wizard-args-input file:
      """









      """

    When I run `wp scaffold _tw --theme_name="Args Theme" --author="Jane Doe" < wizard-args-input`
    Then STDOUT should contain:
      """
      Theme Name:      Args Theme
      """
    And STDOUT should contain:
      """
      Theme Slug:      args-theme
      """
    And STDOUT should contain:
      """
      Author:          Jane Doe
      """
    And STDOUT should contain:
      """
      Success: Created theme 'Args Theme'.
      """
    And the {THEME_DIR}/args-theme/theme/style.css file should exist

  Scenario: Interactive wizard with --activate flag
    Given a WP install
    And a wizard-activate-input file:
      """
      Activate Test








      """

    When I run `wp scaffold _tw --activate < wizard-activate-input`
    Then STDOUT should contain:
      """
      Success: Created theme 'Activate Test'.
      """
    And STDOUT should contain:
      """
      Success: Switched to 'Activate Test' theme.
      """

  Scenario: Interactive wizard with --force flag
    Given a WP install
    And a wizard-force-input file:
      """
      Force Test








      """

    When I run `wp scaffold _tw < wizard-force-input`
    Then STDOUT should contain:
      """
      Success: Created theme 'Force Test'.
      """

    When I run `wp scaffold _tw --force < wizard-force-input`
    Then STDOUT should contain:
      """
      Removing existing theme directory 'force-test'...
      """
    And STDOUT should contain:
      """
      Success: Created theme 'Force Test'.
      """

  Scenario: Interactive wizard with activate prompt answered yes
    Given a WP install
    And a wizard-activate-yes-input file:
      """
      Activate Wizard Test






      yes

      """

    When I run `wp scaffold _tw < wizard-activate-yes-input`
    Then STDOUT should contain:
      """
      Activate:        yes
      """
    And STDOUT should contain:
      """
      Success: Created theme 'Activate Wizard Test'.
      """
    And STDOUT should contain:
      """
      Success: Switched to 'Activate Wizard Test' theme.
      """

  Scenario: Interactive wizard on multisite with network-enable prompt
    Given a WP multisite install
    And a wizard-network-input file:
      """
      Network Test






      no
      yes

      """

    When I run `wp scaffold _tw < wizard-network-input`
    Then STDOUT should contain:
      """
      Network Enable:  yes
      """
    And STDOUT should contain:
      """
      Success: Created theme 'Network Test'.
      """
    And STDOUT should contain:
      """
      Success: Network enabled the 'Network Test' theme.
      """
