<?php

namespace A2Workspace\SocialEntry\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidAccessTokenException extends HttpException
{
    /**
     * {@inheritDoc}
     */
    public function __construct(?string $message = null, int $statusCode = 400)
    {
        parent::__construct($statusCode, $message ?: 'Invalid access token');
    }
}
