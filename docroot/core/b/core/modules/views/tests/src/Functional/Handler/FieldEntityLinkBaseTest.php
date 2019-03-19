<?php

namespace Drupal\Tests\views\Functional\Handler;

use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the core Drupal\views\Plugin\views\field\LinkBase handler.
 *
 * @group views
 */
class FieldEntityLinkBaseTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_link_base_links'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'language', 'views_ui'];

  /**
   * An array of created entities.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  protected $entities;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    // Create Article content type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    // Add languages and refresh the container so the entity manager will have
    // fresh data.
    ConfigurableLanguage::createFromLangcode('hu')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();
    $this->rebuildContainer();

    // Create some test entities. Every other entity is Hungarian while all
    // have a Spanish translation.
    $this->entities = [];
    for ($i = 0; $i < 5; $i++) {
      $entity = Node::create([
        'title' => $this->randomString(),
        'type' => 'article',
        'langcode' => $i % 2 === 0 ? 'hu' : 'en',
      ]);
      $entity->save();
      $translation = $entity->addTranslation('es');
      $translation->set('title', $entity->getTitle() . ' in Spanish');
      $translation->save();
      $this->entities[$i] = $entity;
    }

    $this->drupalLogin($this->rootUser);

  }

  /**
   * Tests link field.
   */
  public function testEntityLink() {
    $this->drupalGet('test-link-base-links');
    foreach ($this->entities as $entity) {
      /** @var \Drupal\Core\Language\LanguageInterface $language */
      foreach ($entity->getTranslationLanguages() as $language) {
        $entity = $entity->getTranslation($language->getId());
        // Test that the canonical, edit form and delete form links are all
        // shown in the proper format.
        $this->assertSession()->linkByHrefExists($entity->toUrl()->toString());
        $this->assertSession()->linkByHrefExists($entity->toUrl('edit-form')->toString());
        $this->assertSession()->linkByHrefExists($entity->toUrl('delete-form')->toString());
        // Test the 'output url as text' output.
        $this->assertSession()->pageTextContains($entity->toUrl()->toString());
      }
    }
  }

}
