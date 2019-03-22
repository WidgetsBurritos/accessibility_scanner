<?php

namespace Drupal\accessibility_scanner\Plugin\CaptureUtility;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\accessibility_scanner\Plugin\CaptureResponse\AcheckerCaptureResponse;
use Drupal\web_page_archive\Plugin\ConfigurableCaptureUtilityBase;

/**
 * Achecker accessibility scanner capture utility.
 *
 * @CaptureUtility(
 *   id = "wpa_achecker_capture",
 *   label = @Translation("Achecker Accessibility Scanner", context = "Web Page Archive"),
 *   description = @Translation("Scans URLs for accessibility issues.", context = "Web Page Archive")
 * )
 */
class AcheckerCaptureUtility extends ConfigurableCaptureUtilityBase {

  /**
   * Mock endpoint used for testing purposes.
   */
  const MOCK_ENDPOINT = 'https://mock.endpoint/checkacc.php';

  /**
   * Most recent response.
   *
   * @var string|null
   */
  private $response = NULL;

  /**
   * {@inheritdoc}
   */
  public function capture(array $data = []) {
    $config = \Drupal::configFactory();
    $capture_utility_settings = $config->get('web_page_archive.wpa_achecker_capture.settings')->get('system');

    if (empty($capture_utility_settings['achecker_endpoint'])) {
      throw new \Exception('Invalid AChecker endpoint');
    }

    if (empty($capture_utility_settings['achecker_web_service_id'])) {
      throw new \Exception('Missing AChecker Web Service ID');
    }

    $key = \Drupal::service('key.repository')->getKey($capture_utility_settings['achecker_web_service_id']);
    if (!isset($key)) {
      throw new \Exception('Invalid key');
    }
    $web_service_id = $key->getKeyValue();
    if (empty($web_service_id)) {
      throw new \Exception('Empty web service key');
    }

    // Handle missing URLs.
    if (!isset($data['url'])) {
      throw new \Exception('Capture URL is required');
    }

    $system = \Drupal::configFactory()->get('web_page_archive.wpa_achecker_capture.settings');

    // If our endpoint is a mock endpoint, let's simulate behavior.
    // This allows for testing without actual HTTP requests being made.
    if ($capture_utility_settings['achecker_endpoint'] == static::MOCK_ENDPOINT) {
      $dir = realpath(__DIR__ . '/../../../tests/fixtures');
      if (stristr($data['url'], 'pass')) {
        $filename = "{$dir}/passing.xml";
      }
      else {
        $filename = "{$dir}/failing.xml";
      }
    }
    // Save xml and set our response.
    else {
      $params = [
        'uri' => $data['url'],
        'id' => $web_service_id,
        'output' => 'rest',
        'guide' => implode(',', array_filter($this->configuration['guidelines'])),
      ];
      $param_str = http_build_query($params);
      $url = "{$capture_utility_settings['achecker_endpoint']}?{$param_str}";

      // Determine file locations.
      $filename = $this->getFileName($data, 'xml');
      \Drupal::httpClient()->request('GET', $url, ['sink' => $filename]);
    }
    $this->response = new AcheckerCaptureResponse($filename, $data['url']);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * Returns a list of valid guidelines.
   *
   * @var array[]
   */
  public function getGuidelines() {
    return [
      'BITV1' => $this->t('BITV1: abbreviation of guideline bitv-1.0-(level-2)'),
      '508' => $this->t('508: abbreviation of guideline section-508'),
      'STANCA' => $this->t('STANCA: abbreviation of guideline stanca-act'),
      'WCAG1-A' => $this->t('WCAG1-A: abbreviation of guideline wcag-1.0-(level-a)'),
      'WCAG1-AA' => $this->t('WCAG1-AA: abbreviation of guideline wcag-1.0-(level-aa)'),
      'WCAG1-AAA' => $this->t('WCAG1-AAA: abbreviation of guideline wcag-1.0-(level-aaa)'),
      'WCAG2-A' => $this->t('WCAG2-A: abbreviation of guideline wcag-2.0-l1'),
      'WCAG2-AA' => $this->t('WCAG2-AA: abbreviation of guideline wcag-2.0-l2'),
      'WCAG2-AAA' => $this->t('WCAG2-AAA: abbreviation of guideline wcag-2.0-l3'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = \Drupal::configFactory()->get('web_page_archive.wpa_achecker_capture.settings');
    return [
      'guidelines' => $config->get('defaults.guidelines'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Use the Form API to create fields. Each field should have corresponding
    // entry in your config/module.schema.yml file.
    $form['guidelines'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Guidelines'),
      '#options' => $this->getGuidelines(),
      '#default_value' => $this->configuration['guidelines'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['guidelines'] = $form_state->getValue('guidelines');
  }

  /**
   * {@inheritdoc}
   */
  public function buildSystemSettingsForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->get('web_page_archive.wpa_achecker_capture.settings');

    $form['achecker_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('AChecker endpoint'),
      '#description' => $this->t('The URL to the AChecker endpoint'),
      '#default_value' => $config->get('system.achecker_endpoint'),
    ];

    $keys = ['' => $this->t('-- None --')];
    foreach (\Drupal::service('key.repository')->getKeys() as $key) {
      $keys[$key->id()] = $key->label();
    }
    asort($keys);

    $link_options = ['destination' => \Drupal::request()->getRequestUri()];
    $key_link = Link::fromTextAndUrl($this->t('Add a web service ID to Drupal via the Key module'), Url::fromRoute('entity.key.add_form', $link_options))->toString();
    $link_options = [
      'attributes' => [
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ],
    ];
    $register_link = Link::fromTextAndUrl($this->t('Register for an AChecker web service ID'), Url::fromUri('https://achecker.ca/register.php', $link_options))->toString();

    $form['achecker_web_service_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Web Service ID'),
      '#description' => $this->t('Specify which web service ID to use with AChecker:') . "<ul><li>{$key_link}</li><li>{$register_link}</li></ul>",
      '#options' => $keys,
      '#default_value' => $config->get('access_token'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupRevision($revision_id) {
    AcheckerCaptureResponse::cleanupRevision($revision_id);
  }

}
