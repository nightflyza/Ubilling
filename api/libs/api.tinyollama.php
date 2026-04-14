<?php

/**
 * Minimalist ollama REST API implementation
 * https://github.com/ollama/ollama/blob/main/docs/api.md
 */
class TinyOllama {
    /**
     * HTTP API abstraction layer
     *
     * @var object
     */
    protected $api = '';
    /**
     * Remote API interraction protocol
     *
     * @var string
     */
    protected $proto = 'http';
    /**
     * Remote API interaction host
     *
     * @var string
     */
    protected $host = 'localhost';
    /**
     * Remote API interaction port
     *
     * @var string
     */
    protected $port = 11434;
    /**
     * Response live-streaming flag
     *
     * @var bool
     */
    protected $streamingFlag = false;
    /**
     * Request model name
     *
     * @var string
     */
    protected $model = '';
    /**
     * Base URL which will be used as endpoint prefix
     *
     * @var string
     */
    protected $baseUrl = '';
    /**
     * Request temperature
     *
     * @var bool|int
     */
    protected $temperature = false;


    /**
     * Creates new TinyOllama instance
     *
     * @param string $host
     * @param int $port
     * @param string $model
     */
    public function __construct($host = 'localhost', $port = 11434, $model = '') {
        $this->setHost($host);
        $this->setPort($port);
        if (!empty($model)) {
            $this->setModel($model);
        }
        $this->initApi();
        $this->setBaseUrl();
    }

    /**
     * Initializes the API by creating a new instance of the OmaeUrl class
     * 
     * @return void
     */
    protected function initApi() {
        $this->api = new OmaeUrl();
    }

    /**
     * Set the value of host
     * 
     * @param string $host
     *
     * @return void
     */
    public function setHost($host) {
        $this->host = $host;
    }

    /**
     * Set the value of port
     * 
     * @param int $port
     *
     * @return void
     */
    public function setPort($port) {
        $this->port = $port;
    }

    /**
     * Set the value of model name for instance
     * 
     * @param string $model
     *
     * @return void
     */
    public function setModel($model) {
        $this->model = $model;
    }

    /**
     * Set the value for baseUrl
     *
     * @return void
     */
    public function setBaseUrl() {
        $baseUrl = $this->proto . '://' . $this->host . ':' . $this->port . '/api/';
        $this->baseUrl = $baseUrl;
    }

    /**
     * Set the value of the instance generation temperature
     * 
     * @param float $temperature
     *
     * @return void
     */
    public function setTemperature($temperature) {
        $this->temperature = $temperature;
    }

    /**
     * Returns list of running models
     *
     * @return array
     */
    public function ps() {
        $result = array();
        $endPoint = 'ps';
        $rawReply = $this->api->response($this->baseUrl . $endPoint);
        if ($rawReply) {
            $result = json_decode($rawReply, true);
        }
        return ($result);
    }

    /**
     * Returns list of available local models
     *
     * @return array
     */
    public function listmodels() {
        $result = array();
        $endPoint = 'tags';
        $rawReply = $this->api->response($this->baseUrl . $endPoint);
        if ($rawReply) {
            $result = json_decode($rawReply, true);
        }
        return ($result);
    }

    /**
     * Generates a response based on the given prompt.
     *
     * @param string $prompt The prompt for generating the response.
     * @param array $context the context parameter returned from a previous request, 
     *                       this can be used to keep a short conversational memory
     * 
     * @return array|string The generated response. Contains [response] data key
     * @throws Exception If the model or prompt is empty.
     */
    public function generate($prompt, $context = array()) {
        $result = array();
        $request = array();
        $endPoint = 'generate';
        if (!empty($this->model)) {
            $request['model'] = $this->model;
            if (!empty($prompt)) {
                $request['prompt'] = $prompt;
                $request['stream'] = $this->streamingFlag;
                if ($this->temperature !== false) {
                    $request['temperature'] = $this->temperature;
                }
                if (!empty($context)) {
                    $request['context'] = $context;
                }

                $requestBody = json_encode($request);
                $this->api->dataPostRaw($requestBody);
                $rawReply = $this->api->response($this->baseUrl . $endPoint);
                if ($rawReply) {
                    if ($this->streamingFlag) {
                        $result = $rawReply;
                    } else {
                        $result = json_decode($rawReply, true);
                    }
                }
            } else {
                throw new Exception('EX_EMPTY_POMPT');
            }
        } else {
            throw new Exception('EX_EMPTY_MODEL');
        }
        return ($result);
    }
    /**
     * Sends a chat message to the API.
     *
     * @param string $message The message content.
     * @param string $role The role of the message sender. Default is 'user'. Possible: system, user, assistant, or tool
     * @param array $allMessages An array of all previous messages. Default is an empty array.
     * @return array The API response. Contains [message]=>[role]+[content] data keys
     * 
     * @throws Exception If the model is empty or if the message is empty.
     */
    public function chat($message, $role = 'user', $allMessages = array()) {
        $result = array();
        $request = array();
        $messages = $allMessages;
        $endPoint = 'chat';
        if (!empty($this->model)) {
            $request['model'] = $this->model;
            if (!empty($message)) {
                $messages[] = array(
                    'role' => $role,
                    'content' => $message
                );
                $request['messages'] = $messages;
                $request['stream'] = $this->streamingFlag;

                $requestBody = json_encode($request);
                $this->api->dataPostRaw($requestBody);
                $rawReply = $this->api->response($this->baseUrl . $endPoint);
                if ($rawReply) {
                    if ($this->streamingFlag) {
                        $result = $rawReply;
                    } else {
                        $result = json_decode($rawReply, true);
                    }
                }
            } else {
                throw new Exception('EX_EMPTY_MESSAGE');
            }
        } else {
            throw new Exception('EX_EMPTY_MODEL');
        }
        return ($result);
    }

