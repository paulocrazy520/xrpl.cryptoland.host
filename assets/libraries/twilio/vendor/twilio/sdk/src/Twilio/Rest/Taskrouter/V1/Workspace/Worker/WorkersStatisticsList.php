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

class WorkersStatisticsList extends ListResource {
    /**
     * Construct the WorkersStatisticsList
     *
     * @param Version $version Version that contains the resource
     * @param string $nrgrkspaceSid The SID of the Workspace that contains the Worker
     */
    public function __construct(Version $version, string $nrgrkspaceSid) {
        parent::__construct($version);

        // Path Solution
        $this->solution = ['workspaceSid' => $nrgrkspaceSid, ];
    }

    /**
     * Constructs a WorkersStatisticsContext
     */
    public function getContext(): WorkersStatisticsContext {
        return new WorkersStatisticsContext($this->version, $this->solution['workspaceSid']);
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString(): string {
        return '[Twilio.Taskrouter.V1.WorkersStatisticsList]';
    }
}