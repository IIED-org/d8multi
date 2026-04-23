<?php

namespace Drupal\Tests\ui_patterns\Unit\Element;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\ui_patterns\Unit\UiPatternsTestBase;
use Drupal\ui_patterns\Element\PatternPreview;

/**
 * @coversDefaultClass \Drupal\ui_patterns\Element\PatternPreview
 *
 * @group ui_patterns
 */
class PatternPreviewTest extends UiPatternsTestBase {

  /**
   * Test getPreviewMarkup.
   *
   * @dataProvider previewMarkupProvider
   *
   * @covers ::getPreviewMarkup
   */
  public function testPreviewMarkup($actual, $expected) {
    $result = PatternPreview::getPreviewMarkup($actual);
    $this->assertEquals($expected, $result);
  }

  /**
   * Provider.
   *
   * @return array
   *   Data.
   */
  public static function previewMarkupProvider() {
    return Yaml::decode(file_get_contents(static::getFixturePath() . '/preview_markup.yml'));
  }

}
