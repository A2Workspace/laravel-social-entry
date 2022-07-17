<?php

namespace A2Workspace\SocialEntry\Exceptions;

use RuntimeException;

class MissingConfigureException extends RuntimeException
{
    /**
     * {@inheritDoc}
     */
    public function __construct($serviceName)
    {
        parent::__construct("Service [$serviceName] not configured");
    }
}
