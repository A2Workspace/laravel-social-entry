<?php

namespace A2Workspace\SocialEntry\Exceptions;

use LogicException;
use Illuminate\Database\Eloquent\Model;

class InvalidUserModelException extends LogicException
{
    /**
     * {@inheritDoc}
     */
    public function __construct($message = null)
    {
        parent::__construct(
            $message ?: sprintf('The user model must instance of %s.', Model::class),
        );
    }
}
