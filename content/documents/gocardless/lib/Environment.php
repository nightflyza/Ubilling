<?php

namespace GoCardlessPro;

/**
 * Class containing constants to determine which server the API client should call.
 */
class Environment
{
    /**
     * For testing your integration
     */
    const SANDBOX = 'sandbox';

    /**
     * For live integrations, this will create real payments! $$$
     */
    const LIVE = 'live';
}
