/**
 * @file
 * Provides some very basic filtering functionality for achecker results.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.accessibilityScanner = Drupal.accessibilityScanner || {};

  Drupal.behaviors.accessibilityScannerGoogleChart = {
    attach: function attach(context) {
      google.charts.load('current', {packages: ['corechart', 'line']});
      google.charts.setOnLoadCallback(Drupal.accessibilityScanner.drawCharts);
    }
  };

  /**
   * Draws all report tables.
   */
  Drupal.accessibilityScanner.drawCharts = function () {
    Drupal.accessibilityScanner.drawPassFailChart();
    Drupal.accessibilityScanner.drawProblemChart();
  }

  /**
   * Draws the pass/fail table.
   */
  Drupal.accessibilityScanner.drawPassFailChart = function () {
    var data = new google.visualization.DataTable();
    data.addColumn('date', 'X');
    data.addColumn('number', Drupal.t('Total'));
    data.addColumn('number', Drupal.t('Passing'));
    data.addColumn('number', Drupal.t('Failing'));

    var problemRows = [];
    for (var i in drupalSettings.acheckerResults) {
      problemRows.push([
        new Date(drupalSettings.acheckerResults[i].timestamp * 1000),
        +drupalSettings.acheckerResults[i].total,
        +drupalSettings.acheckerResults[i].pass,
        +drupalSettings.acheckerResults[i].fail,
      ]);
    }

    data.addRows(problemRows);

    var options = {
      hAxis: { title: Drupal.t('Time') },
      vAxis: { title: Drupal.t('URLs') },
    };

    var chart = new google.visualization.LineChart(document.getElementById('achecker_pass_fail_chart'));
    chart.draw(data, options);
  }

  /**
   * Draws the problem table.
   */
  Drupal.accessibilityScanner.drawProblemChart = function () {
    var data = new google.visualization.DataTable();
    data.addColumn('date', 'X');
    data.addColumn('number', Drupal.t('Errors'));
    data.addColumn('number', Drupal.t('Likely Problems'));
    data.addColumn('number', Drupal.t('Potential Problems'));

    var problemRows = [];
    for (var i in drupalSettings.acheckerResults) {
      problemRows.push([
        new Date(drupalSettings.acheckerResults[i].timestamp * 1000),
        +drupalSettings.acheckerResults[i].num_of_errors,
        +drupalSettings.acheckerResults[i].num_of_likely_problems,
        +drupalSettings.acheckerResults[i].num_of_potential_problems,
      ]);
    }

    data.addRows(problemRows);

    var options = {
      hAxis: { title: Drupal.t('Time') },
      vAxis: { title: Drupal.t('Problems') },
    };

    var chart = new google.visualization.LineChart(document.getElementById('achecker_problem_chart'));
    chart.draw(data, options);
  }

})(jQuery, Drupal, drupalSettings);
