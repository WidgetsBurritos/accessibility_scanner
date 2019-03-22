<?php

namespace Drupal\Tests\accessibility_scanner\Kernel\Plugin\CaptureResponse;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\accessibility_scanner\Plugin\CaptureResponse\AcheckerCaptureResponse;

/**
 * Tests the functionality of the achecker capture response.
 *
 * @group accessibility_scanner
 */
class AcheckerCaptureResponseTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'web_page_archive',
    'accessibility_scanner',
  ];

  /**
   * Tests preview mode on a passing result.
   */
  public function testPassingXmlPreviewMode() {
    $capture = new AcheckerCaptureResponse(__DIR__ . '/../../../../fixtures/passing.xml', 'https://www.realultimatepower.net');
    $options = [
      'mode' => 'preview',
      'vid' => 20,
      'delta' => 3,
    ];
    $expected = [
      '#theme' => 'wpa-achecker-preview',
      '#summary' => [
        'num_of_errors' => 0,
        'num_of_likely_problems' => 0,
        'num_of_potential_problems' => 0,
        'status' => 'Pass',
      ],
      '#url' => 'https://www.realultimatepower.net',
      '#view_button' => [
        '#type' => 'link',
        '#title' => 'View Detailed Report',
      ],
    ];
    $this->assertArraySubset($expected, $capture->renderable($options));
  }

  /**
   * Tests preview mode on a failing result.
   */
  public function testFailingXmlPreviewMode() {
    $capture = new AcheckerCaptureResponse(__DIR__ . '/../../../../fixtures/failing.xml', 'https://www.realultimatepower.net');
    $options = [
      'mode' => 'preview',
      'vid' => 20,
      'delta' => 3,
    ];
    $expected = [
      '#theme' => 'wpa-achecker-preview',
      '#summary' => [
        'num_of_errors' => 2,
        'num_of_likely_problems' => 1,
        'num_of_potential_problems' => 4,
        'status' => 'Fail',
      ],
      '#url' => 'https://www.realultimatepower.net',
      '#view_button' => [
        '#type' => 'link',
        '#title' => 'View Detailed Report',
      ],
    ];
    $this->assertArraySubset($expected, $capture->renderable($options));
  }

  /**
   * Tests full mode on a passing result.
   */
  public function testPassingXmlFullMode() {
    $capture = new AcheckerCaptureResponse(__DIR__ . '/../../../../fixtures/passing.xml', 'https://www.realultimatepower.net');
    $options = [
      'mode' => 'full',
      'vid' => 20,
      'delta' => 3,
    ];
    $expected = [
      '#theme' => 'wpa-achecker-full-report',
      '#summary' => [
        'num_of_errors' => 0,
        'num_of_likely_problems' => 0,
        'num_of_potential_problems' => 0,
        'status' => 'Pass',
        'guidelines' => [
          'Section 508',
        ],
      ],
    ];
    $actual = $capture->renderable($options);
    $this->assertArraySubset($expected, $actual);

    // Confirm generator is empty:
    $this->assertNull($actual['#results']->current());
  }

  /**
   * Tests full mode on a failing result.
   */
  public function testFailingXmlFullMode() {
    $capture = new AcheckerCaptureResponse(__DIR__ . '/../../../../fixtures/failing.xml', 'https://www.realultimatepower.net');
    $options = [
      'mode' => 'full',
      'vid' => 20,
      'delta' => 3,
    ];
    $expected = [
      '#theme' => 'wpa-achecker-full-report',
      '#summary' => [
        'num_of_errors' => 2,
        'num_of_likely_problems' => 1,
        'num_of_potential_problems' => 4,
        'status' => 'Fail',
        'guidelines' => [
          'BITV 1.0 (Level 2)',
          'Section 508',
          'Stanca Act',
          'WCAG 1.0 (Level A)',
          'WCAG 1.0 (Level AA)',
          'WCAG 1.0 (Level AAA)',
          'WCAG 2.0 (Level A)',
          'WCAG 2.0 (Level AA)',
          'WCAG 2.0 (Level AAA)',
        ],
      ],
    ];
    $actual = $capture->renderable($options);
    $this->assertArraySubset($expected, $actual);

    // Confirm first result is an error:
    $expected = [
      'result_type' => 'Error',
      'line_num' => 1,
      'column_num' => -66,
    ];
    $current = $actual['#results']->current();
    $this->assertArraySubset($expected, $current);
    $this->assertContains('Document does not validate.', $current['error_msg']);
    $this->assertContains('Validate the document using a validator service.', $current['repair']);

    // Confirm second result is an error:
    $actual['#results']->next();
    $expected = [
      'result_type' => 'Error',
      'line_num' => 2,
      'column_num' => -55,
    ];
    $current = $actual['#results']->current();
    $this->assertArraySubset($expected, $current);
    $this->assertContains('Content missing <code>address</code> of page author.', $current['error_msg']);
    $this->assertContains("Add an <code>address</code> element that describes the author's contact information.", $current['repair']);

    // Confirm generator is empty:
    $actual['#results']->next();
    $this->assertNull($actual['#results']->current());
  }

  /**
   * Tests full mode on a failing result.
   */
  public function testInvalidXml() {
    $capture = new AcheckerCaptureResponse(__DIR__ . '/../../../../fixtures/invalid.xml', 'https://www.realultimatepower.net');
    $options = [
      'mode' => 'full',
      'vid' => 20,
      'delta' => 3,
    ];
    $expected = [
      '#theme' => 'wpa-achecker-full-report',
      '#summary' => [
        'num_of_errors' => 0,
        'num_of_likely_problems' => 0,
        'num_of_potential_problems' => 0,
        'status' => 'Invalid',
        'guidelines' => [],
      ],
    ];
    $actual = $capture->renderable($options);
    $this->assertArraySubset($expected, $actual);
    $this->assertNull($actual['#results']->current());
  }

}
