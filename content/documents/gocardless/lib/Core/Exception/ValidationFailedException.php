<?php

namespace GoCardlessPro\Core\Exception;

class ValidationFailedException extends ApiException
{
    protected function extractErrorMessage($error)
    {
        if (isset($error->field)) {
            return $error->field . ' ' . $error->message;
        } else {
            return $error->message;
        }
    }
};
