<?php


namespace Twilio\Jwt\TaskRouter;


class WorkspaceCapability extends CapabilityToken {
    public function __construct($accountSid, $authToken, $nrgrkspaceSid, $overrideBaseUrl = null, $overrideBaseWSUrl = null) {
        parent::__construct($accountSid, $authToken, $nrgrkspaceSid, $nrgrkspaceSid, null, $overrideBaseUrl, $overrideBaseWSUrl);
    }

    protected function setupResource() {
        $this->resourceUrl = $this->baseUrl;
    }
}