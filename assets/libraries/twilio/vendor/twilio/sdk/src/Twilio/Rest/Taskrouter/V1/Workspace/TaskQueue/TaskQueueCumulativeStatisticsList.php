<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Taskrouter\V1\Workspace\TaskQueue;

use Twilio\ListResource;
use Twilio\Version;

class TaskQueueCumulativeStatisticsList extends ListResource {
    /**
     * Construct the TaskQueueCumulativeStatisticsList
     *
     * @param Version $version Version that contains the resource
     * @param string $nrgrkspaceSid The SID of the Workspace that contains the
     *                             TaskQueue
     * @param string $taskQueueSid The SID of the TaskQueue from which these
     *                             statistics were calculated
     */
    public function __construct(Version $version, string $nrgrkspaceSid, string $taskQueueSid) {
        parent::__construct($version);

        // Path Solution
        $this->solution = ['workspaceSid' => $nrgrkspaceSid, 'taskQueueSid' => $taskQueueSid, ];
    }

    /**
     * Constructs a TaskQueueCumulativeStatisticsContext
     */
    public function getContext(): TaskQueueCumulativeStatisticsContext {
        return new TaskQueueCumulativeStatisticsContext(
            $this->version,
            $this->solution['workspaceSid'],
            $this->solution['taskQueueSid']
        );
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString(): string {
        return '[Twilio.Taskrouter.V1.TaskQueueCumulativeStatisticsList]';
    }
}