INTRODUCRION
------------

The Accessibility Scanner module allows you to use Drupal in combination with
achecker to perform web accessibility scans on local and remote websites based
of a list of URLs or XML sitemaps, all within the familiar Drupal admin
interface.
* For a full description of the module, visit the project page:
   https://www.drupal.org/project/accessibility_scanner

REQUIREMENTS
------------

This module requires the following modules:

 * Key (https://www.drupal.org/project/key)
 * Web Page Archive (https://www.drupal.org/project/web_page_archive)

INSTALLATION
------------

Install the Devel module as you would normally install a contributed Drupal
   module. Visit https://www.drupal.org/node/1897420 for further information.

CONFIGURATION
-------------

 * Getting Started with Accessibility Scanner
   - https://www.drupal.org/docs/8/modules/accessibility-scanner/getting-started-with-accessibility-scanner-0

 * Permissions
   - To follow along with this guide, you will need the following permissions:
      - administer web page archive
      - view web page archive results
      - administer keys

 * When you first install the module, you will need to configure your AChecker credentials.
   - First, we need to use the key module for managing the AChecker web service id.
   To do so, go to Configuration -> Keys and then click the Add Key button
   (or just navigate to /admin/config/system/keys/add).
   Please review the key module documentation for best practices on key management.
   - Next, we need to tell accessibility scanner where to find the web service id.
   Go to Configuration -> Web Page Archive -> Settings
   (or just navigate to /admin/config/system/web-page-archive/settings).

 * Configuring a Scanner Job
   - Familiarize yourself with creating capture jobs in web page archive.
   When you get to the Configuring Capture Utilities section,
   specify Achecker Accessibility Scanner and then click the Add button.
   - Next learn about running capture jobs on web page archive.
   - Upon run completion you should see scan results,
   but refer to viewing capture job results in web page archive, if you need more context.
      - Preview Mode - Provides an overview of scan results (e.g. pass/fail, # of errors, guidelines used, etc)
      - Full Mode - More detailed scan results on a per-URL basis

TROUBLESHOOTING
---------------

 * Required module Web Page Archive(https://www.drupal.org/project/web_page_archive) need be installed via composer:

   - Package mtdowling/cron-expression can be not installed by default installation.

MAINTAINERS
-----------

Current maintainers:
 * David Stinemetze (WidgetsBurritossun) - https://www.drupal.org/u/widgetsburritos
 * Paul Maddern (pobster) - https://www.drupal.org/u/pobster
 * Adrianna Flores (vessel_adrift) - https://www.drupal.org/u/vessel_adrift

This project has been supported by:
 * Rackspace Hosting Provided development resources - https://www.drupal.org/rackspace-hosting
