<?php

/**
 * Fast and dirty wrapper for codemirror editor
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
     * Hint function for autocomplete (JS expression, e.g. CodeMirror.hint.anyword)
     *
     * @var string
     */
    protected $hintOptions = 'CodeMirror.hint.anyword';

    /**
     * When true, autocomplete (Ctrl-Space) and hintOptions are not applied.
     *
     * @var bool
     */
    protected $disableAutocomplete = false;

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
        $this->lineWrapping = (bool) $lineWrapping;
    }

    /**
     * Shows or hides line numbers in the gutter.
     *
     * @param bool $lineNumbers
     * @return void
     */
    public function setLineNumbers($lineNumbers) {
        $this->lineNumbers = (bool) $lineNumbers;
    }

    /**
     * Enables or disables highlighting of matching brackets.
     *
     * @param bool $matchBrackets
     * @return void
     */
    public function setMatchBrackets($matchBrackets) {
        $this->matchBrackets = (bool) $matchBrackets;
    }

    /**
     * Enables or disables auto-closing of brackets and quotes.
     *
     * @param bool $autoCloseBrackets
     * @return void
     */
    public function setAutoCloseBrackets($autoCloseBrackets) {
        $this->autoCloseBrackets = (bool) $autoCloseBrackets;
    }

    /**
     * Sets the hint function used for autocomplete 
     * 
     * Examples: CodeMirror.hint.anyword
     *           CodeMirror.hint.sql
     *           CodeMirror.hint.javascript
     *           CodeMirror.hint.css
     *           CodeMirror.hint.html
     *           CodeMirror.hint.php
     *
     * @param string $hintOptions
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
        $this->disableAutocomplete = (bool) $disableAutocomplete;
    }

    /**
     * Sets the editor headers.
     *
     * @return void
     */
    public function setHeaders() {
        $this->headers = '
        <link rel="stylesheet" href="modules/jsc/cmirr/codemirror.min.css">
        <link rel="stylesheet" href="modules/jsc/cmirr/theme/'.$this->theme.'.css">
        <link rel="stylesheet" href="modules/jsc/cmirr/show-hint.min.css">
        
        <script src="modules/jsc/cmirr/codemirror.min.js"></script>
        <script src="modules/jsc/cmirr/mode/clike/clike.js"></script>
        <script src="modules/jsc/cmirr/mode/htmlmixed/htmlmixed.js"></script>
        <script src="modules/jsc/cmirr/mode/javascript/javascript.js"></script>
        <script src="modules/jsc/cmirr/mode/css/css.js"></script>
        <script src="modules/jsc/cmirr/mode/php/php.js"></script>
        <script src="modules/jsc/cmirr/mode/sql/sql.js"></script>
        
        <script src="modules/jsc/cmirr/matchbrackets.min.js"></script>
        <script src="modules/jsc/cmirr/closebrackets.min.js"></script>
        <script src="modules/jsc/cmirr/show-hint.min.js"></script>
        <script src="modules/jsc/cmirr/anyword-hint.min.js"></script>
        <script src="modules/jsc/cmirr/javascript-hint.min.js"></script>
        <script src="modules/jsc/cmirr/sql-hint.js"></script>
        <script src="modules/jsc/cmirr/css-hint.js"></script>
        
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

        $options = array(
            'mode: "' . $this->mode . '"',
            'theme: "' . $this->theme . '"',
            'lineWrapping: ' . $lineWrapping,
            'lineNumbers: ' . $lineNumbers,
            'matchBrackets: ' . $matchBrackets,
            'autoCloseBrackets: ' . $autoCloseBrackets
        );
        if (!$this->disableAutocomplete) {
            $options[] = 'extraKeys: { "Ctrl-Space": "autocomplete" }';
            $options[] = 'hintOptions: { hint: ' . $this->hintOptions . ' }';
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
