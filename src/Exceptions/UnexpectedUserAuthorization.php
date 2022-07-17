<?php

namespace A2Workspace\SocialEntry\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UnexpectedUserAuthorization extends HttpException
{
    /**
     * {@inheritDoc}
     */
    public function __construct(?string $message = null, int $statusCode = 403)
    {
        parent::__construct($statusCode, $message ?: 'Unexpected user authorization');
    }
}
