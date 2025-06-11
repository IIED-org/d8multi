<?php

namespace Drupal\ui_patterns\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\ui_patterns\UiPatterns;

/**
 * Hooks related to libraries.
 */
class LibraryHooks {

  /**
   * Implements hook_library_info_build().
   */
  #[Hook('library_info_build')]
  public function infoBuild(): array {
    $definitions = [];
    foreach (UiPatterns::getManager()->getPatterns() as $pattern) {
      $definitions += $pattern->getLibraryDefinitions();
    }
    return $definitions;
  }

}
