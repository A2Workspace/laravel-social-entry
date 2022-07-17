<?php

namespace A2Workspace\SocialEntry\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidStateException extends HttpException
{
    /**
     * {@inheritDoc}
     */
    public function __construct(?string $message = null, int $statusCode = 419)
    {
        parent::__construct($statusCode, $message ?: 'Invalid state');
    }
}
