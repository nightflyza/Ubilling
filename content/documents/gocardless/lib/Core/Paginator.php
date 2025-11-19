<?php

namespace GoCardlessPro\Core;

/**
 * Class allowing for pagination of API resources.
 * @implements \Iterator
 */
class Paginator implements \Iterator
{
    /**
    * Default max records to retrieve per page
    */
    const HARD_RECORD_LIMIT = 500;

    /**
    * @var \GoCardlessPro\Services\BaseService The resource service to fetch records with
    */
    private $service;

    /**
    * @var array Request options
    */
    private $options;

    /**
    * @var int Keep track of current index
    */
    private $current_position;

    /**
    * @var array Keep track of the index of the first record on the current page. Allows for relative indexing into the page.
    */
    private $current_page_position;

    /**
     * Creates the paginator
     * @param \GoCardlessPro\Services\BaseService $service Resource service used to fetch records
     * @param array                               $options Request params to send with each request
     */
    public function __construct($service, $options = array())
    {
        $this->service = $service;
        $this->options = $options;

        if(isset($options['params']) && isset($options['params']['limit'])) {
            $this->options['params']['limit'] = min($options['params']['limit'], self::HARD_RECORD_LIMIT);
        } else {
            $this->options['params']['limit'] = self::HARD_RECORD_LIMIT;
        }
    }

    /**
     * Rewind to the first page for foreach iterators
     */
    public function rewind()
    {
        $this->current_position = 0;
        $this->current_page_position = 0;
        $this->current_response = $this->initial_response();
    }

    /**
     * Get the current element for foreach iterators
     *
     * @return \GoCardlessPro\Resources\BaseResource
     */
    public function current()
    {
        return $this->current_records()[$this->key()];
    }

    /**
     * Gets the current iteration key
     *
     * @return int
     */
    public function key()
    {
        return $this->current_position - $this->current_page_position;
    }

    /**
     * Increments the current index of the iterator and fetches the next
     * page if required
     */
    public function next()
    {
        ++$this->current_position;

        if(!$this->valid()) {
            $this->current_response = $this->next_response();
            $this->current_page_position = $this->current_position;
        }
    }

    /**
     * Returns whether the current position is valid
     *
     * @return bool
     */
    public function valid()
    {
        return !is_null($this->current_response) &&
            array_key_exists($this->key(), $this->current_records());
    }

    /**
     * Fetch the first page of results
     *
     * @return ListResponse
     */
    private function initial_response()
    {
        $options = $this->options;
        $options['params']['after'] = null;
        return $this->service->list($options);
    }

    /**
     * Fetches the next page of results (based on the current page)
     *
     * @return ListResponse
     */
    private function next_response()
    {
        $options = $this->options;
        $options['params']['after'] = $this->current_response->after;
        if (empty($options['params']['after'])) {
            return null;
        }

        return $this->service->list($options);
    }

    /**
     * Returns the current response array
     *
     * @return \GoCardlessPro\Resources\BaseResource[]
     */
    private function current_records()
    {
        return $this->current_response->records;
    }

}
