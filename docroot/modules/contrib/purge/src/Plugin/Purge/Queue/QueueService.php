<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DestructableInterface;
use Drupal\purge\Logger\LoggerServiceInterface;
use Drupal\purge\ModifiableServiceBaseTrait;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;
use Drupal\purge\Plugin\Purge\Queue\Exception\UnexpectedServiceConditionException;
use Drupal\purge\Plugin\Purge\Queuer\QueuerInterface;
use Drupal\purge\ServiceBase;

/**
 * Provides the service that lets invalidations interact with a queue backend.
 */
class QueueService extends ServiceBase implements QueueServiceInterface, DestructableInterface {
  use ModifiableServiceBaseTrait;

  /**
   * The transaction buffer in which invalidation objects temporarily stay.
   *
   * @var \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface
   */
  protected $buffer;

  /**
   * The factory for configuration objects.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Logger channel specific to the queue service.
   *
   * @var \Drupal\purge\Logger\LoggerChannelPartInterface
   */
  protected $logger;

  /**
   * The 'purge.invalidation.factory' service.
   *
   * @var \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface
   */
  protected $purgeInvalidationFactory;

  /**
   * The 'purge.logger' service.
   *
   * @var \Drupal\purge\Logger\LoggerServiceInterface
   */
  protected $purgeLogger;

  /**
   * The 'purge.purgers' service.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected $purgePurgers;

  /**
   * The 'purge.queue.stats' service.
   *
   * @var \Drupal\purge\Plugin\Purge\Queue\StatsTrackerInterface
   */
  protected $purgeQueueStats;

  /**
   * The Queue (plugin) object in which all items are stored.
   *
   * @var \Drupal\purge\Plugin\Purge\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The default backend that gets loaded on empty configuration.
   */
  const DEFAULT_PLUGIN = 'database';

  /**
   * The backend that gets loaded when the configured backend disappeared.
   */
  const FALLBACK_PLUGIN = 'null';

  /**
   * Construct the queue service.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The plugin manager for this service.
   * @param \Drupal\purge\Logger\LoggerServiceInterface $purge_logger
   *   Logging services for the purge module and its submodules.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\purge\Plugin\Purge\Queue\TxBufferInterface $purge_queue_txbuffer
   *   The transaction buffer.
   * @param \Drupal\purge\Plugin\Purge\Queue\StatsTrackerInterface $purge_queue_stats
   *   The queue statistics tracker.
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface $purge_invalidation_factory
   *   The service that instantiates invalidation objects for queue items.
   * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purge_purgers
   *   The purgers service.
   */
  public function __construct(PluginManagerInterface $plugin_manager, LoggerServiceInterface $purge_logger, ConfigFactoryInterface $config_factory, TxBufferInterface $purge_queue_txbuffer, StatsTrackerInterface $purge_queue_stats, InvalidationsServiceInterface $purge_invalidation_factory, PurgersServiceInterface $purge_purgers) {
    $this->pluginManager = $plugin_manager;
    $this->purgeLogger = $purge_logger;
    $this->configFactory = $config_factory;
    $this->purgeInvalidationFactory = $purge_invalidation_factory;
    $this->purgePurgers = $purge_purgers;
    $this->purgeQueueStats = $purge_queue_stats;
    $this->buffer = $purge_queue_txbuffer;
    $this->logger = $this->purgeLogger->get('queue');
  }

