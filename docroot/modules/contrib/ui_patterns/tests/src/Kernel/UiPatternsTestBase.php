<?php

namespace Drupal\Tests\ui_patterns\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\KernelTests\KernelTestBase;

/**
 * Abstract base test class.
 *
 * @group ui_patterns
 */
abstract class UiPatternsTestBase extends KernelTestBase {

  /**
   * Get fixtures base path.
   *
   * @return string
   *   Fixtures base path.
   */
  protected static function getFixturePath() {
    return realpath(__DIR__ . '/../fixtures');
  }

  /**
   * Get fixture content.
   *
   * @param string $filepath
   *   File path.
   *
   * @return array
   *   A set of test data.
   */
  protected static function getFixtureContent($filepath) {
    return Yaml::decode(file_get_contents(static::getFixturePath() . '/' . $filepath));
  }

}