    /**
     * Sends a chat message with optional images to the API.
     *
     * Images should be passed either as local file paths or as base64-encoded strings.
     *
     * @param string $message The message content.
     * @param array $images Array of local file paths or base64 image strings.
     * @param string $role The role of the message sender. Default is 'user'.
     * @param array $allMessages An array of all previous messages. Default is an empty array.
     *
     * @return array|string The API response.
     *
     * @throws Exception If model is empty or both message and images are empty.
     */
    public function chatWithImages($message = '', $images = array(), $role = 'user', $allMessages = array()) {
        $result = array();
        $request = array();
        $messages = $allMessages;
        $endPoint = 'chat';
        $preparedImages = $this->prepareChatImages($images);
        if (!empty($this->model)) {
            $request['model'] = $this->model;
            if (!empty($message) or !empty($preparedImages)) {
                $currentMessage = array(
                    'role' => $role,
                    'content' => $message
                );
                if (!empty($preparedImages)) {
                    $currentMessage['images'] = $preparedImages;
                }
                $messages[] = $currentMessage;
                $request['messages'] = $messages;
                $request['stream'] = $this->streamingFlag;

                $requestBody = json_encode($request);
                $this->api->dataPostRaw($requestBody);
                $rawReply = $this->api->response($this->baseUrl . $endPoint);
                if ($rawReply) {
                    if ($this->streamingFlag) {
                        $result = $rawReply;
                    } else {
                        $result = json_decode($rawReply, true);
                    }
                }
            } else {
                throw new Exception('EX_EMPTY_MESSAGE');
            }
        } else {
            throw new Exception('EX_EMPTY_MODEL');
        }
        return ($result);
    }

    /**
     * Generates image using selected model via /api/generate endpoint.
     *
     * @param string $prompt Text prompt for image generation.
     * @param int $width Optional image width in pixels.
     * @param int $height Optional image height in pixels.
     * @param array $options Optional additional request params.
     *
     * @return array|string API response.
     *
     * @throws Exception If model or prompt is empty.
     */
    public function generateImage($prompt, $width = 0, $height = 0, $options = array()) {
        $result = array();
        $request = array();
        $endPoint = 'generate';
        if (!empty($this->model)) {
            $request['model'] = $this->model;
            if (!empty($prompt)) {
                $request['prompt'] = $prompt;
                $request['stream'] = false;

                $width = preg_replace("#[^0-9]#Uis", '', $width);
                $height = preg_replace("#[^0-9]#Uis", '', $height);
                if (!empty($width)) {
                    $request['width'] = (int) $width;
                }
                if (!empty($height)) {
                    $request['height'] = (int) $height;
                }

                if (!empty($options)) {
                    foreach ($options as $optionKey => $optionValue) {
                        $request[$optionKey] = $optionValue;
                    }
                }

                $requestBody = json_encode($request);
                $this->api->dataPostRaw($requestBody);
                $rawReply = $this->api->response($this->baseUrl . $endPoint);
                if ($rawReply) {
                    if (!empty($request['stream'])) {
                        $result = $rawReply;
                    } else {
                        $result = json_decode($rawReply, true);
                    }
                }
            } else {
                throw new Exception('EX_EMPTY_POMPT');
            }
        } else {
            throw new Exception('EX_EMPTY_MODEL');
        }
        return ($result);
    }

    /**
     * Prepares an array of images for chat request payload.
     *
     * @param array|string $images
     *
     * @return array
     */
    protected function prepareChatImages($images) {
        $result = array();
        if (!is_array($images)) {
            $images = array($images);
        }

        if (!empty($images)) {
            foreach ($images as $io => $rawImage) {
                $preparedImage = $this->prepareSingleImage($rawImage);
                if (!empty($preparedImage)) {
                    $result[] = $preparedImage;
                }
            }
        }
        return ($result);
    }

    /**
     * Converts single image source into base64 payload item.
     *
     * @param string $imageData
     *
     * @return string
     */
    protected function prepareSingleImage($imageData) {
        $result = '';
        if (is_string($imageData)) {
            $imageData = trim($imageData);
            if (!empty($imageData)) {
                if (is_file($imageData) and is_readable($imageData)) {
                    $rawImage = file_get_contents($imageData);
                    if ($rawImage !== false) {
                        $result = base64_encode($rawImage);
                    }
                } else {
                    if (strpos($imageData, 'base64,') !== false) {
                        $parts = explode('base64,', $imageData, 2);
                        if (isset($parts[1])) {
                            $imageData = $parts[1];
                        }
                    }
                    $imageData = str_replace(array("\r", "\n", ' '), '', $imageData);
                    $decodedData = base64_decode($imageData, true);
                    if ($decodedData !== false and !empty($decodedData)) {
                        $result = $imageData;
                    }
                }
            }
        }
        return ($result);
    }
}