  /**
   * {@inheritdoc}
   */
  public function add(QueuerInterface $queuer, array $invalidations) {
    foreach ($invalidations as $invalidation) {
      if (!$this->buffer->has($invalidation)) {
        $this->buffer->set($invalidation, TxBuffer::ADDING);
      }
    }
    $this->logger->debug("@queuer: added @no items.", [
      '@queuer' => $queuer->getPluginId(),
      '@no' => count($invalidations),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function claim($claims = NULL, $lease_time = NULL) {
    $this->commitAdding();
    $this->commitReleasing();
    $this->commitDeleting();

    // When the claim number or lease_time isn't passed, the capacity tracker
    // will kindly give it to us. Then multiply the lease time with the claims.
    $tracker = $this->purgePurgers->capacityTracker();
    if (is_null($claims)) {
      if (!($claims = $tracker->getRemainingInvalidationsLimit())) {
        $this->logger->debug("no more items can be claimed within this request.");
        return [];
      }
    }
    if (is_null($lease_time)) {
      $lease_time = $tracker->getLeaseTimeHint($claims);
    }
    else {
      $lease_time = $claims * $lease_time;
    }

    // Claim one or several items out of the queue or finish the call.
    $this->initializeQueue();
    if ($claims === 1) {
      if (!($item = $this->queue->claimItem($lease_time))) {
        $this->logger->debug("attempt to claim 1 item failed.");
        return [];
      }
      $items = [$item];
    }
    elseif (!($items = $this->queue->claimItemMultiple($claims, $lease_time))) {
      $this->logger->debug("attempt to claim @no items failed.", ['@no' => $claims]);
      return [];
    }

    // Iterate the $items array and replace each with full instances.
    foreach ($items as $i => $item) {

      // See if the inv. object is still buffered locally, else instantiate it.
      if (!($inv = $this->buffer->getByProperty('item_id', $item->item_id))) {
        $inv = $this->purgeInvalidationFactory->getFromQueueData($item->data);
      }

      // Ensure it is buffered, has the right state and properties, then add it.
      $this->buffer->set($inv, TxBuffer::CLAIMED);
      $this->buffer->setProperty($inv, 'item_id', $item->item_id);
      $this->buffer->setProperty($inv, 'created', $item->created);
      $items[$i] = $inv;
    }

    $output = implode(', ', $items);
    $this->logger->debug("claimed @no items: @output", ['@no' => count($items), '@output' => $output]);
    return $items;
  }

  /**
   * Commit all actions in the internal buffer to the queue.
   *
   * @param bool $sync_stat
   *   Sync the numberOfItems statistic after the queue content has changed.
   */
  public function commit($sync_stat = TRUE) {
    if (!count($this->buffer)) {
      return;
    }
    $this->commitAdding();
    $this->commitReleasing();
    $this->commitDeleting();

    // If tasked to do so, sync the numberOfItems stat since the queue changed.
    if ($sync_stat) {
      $this->purgeQueueStats
        ->numberOfItems()
        ->set($this->numberOfItems());
    }
  }

  /**
   * Commit all adding invalidations in the buffer to the queue.
   */
  private function commitAdding() {
    $items = $this->buffer->getFiltered(TxBuffer::ADDING);
    if (!($items_count = count($items))) {
      return;
    }

    // Since we do have items to add, initialize the queue.
    $this->initializeQueue();

    // Small anonymous function that fetches the 'data' field for createItem()
    // and createItemMultiple() - keeps queue plugins out of Purge specifics.
    $getProxiedData = function ($invalidation) {
      $proxy = new ProxyItem($invalidation, $this->buffer);
      return $proxy->data;
    };

    // Add just one item to the queue using createItem() on the queue.
    if ($items_count === 1) {
      $invalidation = current($items);
      if (!($id = $this->queue->createItem($getProxiedData($invalidation)))) {
        throw new UnexpectedServiceConditionException("The queue returned FALSE on createItem().");
      }
      else {
        $this->buffer->set($invalidation, TxBuffer::RELEASED);
        $this->buffer->setProperty($invalidation, 'item_id', $id);
        $this->buffer->setProperty($invalidation, 'created', time());
      }
    }

    // Add multiple to the queue at once with ::createItemMultiple().
    else {
      $item_chunks = array_chunk($items, 1000);
      if ($item_chunks) {
        foreach ($item_chunks as $chunk) {
          $data_items = [];
          foreach ($chunk as $invalidation) {
            $data_items[] = $getProxiedData($invalidation);
          }
          if (!($ids = $this->queue->createItemMultiple($data_items))) {
            throw new UnexpectedServiceConditionException(
              "The queue returned FALSE on createItemMultiple().");
          }
          foreach ($chunk as $key => $invalidation) {
            if (!array_key_exists($key, $ids)) {
              continue;
            }
            $this->buffer->set($invalidation, TxBuffer::ADDED);
            $this->buffer->setProperty($invalidation, 'item_id', $ids[$key]);
            $this->buffer->setProperty($invalidation, 'created', time());
          }
        }
      }
    }

    $this->purgeQueueStats->updateTotals($items);
  }

  /**
   * Commit all releasing invalidations in the buffer to the queue.
   */
  private function commitReleasing() {
    $items = $this->buffer->getFiltered(TxBuffer::RELEASING);
    if (!($items_count = count($items))) {
      return;
    }
    $this->initializeQueue();
    if ($items_count === 1) {
      $invalidation = current($items);
      $this->queue->releaseItem(new ProxyItem($invalidation, $this->buffer));
      $this->buffer->set($invalidation, TxBuffer::RELEASED);
    }
    else {
      $proxyitems = [];
      foreach ($items as $item) {
        $proxyitems[] = new ProxyItem($item, $this->buffer);
      }
      $this->queue->releaseItemMultiple($proxyitems);
      $this->buffer->set($items, TxBuffer::RELEASED);
    }

    $this->purgeQueueStats->updateTotals($items);
  }

  /**
   * Commit all deleting invalidations in the buffer to the queue.
   */
  private function commitDeleting() {
    $items = $this->buffer->getFiltered(TxBuffer::DELETING);
    if (!($items_count = count($items))) {
      return;
    }
    $this->initializeQueue();
    if ($items_count === 1) {
      $invalidation = current($items);
      $this->queue->deleteItem(new ProxyItem($invalidation, $this->buffer));
      $this->buffer->delete($invalidation);
    }
    else {
      $proxyitems = [];
      foreach ($items as $item) {
        $proxyitems[] = new ProxyItem($item, $this->buffer);
      }
      $this->queue->deleteItemMultiple($proxyitems);
      $this->buffer->delete($items);
    }

    $this->purgeQueueStats->updateTotals($items);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $invalidations) {
    $this->buffer->set($invalidations, TxBuffer::DELETING);
    $count = count($invalidations);
    $this->logger->debug("deleting @no items.", ['@no' => $count]);
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {

    // The queue service attempts to collect all actions done for invalidations
    // in $this->buffer, and commits them as infrequent as possible during
    // runtime. At minimum it will commit to the underlying queue plugin upon
    // shutdown and by doing so, attempts to reduce and bundle the amount of
    // work the queue has to do (e.g., queries, disk writes, mallocs). This
    // helps purge to scale better and should cause no noticeable side-effects.
    $this->commit();
  }

  /**
   * {@inheritdoc}
   */
  public function emptyQueue() {
    $this->initializeQueue();
    $this->buffer->deleteEverything();
    $this->queue->deleteQueue();
    $this->purgeQueueStats->numberOfItems()->set(0);
    $this->logger->debug("emptied the queue.");
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->getPlugins()[current($this->getPluginsEnabled())]['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->getPlugins()[current($this->getPluginsEnabled())]['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins() {
    if (is_null($this->plugins)) {
      $this->plugins = $this->pluginManager->getDefinitions();
      unset($this->plugins[self::FALLBACK_PLUGIN]);
    }
    return $this->plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginsEnabled() {
    if (is_null($this->pluginsEnabled)) {
      $plugin_ids = array_keys($this->getPlugins());
      $this->pluginsEnabled = [];

      // The queue service always interacts with just one underlying queue,
      // which is stored in configuration. By default, we use the DEFAULT_PLUGIN
      // or the FALLBACK_PLUGIN in case nothing else loads.
      $plugin_id = $this->configFactory->get('purge.plugins')->get('queue');
      if (is_null($plugin_id)) {
        $this->pluginsEnabled[] = self::DEFAULT_PLUGIN;
      }
      elseif (!in_array($plugin_id, $plugin_ids)) {
        $this->pluginsEnabled[] = self::FALLBACK_PLUGIN;
      }
      else {
        $this->pluginsEnabled[] = $plugin_id;
      }
    }
    return $this->pluginsEnabled;
  }

  /**
   * Initialize the transaction buffer and queue backend.
   */
  protected function initializeQueue() {
    if (!is_null($this->queue)) {
      return;
    }

    // Lookup the plugin ID and instantiate the queue.
    $plugin_id = current($this->getPluginsEnabled());
    $this->logger->debug("backend: @plugin_id.", ['@plugin_id' => $plugin_id]);
    $this->queue = $this->pluginManager->createInstance($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function handleResults(array $invalidations) {
    $counters = [
      'succeeded' => 0,
      'failed' => 0,
      'new' => 0,
    ];

    foreach ($invalidations as $invalidation) {

      // Although PurgersServiceInterface::invalidate() always resets context
      // after purging, we cannot rely on what happened in between. By making
      // sure its reset, we know we will always get the general state below.
      $invalidation->setStateContext(NULL);

      // Mark succeeded objects as deleting in the buffer.
      if ($invalidation->getState() === InvalidationInterface::SUCCEEDED) {
        $this->buffer->set($invalidation, TxBuffer::DELETING);
        $counters['succeeded']++;
      }
      // FRESH, PROCESSING, FAILED and NOT_SUPPORTED all go back to the queue.
      else {
        if (!$this->buffer->has($invalidation)) {
          $this->buffer->set($invalidation, TxBuffer::ADDING);
          $counters['new']++;
        }
        else {
          $this->buffer->set($invalidation, TxBuffer::RELEASING);
          $counters['failed']++;
        }
      }
    }

    // Log what happened (but only if info logging is enabled).
    if ($this->logger->isDebuggingEnabled()) {
      $this->logger->debug(
        "handled @no returned items: @results",
        ['@no' => count($invalidations), '@results' => json_encode($counters)]
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function numberOfItems() {
    $this->commit(FALSE);
    $this->initializeQueue();
    return $this->queue->numberOfItems();
  }

  /**
   * {@inheritdoc}
   */
  public function release(array $invalidations) {
    $this->buffer->set($invalidations, TxBuffer::RELEASING);
    $this->logger->debug("deleting @no items.", ['@no' => count($invalidations)]);
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    parent::reload();
    if (!is_null($this->queue)) {
      $this->commit(FALSE);
    }
    $this->buffer->deleteEverything();
    // phpcs:ignore DrupalPractice.Objects.GlobalDrupal.GlobalDrupal -- We're already injecting, this is needed for tests to work.
    $this->configFactory = \Drupal::configFactory();
    $this->queue = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function selectPage($page = 1) {
    $this->initializeQueue();
    $this->commit();
    $immutables = [];
    foreach ($this->queue->selectPage($page) as $item) {
      $immutables[] = $this->purgeInvalidationFactory
        ->getImmutableFromQueueData($item->data);
    }
    return $immutables;
  }

  /**
   * {@inheritdoc}
   */
  public function selectPageLimit($set_limit_to = NULL) {
    $this->initializeQueue();
    return $this->queue->selectPageLimit($set_limit_to);
  }

  /**
   * {@inheritdoc}
   */
  public function selectPageMax() {
    $this->initializeQueue();
    return $this->queue->selectPageMax();
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginsEnabled(array $plugin_ids) {
    if (count($plugin_ids) !== 1) {
      throw new \LogicException('Incorrect number of arguments.');
    }
    $plugin_id = current($plugin_ids);
    if (!isset($this->pluginManager->getDefinitions()[$plugin_id])) {
      throw new \LogicException('Invalid plugin_id.');
    }
    $this->configFactory->getEditable('purge.plugins')->set('queue', $plugin_id)->save();
    $this->logger->debug("switched backend to @id.", ['@id' => $plugin_id]);
    $this->reload();
    $this->emptyQueue();
  }

}
