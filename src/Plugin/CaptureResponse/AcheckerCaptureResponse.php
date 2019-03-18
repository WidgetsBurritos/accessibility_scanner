<?php

namespace Drupal\accessibility_scanner\Plugin\CaptureResponse;

use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\serialization\Encoder\XmlEncoder;
use Drupal\web_page_archive\Plugin\CaptureResponseInterface;
use Drupal\web_page_archive\Plugin\CaptureResponse\UriCaptureResponse;

/**
 * Achecker capture response.
 */
class AcheckerCaptureResponse extends UriCaptureResponse {

  /**
   * {@inheritdoc}
   */
  public static function getId() {
    return 'wpa_achecker_capture_response';
  }

  /**
   * {@inheritdoc}
   */
  public function renderable(array $options = []) {
    return (isset($options['mode']) && $options['mode'] == 'full') ?
      $this->renderFull($options) : $this->renderPreview($options);
  }

  /**
   * Renders "preview" mode.
   */
  private function renderPreview(array $options) {
    $contents = $this->retrieveFileContents();

    $route_params = [
      'web_page_archive_run_revision' => $options['vid'],
      'delta' => $options['delta'],
    ];

    $render = [
      '#theme' => 'wpa-achecker-preview',
      '#summary' => $contents->summary,
      '#url' => $this->captureUrl,
      '#view_button' => [
        '#type' => 'link',
        '#url' => Url::fromRoute('entity.web_page_archive.modal', $route_params),
        '#title' => $this->t('View Detailed Report'),
        '#attributes' => [
          'class' => ['button', 'use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode(['width' => 1280]),
        ],
      ],
    ];

    return $render;
  }

  /**
   * Retrieves file contents.
   */
  public function retrieveFileContents() {
    if (!empty($this->content) && file_exists($this->content)) {

      $contents= file_get_contents($this->content);
      $contents = str_replace(["\n", "\r", "\t"], '', $contents);
      $contents = trim(str_replace('"', "'", $contents));
      $contents = simplexml_load_string($contents);
      return $contents;

      return simplexml_load_file($this->content);
      return \Drupal::service('serializer')->deserialize(file_get_contents($this->content));
      return $xml_encoder->decode(file_get_contents($this->content), 'xml');
    }
    return '';
  }

  /**
   * Renders full mode.
   */
  private function renderFull(array $options) {
    $contents = $this->retrieveFileContents();

    $render = [
      '#theme' => 'wpa-achecker-full-report',
      '#summary' => $contents->summary,
      '#results' => $contents->results,
      '#url' => $this->captureUrl,
    ];

    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public static function compare(CaptureResponseInterface $a, CaptureResponseInterface $b, array $compare_utilities, array $tags = [], array $data = []) {
    $tags[] = 'achecker';
    return parent::compare($a, $b, $compare_utilities, $tags, $data);
  }

}
