<?php

/**
 * Just dynamic content update abstraction layer
 */
class ZenFlow {

    /**
     * Container refresh timeout in ms.
     *
     * @var int
     */
    protected $timeout = 3000;

    /**
     * Contains current zen-flow ID string
     *
     * @var string
     */
    protected $flowId = '';

    /**
     * Content string to render in container area
     *
     * @var string
     */
    protected $content = '';

    /**
     * Debug flag
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Contains sound path to be played if content updates
     *
     * @var string
     */
    protected $soundOnChange = '';

    /**
     * Contains some predefined routes
     */
    const ROUTE_ZENFLOW = 'zenflow';

    /**
     * Creates new Zen-flow instance
     * 
     * @param string $flowId unique identifier of zen-flow instance
     * @param string $content some string or function which will be updated into container
     * @param int $timeout timeout in ms.
     */
    public function __construct($flowId, $content = '', $timeout = '') {
        $this->setFlowId($flowId); //set flow ID
        $this->setContent($content);
        if (!empty($timeout)) {
            $this->setTimeout($timeout);
        }
        $this->listener();
    }

    /**
     * Sets current instance flow ID
     * 
     * @param string $flowId
     * 
     * @return void
     */
    protected function setFlowId($flowId) {
        if (!empty($flowId)) {
            $this->flowId = 'zen' . $flowId;
        } else {
            throw new Exception('EX_EMPTY_FLOWID');
        }
    }

    /**
     * Sets instance refresh rate in ms.
     * 
     * @param int $timeout
     * 
     * @return void
     */
    protected function setTimeout($timeout) {
        $this->timeout = $timeout;
    }
    /**
     * Sets the debug state. Enables debug timestamp output on content update.
     *
     * @param bool $state The debug state to set. True to enable debugging, false to disable.
     * 
     * @return void
     */
    public function setDebug($state) {
        $this->debug = $state;
    }


    /**
     * Set sound file path to be played on content updates
     *
     * @param string $soundOnChange
     *
     * @return void
     */
    public function setSoundOnChange($soundOnChange) {
        $this->soundOnChange = $soundOnChange;
    }

    /**
     * Puts content data from constructor into protected property.
     * 
     * @param string $content
     * 
     * @return void
     */
    protected function setContent($content = '') {
        $this->content = $content;
    }

    /**
     * Renders initial zen-container with some prefilled content.
     * 
     * @return string
     */
    public function render() {
        $result = '';
        if (!empty($this->flowId)) {
            $container = 'zencontainer_' . $this->flowId;
            $debugArea = 'zendebug_' . $this->flowId;
            $epoch = 'zen' . $this->flowId . 'epoch';
            $epochUpd = 'zen' . $this->flowId . 'epochupd';
            $debugCode = '';
            $requestUrl = $_SERVER['REQUEST_URI'];
            $soundPlayback = '';

            if (!empty($requestUrl)) {
                $result .= wf_AjaxContainer($container, '', $this->content);
                if ($this->debug) {
                    $result .= wf_AjaxContainer($debugArea, '', '');
                    $debugCode .= '  
                    var debugTimestamp' . $this->flowId . ' = new Date();
                    var debutTimeLabel' . $this->flowId . ' = debugTimestamp' . $this->flowId . '.toLocaleTimeString();
                    $("#' . $debugArea . '").html("' . __('Changed') . ': "+debutTimeLabel' . $this->flowId . '+" ' . __('Iteration') . ': "+' . $epochUpd . '+" of "+' . $epoch . ');
                    ';
                }

                if ($this->soundOnChange) {
                    $soundPlayback = '
                    if (' . $epochUpd . '>0) {
                        var audio' . $this->flowId . ' = new Audio("' . $this->soundOnChange . '");
                        audio' . $this->flowId . '.play();
                    }
                    ';
                }

                $dataUrl = $requestUrl;
                if (!ubRouting::checkGet(self::ROUTE_ZENFLOW)) {
                    $dataUrl .= '&' . self::ROUTE_ZENFLOW . '=' . $this->flowId;
                }
                $result .= wf_tag('script');
                $result .= '$(document).ready(function() {
                        var ' . $epoch . ' = 0;
                        var ' . $epochUpd . ' = 0;
                        var prevData= "";
                        setInterval(function(){ 
                                ' . $epoch . '++;
                                $.get("' . $dataUrl . '", function(data) {
                                //update zen-container only if data is changed
                                if (prevData!=data) {
                                    $("#' . $container . '").html(data);
                                    prevData=data;
                                    ' . $soundPlayback . '
                                    ' . $epochUpd . '++;
                                }
                                ' . $debugCode . '
                        });
                    }, ' . $this->timeout . ');
                });
                ';

                $result .= wf_tag('script', true);
            }
        }
        return ($result);
    }

    /**
     * Listens for some flow callback, checks is this current instance flow and renders content.
     * 
     * @return bool
     */
    protected function listener() {
        $result = false;
        if (ubRouting::checkGet(self::ROUTE_ZENFLOW)) {
            $requestFlow = ubRouting::get(self::ROUTE_ZENFLOW);
            //its my flow?
            if ($requestFlow == $this->flowId) {
                print($this->content);
                die();
            }
        }
        /**
         * If you heard the loud big bang
         * You can see it with your eyes
         * The eternal night breaks when
         * The mushroom grows into the sky
         */
        return ($result);
    }
}
