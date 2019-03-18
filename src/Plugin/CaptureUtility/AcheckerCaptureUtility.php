<?php

namespace Drupal\accessibility_scanner\Plugin\CaptureUtility;

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
   * Most recent response.
   *
   * @var string|null
   */
  private $response = NULL;

  /**
   * {@inheritdoc}
   */
  public function capture(array $data = []) {
    // Handle missing URLs.
    if (!isset($data['url'])) {
      throw new \Exception('Capture URL is required');
    }
    // TODO: Can customize/self-host endpoint?
    // TODO: Pull web service id from key module?
    // TODO: Pull guide from config.
    $endpoint = 'https://achecker.ca/checkacc.php';
    $params = [
      'uri' => $data['url'],
      'id' => \Drupal::state()->get('mytempkey'),
      'output' => 'rest',
      'guide' => '508',
    ];
    $param_str = http_build_query($params);
    $url = "{$endpoint}?{$param_str}";

    // Determine file locations.
    $filename = $this->getFileName($data, 'xml');

    // Save xml and set our response.
    \Drupal::httpClient()->request('GET', $url, ['sink' => $filename]);
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
  public function cleanupRevision($revision_id) {
    AcheckerCaptureResponse::cleanupRevision($revision_id);
  }

}
