<?php

namespace Drupal\accessibility_scanner\Sql;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Handles storage concerns for achecker summary.
 */
class AcheckerSummaryStorage implements AcheckerSummaryStorageInterface {

  use StringTranslationTrait;

  /**
   * Drupal database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Drupal time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new AcheckerSummaryStorage object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Drupal time service.
   */
  public function __construct(Connection $database, TimeInterface $time) {
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function addResult(array $result) {
    $required_keys = [
      'entity_id',
      'vid',
      'total',
      'pass',
      'fail',
      'num_of_errors',
      'num_of_likely_problems',
      'num_of_potential_problems',
      'guidelines',
    ];
    foreach ($required_keys as $required_key) {
      if (!isset($result[$required_key])) {
        throw new \Exception($this->t('@key is required', ['@key' => $required_key]));
      }
    }

    $values = [
      'entity_id' => (int) $result['entity_id'],
      'vid' => (int) $result['vid'],
      'total' => (int) $result['total'],
      'pass' => (int) $result['pass'],
      'fail' => (int) $result['fail'],
      'num_of_errors' => (int) $result['num_of_errors'],
      'num_of_likely_problems' => (int) $result['num_of_likely_problems'],
      'num_of_potential_problems' => (int) $result['num_of_potential_problems'],
      'guidelines' => serialize($result['guidelines']),
      'timestamp' => $this->time->getRequestTime(),
    ];

    $this->database
      ->upsert('wpa_achecker_summary')
      ->key('vid')
      ->fields(array_keys($values))
      ->values(array_values($values))
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getResult($vid) {
    $result = $this->database
      ->select('wpa_achecker_summary', 's')
      ->fields('s')
      ->condition('vid', (int) $vid)
      ->execute()->fetchAssoc();
    $result['guidelines'] = unserialize($result['guidelines']);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultsByJobId($id) {
    $result = $this->database
      ->select('wpa_achecker_summary', 's')
      ->fields('s')
      ->condition('entity_id', (int) $id)
      ->execute()->fetchAllAssoc('vid', \PDO::FETCH_ASSOC);
    return $result;
  }

}
