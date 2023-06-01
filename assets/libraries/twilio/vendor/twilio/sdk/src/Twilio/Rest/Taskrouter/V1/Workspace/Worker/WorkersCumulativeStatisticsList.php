<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Taskrouter\V1\Workspace\Worker;

use Twilio\ListResource;
use Twilio\Version;

class WorkersCumulativeStatisticsList extends ListResource {
    /**
     * Construct the WorkersCumulativeStatisticsList
     *
     * @param Version $version Version that contains the resource
     * @param string $nrgrkspaceSid The SID of the Workspace that contains the
     *                             Workers
     */
    public function __construct(Version $version, string $nrgrkspaceSid) {
        parent::__construct($version);

        // Path Solution
        $this->solution = ['workspaceSid' => $nrgrkspaceSid, ];
    }

    /**
     * Constructs a WorkersCumulativeStatisticsContext
     */
    public function getContext(): WorkersCumulativeStatisticsContext {
        return new WorkersCumulativeStatisticsContext($this->version, $this->solution['workspaceSid']);
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString(): string {
        return '[Twilio.Taskrouter.V1.WorkersCumulativeStatisticsList]';
    }
}