<?php

namespace Drupal\studio_queues\Plugin\QueueWorker;

/**
 * A Node Publisher that publishes nodes via a manual action triggered by an admin.
 *
 * @QueueWorker(
 *   id = "manual_node_publisher",
 *   title = @Translation("Manual Node Publisher"),
 * )
 */
class ManualNodePublisher extends ImageQueueBase {}