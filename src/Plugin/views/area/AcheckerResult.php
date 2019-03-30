<?php

namespace Drupal\accessibility_scanner\Plugin\views\area;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\accessibility_scanner\Sql\AcheckerSummaryStorageInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views area handler to display some configurable result summary.
 *
 * @ViewsArea("achecker_result")
 */
class AcheckerResult extends AreaPluginBase {

  /**
   * The entity_type.manager service.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The achecker storage service.
   *
   * @var Drupal\accessibility_scanner\Sql\AcheckerSummaryStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setStorage($container->get('accessibility_scanner.achecker_summary_storage'));
    $instance->setEntityTypeManager($container->get('entity_type.manager'));
    return $instance;
  }

  /**
   * Sets the achecker storage service.
   *
   * @param \Drupal\accessibility_scanner\Sql\AcheckerSummaryStorageInterface $storage
   *   The achecker storage service.
   */
  public function setStorage(AcheckerSummaryStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * Sets the entity type manager service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    if (isset($this->view->argument['vid_1'])) {
      $run_id = intval($this->view->argument['vid_1']->getValue());
      if ($result = $this->storage->getResult($run_id)) {
        $run_entity = $this->entityTypeManager->getStorage('web_page_archive_run')->load($result['entity_id']);
        $job_id = $run_entity->getConfigEntity()->id();
        $route_params = ['web_page_archive' => $job_id];
        return [
          '#theme' => 'wpa-achecker-summary',
          '#result' => $result,
          '#trend_button' => [
            '#type' => 'link',
            '#url' => Url::fromRoute('entity.web_page_archive.achecker_history', $route_params),
            '#title' => $this->t('View Historical Trends'),
            '#attributes' => [
              'class' => ['button'],
            ],
          ],
        ];
      }
    }
    return [];
  }

}
