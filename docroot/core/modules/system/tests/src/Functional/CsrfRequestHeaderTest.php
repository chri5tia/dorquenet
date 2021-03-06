<?php

namespace Drupal\Tests\system\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests protecting routes by requiring CSRF token in the request header.
 *
 * @group system
 */
class CsrfRequestHeaderTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'csrf_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests access to routes protected by CSRF request header requirements.
   *
   * This checks one route that uses _csrf_request_header_token and one that
   * uses the deprecated _access_rest_csrf.
   *
   * @group legacy
   */
  public function testRouteAccess() {
    $this->expectDeprecation('Route requirement _access_rest_csrf is deprecated in drupal:9.2.0 and is removed in drupal:10.0.0. Use _csrf_request_header_token instead. See https://www.drupal.org/node/2772399');
    $client = $this->getHttpClient();
    $csrf_token_paths = ['deprecated/session/token', 'session/token'];
    // Test using the both the current path and a test path that returns
    // a token using the deprecated 'rest' value.
    // Checking /deprecated/session/token can be removed in 8.3.
    // @see \Drupal\Core\Access\CsrfRequestHeaderAccessCheck::access()
    foreach ($csrf_token_paths as $csrf_token_path) {
      // Check both test routes.
      $route_names = ['csrf_test.protected', 'csrf_test.deprecated.protected'];
      foreach ($route_names as $route_name) {
        $user = $this->drupalCreateUser();
        $this->drupalLogin($user);

        $csrf_token = $this->drupalGet($csrf_token_path);
        $url = Url::fromRoute($route_name)
          ->setAbsolute(TRUE)
          ->toString();
        $post_options = [
          'headers' => ['Accept' => 'text/plain'],
          'http_errors' => FALSE,
        ];

        // Test that access is allowed for anonymous user with no token in header.
        $result = $client->post($url, $post_options);
        $this->assertEquals(200, $result->getStatusCode());

        // Add cookies to POST options so that all other requests are for the
        // authenticated user.
        $post_options['cookies'] = $this->getSessionCookies();

        // Test that access is denied with no token in header.
        $result = $client->post($url, $post_options);
        $this->assertEquals(403, $result->getStatusCode());

        // Test that access is allowed with correct token in header.
        $post_options['headers']['X-CSRF-Token'] = $csrf_token;
        $result = $client->post($url, $post_options);
        $this->assertEquals(200, $result->getStatusCode());

        // Test that access is denied with incorrect token in header.
        $post_options['headers']['X-CSRF-Token'] = 'this-is-not-the-token-you-are-looking-for';
        $result = $client->post($url, $post_options);
        $this->assertEquals(403, $result->getStatusCode());
      }
    }

  }

}
