<?php

namespace Drupal\accessibility_scanner\EventSubscriber;

use Drupal\accessibility_scanner\Sql\AcheckerSummaryStorageInterface;
use Drupal\web_page_archive\Entity\WebPageArchiveRunInterface;
use Drupal\web_page_archive\Event\CaptureJobCompleteEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to the web page archive events.
 */
class WebPageArchiveEventSubscriber implements EventSubscriberInterface {

  /**
   * Storage service.
   *
   * @var Drupal\accessibility_scanner\Sql\AcheckerSummaryStorageInterface
   */
  protected $acheckerStorage;

  /**
   * Constructs a new WebPageArchiveEventSubscriber instance().
   *
   * @param Drupal\accessibility_scanner\Sql\AcheckerSummaryStorageInterface $achecker_storage
   *   Achecker storage service.
   */
  public function __construct(AcheckerSummaryStorageInterface $achecker_storage) {
    $this->acheckerStorage = $achecker_storage;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      CaptureJobCompleteEvent::EVENT_NAME => 'captureComplete',
    ];
  }

  /**
   * Checks if a run comparison entity has the achecker capture utility.
   *
   * @param \Drupal\web_page_archive\Entity\WebPageArchiveRunInterface $web_page_archive_run
   *   The web page archive run entity to check for achecker.
   */
  private function hasAcheckerCaptureUtility(WebPageArchiveRunInterface $web_page_archive_run) {
    foreach ($web_page_archive_run->getCaptureUtilities() as $capture_utility) {
      $capture_utility_value = $capture_utility->getValue();
      if (isset($capture_utility_value['wpa_achecker_capture'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * React to a capture job completing.
   *
   * @param \Drupal\web_page_archive\Event\CaptureJobCompleteEvent $event
   *   Capture job completion event.
   */
  public function captureComplete(CaptureJobCompleteEvent $event) {
    if (!$this->hasAcheckerCaptureUtility($event->runEntity)) {
      return;
    }

    $result = [
      'entity_id' => $event->runEntity->id(),
      'vid' => $event->runEntity->getRevisionId(),
      'total' => 0,
      'pass' => 0,
      'fail' => 0,
      'num_of_errors' => 0,
      'num_of_likely_problems' => 0,
      'num_of_potential_problems' => 0,
      'guidelines' => [],
    ];
    $captured = $event->runEntity->getCapturedArray();
    foreach ($captured as $captured_item) {
      $value = $captured_item->getValue();
      if (empty($value['value'])) {
        continue;
      }
      $capture_item_result = unserialize($value['value']);
      if (!isset($capture_item_result['capture_response']) || $capture_item_result['capture_response']->getId() != 'wpa_achecker_capture_response') {
        continue;
      }
      $summary = $capture_item_result['capture_response']->retrieveFileContents()->summary;
      $result['total']++;
      if ($summary->status == 'PASS') {
        $result['pass']++;
      }
      else {
        $result['fail']++;
      }
      $result['num_of_errors'] += intval($summary->NumOfErrors);
      $result['num_of_likely_problems'] += intval($summary->NumOfLikelyProblems);
      $result['num_of_potential_problems'] += intval($summary->NumOfPotentialProblems);
      foreach ($summary->guidelines as $guideline) {
        $guideline_str = (string) $guideline->guideline;
        if (!isset($result['guidelines'][$guideline_str])) {
          $result['guidelines'][$guideline_str] = $guideline_str;
        }
      }
    }

    $this->acheckerStorage->addResult($result);
  }

}
