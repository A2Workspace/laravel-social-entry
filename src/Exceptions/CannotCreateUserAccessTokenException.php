<?php

namespace A2Workspace\SocialEntry\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CannotCreateUserAccessTokenException extends HttpException
{
    /**
     * {@inheritDoc}
     */
    public function __construct(?string $message = null, int $statusCode = 500)
    {
        parent::__construct($statusCode, $message ?: 'Cannot create access token for user');
    }
}
