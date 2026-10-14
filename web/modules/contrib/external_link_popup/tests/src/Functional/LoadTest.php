<?php

namespace Drupal\Tests\external_link_popup\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group external_link_popup
 */
class LoadTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['external_link_popup'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to administer external link popup.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer external link popup']);
    $this->drupalLogin($this->user);
  }

  /**
   * Test some pages.
   */
  public function testLoad() {
    // Test that the home page loads with a 200 response.
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertSession()->statusCodeEquals(200);

    // Visit the settings page.
    $url = Url::fromRoute('external_link_popup.settings');
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Test that the page contains a known label.
    $this->assertSession()->pageTextContains('Trusted domains');

    // Visit the popup collection page.
    $url = Url::fromRoute('entity.external_link_popup.collection');
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Test that the page contains the Default popup.
    $this->assertSession()->pageTextContains('Default');

    // Visit the popup add form page.
    $url = Url::fromRoute('entity.external_link_popup.add_form');
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);

    // Test that the page contains a known label.
    $this->assertSession()->pageTextContains('Yes button');
  }

}
