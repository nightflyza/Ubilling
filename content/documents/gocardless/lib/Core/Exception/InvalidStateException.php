<?php

namespace GoCardlessPro\Core\Exception;

class InvalidStateException extends ApiException
{
    public function isIdempotentCreationConflict()
    {
        return !is_null($this->getIdempotentCreationConflictError());
    }

    public function getConflictingResourceId()
    {
        $error = $this->getIdempotentCreationConflictError();

        if ($error) {
            return $error->links->conflicting_resource_id;
        }
    }

    private function getIdempotentCreationConflictError()
    {
        foreach ($this->getErrors() as $error) {
            if ($error->reason == 'idempotent_creation_conflict') {
                return $error;
            }
        }
    }
};
