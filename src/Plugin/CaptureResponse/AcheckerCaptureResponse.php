<?php

namespace Drupal\accessibility_scanner\Plugin\CaptureResponse;

use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
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
    $summary_obj = isset($contents) && isset($contents->summary) ? $contents->summary : NULL;

    $route_params = [
      'web_page_archive_run_revision' => $options['vid'],
      'delta' => $options['delta'],
    ];

    $render = [
      '#theme' => 'wpa-achecker-preview',
      '#summary' => $this->transformSummary($summary_obj),
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

      $contents = file_get_contents($this->content);
      $contents = str_replace(["\n", "\r", "\t"], '', $contents);
      $contents = trim(str_replace('"', "'", $contents));
      $contents = @simplexml_load_string($contents);

      return $contents;
    }
    return '';
  }

  /**
   * Transform summary object into a readable array.
   */
  private function transformSummary($summary_obj) {
    if (!isset($summary_obj->status)) {
      $status = $this->t('Invalid');
    }
    else {
      $status = $summary_obj->status == 'PASS' ? $this->t('Pass') : $this->t('Fail');
    }

    return [
      'num_of_errors' => isset($summary_obj->NumOfErrors) ? intval($summary_obj->NumOfErrors) : 0,
      'num_of_likely_problems' => isset($summary_obj->NumOfLikelyProblems) ? intval($summary_obj->NumOfLikelyProblems) : 0,
      'num_of_potential_problems' => isset($summary_obj->NumOfPotentialProblems) ? intval($summary_obj->NumOfPotentialProblems) : 0,
      'status' => $status,
      'guidelines' => isset($summary_obj->guidelines->guideline) ? (array) $summary_obj->guidelines->guideline : [],
    ];
  }

  /**
   * Transforms result object into a readable array.
   */
  private function transformResults($result_obj) {
    if (isset($result_obj)) {
      foreach ($result_obj->result as $result) {
        yield [
          'result_type' => (string) $result->resultType,
          'line_num' => (int) $result->lineNum,
          'column_num' => (int) $result->columnNum,
          'error_msg' => (string) $result->errorMsg,
          'error_source_code' => (string) $result->errorSourceCode,
          'repair' => (string) $result->repair,
        ];
      }
    }
  }

  /**
   * Renders full mode.
   */
  private function renderFull(array $options) {
    $contents = $this->retrieveFileContents();
    $summary_obj = isset($contents) && isset($contents->summary) ? $contents->summary : NULL;
    $results_obj = isset($contents) && isset($contents->results) ? $contents->results : NULL;

    $render = [
      '#theme' => 'wpa-achecker-full-report',
      '#summary' => $this->transformSummary($summary_obj),
      '#results' => $this->transformResults($results_obj),
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
