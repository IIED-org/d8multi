<?php

namespace Drupal\purge\Plugin\Purge\Processor;

/**
 * Processor for the 'drush p:queue-work' command.
 *
 * @PurgeProcessor(
 *   id = "drush_purge_queue_work",
 *   label = @Translation("Drush p:queue-work"),
 *   description = @Translation("Processor for the 'drush p:queue-work' command."),
 *   configform = "",
 * )
 */
class DrushQueueWorkProcessor extends ProcessorBase implements ProcessorInterface {

}
