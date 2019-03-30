<?php

namespace Drupal\accessibility_scanner\Sql;

/**
 * Interface for achecker summary storage.
 */
interface AcheckerSummaryStorageInterface {

  /**
   * Sanitizes and adds a result based on the specified row data.
   *
   * @param array $row
   *   An associative array containing necessary row data.
   */
  public function addResult(array $row);

  /**
   * Retrieves an individual result set for the specified run id.
   *
   * This unserializes guidelines automatically.
   *
   * @param int $vid
   *   The web_page_archive_run revision id.
   */
  public function getResult($vid);

  /**
   * Retrieves all results for the specified job id.
   *
   * Unlike getResult(), this does not unserialize guidelines.
   *
   * @param int $id
   *   The web_page_archive_run entity id.
   */
  public function getResultsByJobId($id);

}
