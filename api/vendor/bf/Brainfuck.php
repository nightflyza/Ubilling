<?php

namespace dotzero;

/**
 * Class Brainfuck
 *
 * A PHP implementation of interpreter for Brainfuck.
 *
 * @package dotzero
 * @version 1.1
 * @author dotzero <mail@dotzero.ru>
 * @link http://www.dotzero.ru/
 * @link https://github.com/dotzero/brainfuck-php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Brainfuck {

    /**
     * @var null|string Source code
     */
    private $code = null;

    /**
     * @var int Source code pointer
     */
    private $code_pointer = 0;

    /**
     * @var array Data cells
     */
    private $cells = array();

    /**
     * @var int Data cell pointer
     */
    private $pointer = 0;

    /**
     * @var null|string User input
     */
    private $input = null;

    /**
     * @var int User input pointer
     */
    private $input_pointer = 0;

    /**
     * @var array Buffer
     */
    private $buffer = array();

    /**
     * @var string Output
     */
    private $output = '';

    /**
     * @var boolean Wrap over/underflows?
     */
    private $wrap = true;

    /**
     * Brainfuck constructor.
     *
     * @param string $code Source code
     * @param null|string $input User input
     */
    public function __construct($code = '', $input = null, $wrap = null) {
        $this->setCode($code);
        $this->setInput($input);
        $this->setWrap($wrap);
    }

    /**
     * Sets code to execute
     * 
     * @param string $code
     * 
     * @return void
     */
    public function setCode($code = '') {
        $this->code = $code;
        //clearing object instance data during new code setting
        $this->code_pointer=0;
        $this->cells=array();
        $this->output='';
    }

    /**
     * Sets users input
     * 
     * @param string $input
     * 
     * @return void
     */
    public function setInput($input = null) {
        $this->input = ($input) ? $input : null;
        //clearing input data
        $this->input_pointer=0;
    }

    /**
     * Executes PHP code returned from run method
     * 
     * @return void
     */
    public function execute() {
        if (!empty($this->code)) {
            $result = $this->run(true);
            if (!empty($result)) {
                eval($result);
            }
        }
    }

    /**
     * Sets instaince wrap property
     * 
     * @param bool $wrap
     * 
     * @return void
     */
    public function setWrap($wrap = null) {
        $this->wrap = (boolean) $wrap;
    }

    /**
     * Execute Brainfuck interpreter
     *
     * @param bool $return
     * @return string
     */
    public function run($return = false) {
        while ($this->code_pointer < strlen($this->code)) {
            $this->interpret($this->code[$this->code_pointer]);
            $this->code_pointer++;
        }

        if ($return) {
            return $this->output;
        } else {
            echo $this->output;
        }
    }

    /**
     * Command interpreter
     *
     * @param $command
     */
    private function interpret($command) {
        if (!isset($this->cells[$this->pointer])) {
            $this->cells[$this->pointer] = 0;
        }

        switch ($command) {
            case '>' :
                $this->pointer++;
                break;
            case '<' :
                $this->pointer--;
                break;
            case '+' :
                $this->cells[$this->pointer] ++;
                if ($this->wrap && $this->cells[$this->pointer] > 255) {
                    $this->cells[$this->pointer] = 0;
                }
                break;
            case '-' :
                $this->cells[$this->pointer] --;
                if ($this->wrap && $this->cells[$this->pointer] < 0) {
                    $this->cells[$this->pointer] = 255;
                }
                break;
            case '.' :
                $this->output .= chr($this->cells[$this->pointer]);
                break;
            case ',' :
                $this->cells[$this->pointer] = isset($this->input[$this->input_pointer]) ? ord($this->input[$this->input_pointer]) : 0;
                $this->input_pointer++;
                break;
            case '[' :
                if ($this->cells[$this->pointer] == 0) {
                    $delta = 1;
                    while ($delta AND $this->code_pointer++ < strlen($this->code)) {
                        switch ($this->code[$this->code_pointer]) {
                            case '[' :
                                $delta++;
                                break;
                            case ']' :
                                $delta--;
                                break;
                        }
                    }
                } else {
                    $this->buffer[] = $this->code_pointer;
                }
                break;
            case ']' :
                $this->code_pointer = array_pop($this->buffer) - 1;
        }
    }

}
