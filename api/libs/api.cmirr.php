<?php

/**
 * Wrapper for CodeMirror editor
 */
class CMIRR {
    /**
     * Contains unique editor ID
     *
     * @var string
     */
    protected $editorId = '';

    /**
     * Indicates whether the headers have been rendered.
     * 
     * @var bool 
     */
    protected $headersRendered = false;

    /**
     * The default mode of editor
     * 
     * @var string 
     */
    protected $mode = 'text/x-php';

    /**
     * CodeMirror theme name
     *
     * @var string
     */
    protected $theme = 'dracula';

    /**
     * Enable line wrapping
     *
     * @var bool
     */
    protected $lineWrapping = true;

    /**
     * Show line numbers
     *
     * @var bool
     */
    protected $lineNumbers = true;

    /**
     * Highlight matching brackets
     *
     * @var bool
     */
    protected $matchBrackets = true;

    /**
     * Auto-close brackets
     *
     * @var bool
     */
    protected $autoCloseBrackets = true;

    /**
     * Highlight the line where the cursor is.
     *
     * @var bool
     */
    protected $styleActiveLine = true;

    /**
     * Hint function for autocomplete (short name, e.g. anyword, sql, javascript, css).
     *
     * @var string
     */
    protected $hintOptions = 'anyword';

    /**
     * When true, autocomplete (Ctrl-Space) and hintOptions are not applied.
     *
     * @var bool
     */
    protected $disableAutocomplete = false;

    /**
     * Enable F11 fullscreen and Esc to exit.
     *
     * @var bool
     */
    protected $enableFullscreen = true;

    /**
     * Contains the necessary stylesheets and scripts for CodeMirror integration.
     *
     * @var string
     */
    protected $headers = '';

    /**
     * Contains code mirror init JS script with some unique ID
     *
     * @var string
     */
    protected $script = '';
    /**
     * Contains editor area custom styling
     *
     * @var string
     */
    protected $style = '';


    /**
     * Path to CodeMirror library
     *
     * @var string
     */
    protected $cmLibPath='modules/jsc/cmirr/';


    public function __construct() {
        $this->setStyle();
    }

    /**
     * Sets CodeMirror theme (e.g. dracula, default).
     *
     * @param string $theme
     * @return void
     */
    public function setTheme($theme) {
        $this->theme = $theme;
    }

    /**
     * Enables or disables line wrapping in the editor.
     *
     * @param bool $lineWrapping
     * @return void
     */
    public function setLineWrapping($lineWrapping) {
        $this->lineWrapping = $lineWrapping;
    }

    /**
     * Shows or hides line numbers in the gutter.
     *
     * @param bool $lineNumbers
     * @return void
     */
    public function setLineNumbers($lineNumbers) {
        $this->lineNumbers = $lineNumbers;
    }

    /**
     * Enables or disables highlighting of matching brackets.
     *
     * @param bool $matchBrackets
     * @return void
     */
    public function setMatchBrackets($matchBrackets) {
        $this->matchBrackets = $matchBrackets;
    }

    /**
     * Enables or disables auto-closing of brackets and quotes.
     *
     * @param bool $autoCloseBrackets
     * @return void
     */
    public function setAutoCloseBrackets($autoCloseBrackets) {
        $this->autoCloseBrackets = $autoCloseBrackets;
    }

    /**
     * Enables or disables highlighting of the current line.
     *
     * @param bool $styleActiveLine
     * @return void
     */
    public function setStyleActiveLine($styleActiveLine) {
        $this->styleActiveLine = $styleActiveLine;
    }

    /**
     * Sets the hint function used for autocomplete by short name.
     * Available (loaded): anyword, javascript, sql, css.
     *
     * @param string $hintOptions Short name, e.g. anyword, sql, javascript, css
     * @return void
     */
    public function setHintOptions($hintOptions) {
        $this->hintOptions = $hintOptions;
    }

    /**
     * Disables or enables autocomplete (Ctrl-Space and hint dropdown).
     *
     * @param bool $disableAutocomplete
     * @return void
     */
    public function setDisableAutocomplete($disableAutocomplete) {
        $this->disableAutocomplete = $disableAutocomplete;
    }

    /**
     * Enables or disables fullscreen (F11 / Esc).
     *
     * @param bool $enableFullscreen
     * @return void
     */
    public function setEnableFullscreen($enableFullscreen) {
        $this->enableFullscreen = $enableFullscreen;
    }

