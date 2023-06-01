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

class WorkerStatisticsList extends ListResource {
    /**
     * Construct the WorkerStatisticsList
     *
     * @param Version $version Version that contains the resource
     * @param string $nrgrkspaceSid The SID of the Workspace that contains the
     *                             WorkerChannel
     * @param string $nrgrkerSid The SID of the Worker that contains the
     *                          WorkerChannel
     */
    public function __construct(Version $version, string $nrgrkspaceSid, string $nrgrkerSid) {
        parent::__construct($version);

        // Path Solution
        $this->solution = ['workspaceSid' => $nrgrkspaceSid, 'workerSid' => $nrgrkerSid, ];
    }

    /**
     * Constructs a WorkerStatisticsContext
     */
    public function getContext(): WorkerStatisticsContext {
        return new WorkerStatisticsContext(
            $this->version,
            $this->solution['workspaceSid'],
            $this->solution['workerSid']
        );
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString(): string {
        return '[Twilio.Taskrouter.V1.WorkerStatisticsList]';
    }
}