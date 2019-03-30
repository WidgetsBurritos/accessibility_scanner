<?php

namespace Drupal\accessibility_scanner\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\accessibility_scanner\Sql\AcheckerSummaryStorageInterface;
use Drupal\web_page_archive\Entity\WebPageArchiveInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AcheckerHistoryController.
 */
class AcheckerHistoryController extends ControllerBase {

  /**
   * Constructs a new AcheckerHistoryController.
   *
   * @param Drupal\accessibility_scanner\Sql\AcheckerSummaryStorageInterface $storage
   *   The achecker storage service.
   */
  public function __construct(AcheckerSummaryStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('accessibility_scanner.achecker_summary_storage')
    );
  }

  /**
   * Returns render array for the history content.
   *
   * @param \Drupal\web_page_archive\Entity\WebPageArchiveInterface $web_page_archive
   *   A web page archive config entity.
   *
   * @return array
   *   Render array for the history content.
   */
  public function historyContent(WebPageArchiveInterface $web_page_archive) {
    $run_entity = $web_page_archive->getRunEntity();
    $results = $this->storage->getResultsByJobId($run_entity->id());

    return [
      '#theme' => 'wpa-achecker-history',
      '#results' => $results,
      '#attached' => [
        'drupalSettings' => [
          'acheckerResults' => $results,
        ],
      ],
    ];
  }

  /**
   * Returns title for the history route.
   *
   * @param \Drupal\web_page_archive\Entity\WebPageArchiveInterface $web_page_archive
   *   A web page archive config entity.
   *
   * @return string
   *   The title of for the history route.
   */
  public function historyTitle(WebPageArchiveInterface $web_page_archive) {
    return $this->t('Accessibility History: @label', ['@label' => $web_page_archive->label()]);
  }

}