    /**
     * Sets the editor headers.
     *
     * @return void
     */
    public function setHeaders() {
        $this->headers = '
        <link rel="stylesheet" href="'.$this->cmLibPath.'lib/codemirror.css">
        <link rel="stylesheet" href="'.$this->cmLibPath.'theme/'.$this->theme.'.css">
        <link rel="stylesheet" href="'.$this->cmLibPath.'addon/hint/show-hint.css">
        <link rel="stylesheet" href="'.$this->cmLibPath.'addon/display/fullscreen.css">
        
        <script src="'.$this->cmLibPath.'lib/codemirror.js"></script>
        <script src="'.$this->cmLibPath.'mode/clike/clike.js"></script>
        <script src="'.$this->cmLibPath.'mode/htmlembedded/htmlembedded.js"></script>
        <script src="'.$this->cmLibPath.'mode/htmlmixed/htmlmixed.js"></script>
        <script src="'.$this->cmLibPath.'mode/javascript/javascript.js"></script>
        <script src="'.$this->cmLibPath.'mode/css/css.js"></script>
        <script src="'.$this->cmLibPath.'mode/php/php.js"></script>
        <script src="'.$this->cmLibPath.'mode/sql/sql.js"></script>
        <script src="'.$this->cmLibPath.'mode/shell/shell.js"></script>
        
        <script src="'.$this->cmLibPath.'addon/edit/matchbrackets.js"></script>
        <script src="'.$this->cmLibPath.'addon/edit/closebrackets.js"></script>
        <script src="'.$this->cmLibPath.'addon/selection/active-line.js"></script>
        <script src="'.$this->cmLibPath.'addon/display/fullscreen.js"></script>
        <script src="'.$this->cmLibPath.'addon/hint/show-hint.js"></script>
        <script src="'.$this->cmLibPath.'addon/hint/anyword-hint.js"></script>
        <script src="'.$this->cmLibPath.'addon/hint/javascript-hint.js"></script>
        <script src="'.$this->cmLibPath.'addon/hint/sql-hint.js"></script>
        <script src="'.$this->cmLibPath.'addon/hint/css-hint.js"></script>
        
        ';
    }

    /**
     * Sets the editor language mode (syntax highlighting and parsing).
     * Supported values 
     * - text/x-php 
     * - text/javascript 
     * - text/css
     * - text/html, htmlmixed 
     * - text/x-sql
     *
     * @param string $mode
     * @return void
     */
    public function setMode($mode) {
        $this->mode = $mode;
    }

    /**
     * Sets the editor ID.
     *
     * @return void
     */
    protected function setEditorId() {
        $this->editorId = wf_InputId();
    }


    /**
     * This method is responsible for configuring the JS script that will be used to init editor
     *
     * @return void
     */
    protected function setScript() {
        $lineWrapping = ($this->lineWrapping) ? 'true' : 'false';
        $lineNumbers = ($this->lineNumbers) ? 'true' : 'false';
        $matchBrackets = ($this->matchBrackets) ? 'true' : 'false';
        $autoCloseBrackets = ($this->autoCloseBrackets) ? 'true' : 'false';
        $styleActiveLine = ($this->styleActiveLine) ? 'true' : 'false';

        $options = array(
            'mode: "' . $this->mode . '"',
            'theme: "' . $this->theme . '"',
            'lineWrapping: ' . $lineWrapping,
            'lineNumbers: ' . $lineNumbers,
            'matchBrackets: ' . $matchBrackets,
            'autoCloseBrackets: ' . $autoCloseBrackets,
            'styleActiveLine: ' . $styleActiveLine
        );
        $extraKeysParts = array();
        if ($this->enableFullscreen) {
            $extraKeysParts[] = '"F11": function(cm) { cm.setOption("fullScreen", !cm.getOption("fullScreen")); }';
            $extraKeysParts[] = '"Esc": function(cm) { if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false); }';
        }
        if (!$this->disableAutocomplete) {
            $hintRef = (strpos($this->hintOptions, 'CodeMirror.hint.') === 0)
                ? $this->hintOptions
                : 'CodeMirror.hint.' . $this->hintOptions;
            $extraKeysParts[] = '"Ctrl-Space": "autocomplete"';
            $options[] = 'hintOptions: { hint: ' . $hintRef . ' }';
        }
        if (!empty($extraKeysParts)) {
            $options[] = 'extraKeys: { ' . implode(', ', $extraKeysParts) . ' }';
        }
        $optionsStr = implode(', ', $options);

        $this->script = wf_tag('script', false);
        $this->script .= '
            var editor' . $this->editorId . ' = CodeMirror.fromTextArea(document.getElementById("codeEditor' . $this->editorId . '"), {
                ' . $optionsStr . '
            });
        ';
        $this->script .= wf_tag('script', true);
    }

    /**
     * Sets or overrides some custom editor styling
     * 
     * @param string $customStyle
     *
     * @return void
     */
    public function setStyle($customStyle = '') {
        $this->style = wf_tag('style', false);
        if (!empty($customStyle)) {
            $this->style .= $customStyle;
        } else {
        $this->style .= '
            #editor-container {
                width: 100% !important;
                height: 65vh;
                border: 1px solid #ccc;
            }

            .CodeMirror {
                height: 100%;
                width: 100%;
                font-size: 16px;
            }
        ';
        }
        $this->style .= wf_tag('style', true);
    }

    /**
     * Returns text editing area with initialized code mirror editor
     *
     * @param string $name
     * @param string $contentPreset
     * @param int $cols
     * @param int $rows
     * 
     * @return string
     */
    public function getEditorArea($name, $contentPreset = '', $cols = 145, $rows = 30) {
        //setting new editor properties
        $this->setEditorId();
        $this->setHeaders();
        $this->setScript();

        //rendering result
        $result = '';
        if (!$this->headersRendered) {
            $result .= $this->headers;
            $this->headersRendered = true;
        }
        $result .= $this->style;

        $result .= wf_tag('div', false, '', 'id="editor-container"');
        $options='id="codeEditor' . $this->editorId . '" name="' . $name . '" cols="'.$cols.'" rows="'.$rows.'" spellcheck="false"';
        $result .= wf_tag('textarea', false, '', $options);
        $result .= $contentPreset;
        $result .= wf_tag('textarea', true);

        $result .= $this->script;
        $result .= wf_tag('div', true);
        return ($result);
    }
}
