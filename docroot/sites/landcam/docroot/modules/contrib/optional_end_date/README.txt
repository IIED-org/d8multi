Optional end date

Make the end date in a Date range field (DateRangeItem) optional.

An extra "Optional end date" checkbox is added to the Date range field type
Storage settings. When the box is checked, the end date is no longer required.

This will mimic the behavior of of optional end dates in Drupal 8.6.x. So when
8.6.x is ready, you should be able to uninstall this module after updating, and
use the core implementation instead.

See https://www.drupal.org/project/drupal/issues/2794481 for more information
about the core implementation.
