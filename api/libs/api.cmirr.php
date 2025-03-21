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
     * Contains the necessary stylesheets and scripts for CodeMirror integration.
     * 
     * @var string
     */
    protected $headers = '
        <link rel="stylesheet" href="modules/jsc/cmirr/codemirror.min.css">
        <link rel="stylesheet" href="modules/jsc/cmirr/dracula.min.css">
        <link rel="stylesheet" href="modules/jsc/cmirr/show-hint.min.css">
        
        <script src="modules/jsc/cmirr/codemirror.min.js"></script>
        <script src="modules/jsc/cmirr/clike.min.js"></script>
        <script src="modules/jsc/cmirr/htmlmixed.min.js"></script>
        <script src="modules/jsc/cmirr/javascript.min.js"></script>
        <script src="modules/jsc/cmirr/css.min.js"></script>
        <script src="modules/jsc/cmirr/php.min.js"></script>
        
        <script src="modules/jsc/cmirr/matchbrackets.min.js"></script>
        <script src="modules/jsc/cmirr/closebrackets.min.js"></script>
        <script src="modules/jsc/cmirr/show-hint.min.js"></script>
        <script src="modules/jsc/cmirr/anyword-hint.min.js"></script>
        <script src="modules/jsc/cmirr/javascript-hint.min.js"></script>
    ';

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
        $this->script = wf_tag('script', false);
        $this->script .= '
            var editor' . $this->editorId . ' = CodeMirror.fromTextArea(document.getElementById("codeEditor' . $this->editorId . '"), {
                mode: "' . $this->mode . '",
                theme: "dracula",
                lineWrapping: true,
                lineNumbers: true,
                matchBrackets: true,
                autoCloseBrackets: true,
                extraKeys: { "Ctrl-Space": "autocomplete" },
                hintOptions: { hint: CodeMirror.hint.anyword }
            });
        ';
        $this->script .= wf_tag('script', true);
    }

    /**
     * Sets some custom editor styling
     *
     * @return void
     */
    protected function setStyle() {
        $this->style = wf_tag('style', false);
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
        $this->style .= wf_tag('style', true);
    }

    /**
     * Returns text editing area with initialized code mirror editor
     *
     * @param string $name
     * @param string $contentPreset
     * 
     * @return string
     */
    public function getEditorArea($name, $contentPreset = '') {
        //setting new editor properties
        $this->setEditorId();
        $this->setScript();

        //rendering result
        $result = '';
        if (!$this->headersRendered) {
            $result .= $this->headers;
            $this->headersRendered = true;
        }
        $result .= $this->style;

        $result .= wf_tag('div', false, '', 'id="editor-container"');
        $result .= wf_tag('textarea', false, '', 'id="codeEditor' . $this->editorId . '" name="' . $name . '" cols="145" rows="30" spellcheck="false"');
        $result .= $contentPreset;
        $result .= wf_tag('textarea', true);

        $result .= $this->script;
        $result .= wf_tag('div', true);
        return ($result);
    }
}
