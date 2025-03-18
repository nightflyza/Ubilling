<?php


class UBCodeBrowser {
    /**
     * Code inspector instance
     *
     * @var object
     */
    protected $inspector = '';
    protected $fullCodeEnv = array();
    protected $sourceTree = 'https://github.com/nightflyza/Ubilling/blob/master/';
    public function __construct() {
        $this->inspector = new UBCodeInspector();
        $this->fullCodeEnv = $this->inspector->getCodeEnv();
    }

    public function renderFuncsList() {
        $result = '';
        $funcArr = array();

        foreach ($this->fullCodeEnv['functions'] as $funcName => $funcData) {
            $funcString = $funcName;
            if (!empty($funcData['params'])) {
                $funcParams = array();
                foreach ($funcData['params'] as $eachParam => $paramOptional) {

                    $paramName = '$' . $eachParam;
                    if ($paramOptional) {
                        $paramName = '[' . $paramName . ']';
                    }

                    $funcParams[] = $paramName;
                }
                $funcString .= '(';
                $funcString .= implode(', ', $funcParams);
                $funcString .= ')';
            } else {
                $funcString .= '()';
            }

            $funcDesc = zb_cutString($this->inspector->parseDocBlock($funcData['comment'], false),80);
            $defUrl = $this->sourceTree . $funcData['file'] . '#L' . $funcData['line'];
            $defLink = wf_Link($defUrl, $funcData['file'] . ':' . $funcData['line']);
            $funcArr[] = array(
                $funcString,
                $funcDesc,
                $defLink
            );
        }

        $columns = array('Function', 'Description', 'Definition');
        $opts = '';
        $result .= wf_JqDtEmbed($columns, $funcArr, false, 'Functions', 100, $opts);
        return ($result);
    }
}
