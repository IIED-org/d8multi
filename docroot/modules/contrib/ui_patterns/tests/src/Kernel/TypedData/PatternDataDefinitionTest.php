<?php

namespace Drupal\Tests\ui_patterns\Kernel\TypedData;

use Drupal\Tests\ui_patterns\Kernel\UiPatternsTestBase;
use Drupal\ui_patterns\TypedData\PatternDataDefinition;

/**
 * @coversDefaultClass \Drupal\ui_patterns\TypedData\PatternDataDefinition
 *
 * @group ui_patterns
 */
class PatternDataDefinitionTest extends UiPatternsTestBase {

  /**
   * Test plugin validation.
   *
   * @dataProvider validationProvider
   */
  public function testValidation($pattern, $messages) {
    $definition = PatternDataDefinition::create();
    $violations = \Drupal::typedDataManager()->create($definition, $pattern)->validate();

    $actual = [];
    foreach ($violations as $violation) {
      $actual[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
    }
    $this->assertEquals($messages, $actual);
  }

  /**
   * Return validation data.
   *
   * @return array
   *   Pattern validation data.
   */
  public static function validationProvider() {
    return static::getFixtureContent('validation.yml');
  }

}
