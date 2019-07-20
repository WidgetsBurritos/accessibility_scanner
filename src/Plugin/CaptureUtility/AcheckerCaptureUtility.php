<?php

namespace Drupal\accessibility_scanner\Plugin\CaptureUtility;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\accessibility_scanner\Plugin\CaptureResponse\AcheckerCaptureResponse;
use Drupal\key\KeyRepositoryInterface;
use Drupal\web_page_archive\Plugin\ConfigurableCaptureUtilityBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

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

  use DependencySerializationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The key repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The HTTP client to fetch the files with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a new AcheckerCaptureUtility.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The key repository.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ConfigFactoryInterface $config_factory, KeyRepositoryInterface $key_repository, Request $request, ClientInterface $http_client) {
    $this->configFactory = $config_factory;
    $this->keyRepository = $key_repository;
    $this->request = $request;
    $this->httpClient = $http_client;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('accessibility_scanner'),
      $container->get('config.factory'),
      $container->get('key.repository'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('http_client')
    );
  }

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
    $capture_utility_settings = $this->configFactory->get('web_page_archive.wpa_achecker_capture.settings')->get('system');

    if (empty($capture_utility_settings['achecker_endpoint'])) {
      throw new \Exception('Invalid AChecker endpoint');
    }

    if (empty($capture_utility_settings['achecker_web_service_id'])) {
      throw new \Exception('Missing AChecker Web Service ID');
    }

    $key = $this->keyRepository->getKey($capture_utility_settings['achecker_web_service_id']);
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
      $this->httpClient->request('GET', $url, ['sink' => $filename]);
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
    return [
      'guidelines' => $this->configFactory->get('web_page_archive.wpa_achecker_capture.settings')->get('defaults.guidelines'),
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
    $form['achecker_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('AChecker endpoint'),
      '#description' => $this->t('The URL to the AChecker endpoint'),
      '#default_value' => $this->configFactory->get('web_page_archive.wpa_achecker_capture.settings')->get('system.achecker_endpoint'),
    ];

    $keys = ['' => $this->t('-- None --')];
    foreach ($this->keyRepository->getKeys() as $key) {
      $keys[$key->id()] = $key->label();
    }
    asort($keys);

    $link_options = ['destination' => $this->request->getRequestUri()];
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
      '#default_value' => $this->configFactory->get('web_page_archive.wpa_achecker_capture.settings')->get('access_token'),
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
