<?php

namespace Drupal\Tests\accessibility_scanner\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\accessibility_scanner\Plugin\CaptureUtility\AcheckerCaptureUtility;

/**
 * Tests web page archive.
 *
 * @group accessibility_scanner
 */
class AcheckerEndToEndTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public $profile = 'minimal';

  /**
   * Authorized Admin User.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authorizedAdminUser;

  /**
   * Authorized View User.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authorizedReadOnlyUser;

  /**
   * Unauthorized User.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $unauthorizedUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'accessibility_scanner',
    'key',
    'web_page_archive',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->authorizedAdminUser = $this->drupalCreateUser([
      'administer web page archive',
      'view web page archive results',
      'administer keys',
    ]);
  }

  /**
   * Tests the achecker capture utility end-to-end.
   */
  public function testAcheckerEndToEnd() {
    $assert = $this->assertSession();
    $this->drupalLogin($this->authorizedAdminUser);

    // Setup a key.
    $this->drupalPostForm('admin/config/system/keys/add',
      [
        'label' => 'Test Key',
        'id' => 'test_key',
        'key_type' => 'authentication',
        'key_provider' => 'config',
        'key_input_settings[key_value]' => 'notanactualkeyvalue',
      ],
      t('Save')
    );

    // Set our key and endpoint.
    $this->drupalPostForm('admin/config/system/web-page-archive/settings',
      [
        'wpa_achecker_capture[system][achecker_endpoint]' => AcheckerCaptureUtility::MOCK_ENDPOINT,
        'wpa_achecker_capture[system][achecker_web_service_id]' => 'test_key',
      ],
      t('Save configuration')
    );

    // Setup WPA job.
    $this->drupalPostForm('admin/config/system/web-page-archive/jobs/add',
      [
        'label' => 'Achecker Job',
        'id' => 'achecker_job',
        'use_cron' => 0,
        'urls' => implode(PHP_EOL, [
          'http://localhost/failing',
          'http://localhost/passing',
          'http://localhost/this-should-fail',
          'http://localhost/this-should-pass',
        ]),
      ],
      t('Create new archive')
    );

    // Add achecker scanner.
    $this->drupalPostForm(NULL,
      ['new' => 'wpa_achecker_capture'],
      t('Add')
    );
    $this->drupalPostForm(NULL, [], t('Add capture utility'));

    // Start the run.
    $this->drupalPostForm('admin/config/system/web-page-archive/jobs/achecker_job/queue',
      [],
      t('Start Run')
    );

    // Go view full run.
    $this->clickLink('View Details');
    $assert->pageTextContains(t('PASS'));
    $assert->pageTextContains(t('FAIL'));
    $assert->pageTextContains(t('Errors: 2'));
    $assert->pageTextContains(t('Likely Problems: 1'));
    $assert->pageTextContains(t('Potential Problems: 4'));

  }

}
