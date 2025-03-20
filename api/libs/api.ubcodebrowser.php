<?php


class UBCodeBrowser {
    /**
     * Code inspector instance
     *
     * @var object
     */
    protected $inspector = '';

    /**
     * Contains full code environment preloaded
     *
     * @var array
     */
    protected $fullCodeEnv = array();

    /**
     * Default functions source tree URL
     *
     * @var string
     */

    protected $sourceTree = 'https://github.com/nightflyza/Ubilling/blob/master/';

    /**
     * Messages helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    //some predefined stuff
    const URL_ME = '?module=codebrowser';
    const ROUTE_SCOPE = 'scope';
    const ROUTE_FUNC_NAME = 'funcname';
    const ROUTE_METHOD_NAME = 'methodname';
    const ROUTE_CLASS_NAME = 'classname';
    const SCOPE_FUNC_DESC = 'funcdesc';
    const SCOPE_CLASS_DESC = 'classdesc';
    const SCOPE_FUNC = 'funcs';
    const SCOPE_CLASSES = 'classes';
    const SCOPE_PHP_CLASSES = 'nativeclasses';
    const SCOPE_PHP_FUNC = 'nativefuncs';

    //preloading full codebase data
    public function __construct() {
        $this->initMessages();
        $this->inspector = new UBCodeInspector();
        $this->fullCodeEnv = $this->inspector->getCodeEnv();
    }

    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    public function renderControls() {
        $result = '';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_SCOPE . '=' . self::SCOPE_FUNC, __('Ubilling functions'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_SCOPE . '=' . self::SCOPE_CLASSES, __('Ubilling classes'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_SCOPE . '=' . self::SCOPE_PHP_FUNC, __('PHP functions'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_SCOPE . '=' . self::SCOPE_PHP_CLASSES, __('PHP classes'), false, 'ubButton');
        return ($result);
    }

    protected function getFuncString($funcName, $funcData) {
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
        return ($funcString);
    }

    public function renderFuncsList() {
        $result = '';
        $funcArr = array();

        foreach ($this->fullCodeEnv['functions'] as $funcName => $funcData) {
            $funcString = $this->getFuncString($funcName, $funcData);

            $funcDesc = zb_cutString($this->inspector->parseDocBlock($funcData['comment'], false), 80);
            $descUrl = self::URL_ME . '&' . self::ROUTE_SCOPE . '=' . self::SCOPE_FUNC_DESC . '&' . self::ROUTE_FUNC_NAME . '=' . $funcName;
            $descLink = wf_Link($descUrl, $funcDesc);
            $defUrl = $this->sourceTree . $funcData['file'] . '#L' . $funcData['line'];
            $defLink = wf_Link($defUrl, $funcData['file'] . ':' . $funcData['line'], false, '', 'target="_blank"');


            $funcArr[] = array(
                $funcString,
                $descLink,
                $defLink
            );
        }

        $columns = array('Function', 'Description', 'Definition');
        $opts = '"order": [[ 0, "asc" ]]';
        $result .= wf_JqDtEmbed($columns, $funcArr, true, 'Functions', 50, $opts);
        return ($result);
    }

    public function renderFuncDescription($funcName = '') {
        $result = '';

        if (!empty($funcName)) {
            if (isset($this->fullCodeEnv['functions'])) {
                if (isset($this->fullCodeEnv['functions'][$funcName])) {
                    $funcData = $this->fullCodeEnv['functions'][$funcName];

                    $funcString = $this->getFuncString($funcName, $funcData);
                    $funcDesc = $this->inspector->parseDocBlock($funcData['comment']);
                    $defUrl = $this->sourceTree . $funcData['file'] . '#L' . $funcData['line'];
                    $defLabel = __('View definition in') . ' ' . $funcData['file'] . ':' . $funcData['line'];
                    $defLink = wf_Link($defUrl, $defLabel, false, '', 'target="_blank"');

                    $result .= wf_tag('h2') . $funcString . wf_tag('h2', true);
                    $result .= wf_tag('pre') . ($funcDesc) . wf_tag('pre', true);
                    $result .= wf_delimiter();
                    $result .= $defLink;
                } else {
                    $result .= $this->messages->getStyledMessage(__('Function definition not found'), 'error');
                }
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Empty function name'), 'error');
        }
        $result .= wf_delimiter();
        $result .= wf_BackLink(self::URL_ME . '&' . self::ROUTE_SCOPE . '=' . self::SCOPE_FUNC);

        return ($result);
    }

    public function renderClassesList() {
        $result = '';
        $classArr = array();

        foreach ($this->fullCodeEnv['classes'] as $className => $classData) {
            //locally defined
            if (!empty($classData['file'])) {
                $classString = $className;
                $classDesc = zb_cutString($this->inspector->parseDocBlock($classData['comment'], false), 80);
                $descUrl = self::URL_ME . '&' . self::ROUTE_SCOPE . '=' . self::SCOPE_FUNC_DESC . '&' . self::ROUTE_FUNC_NAME . '=' . $className;
                $descLink = wf_Link($descUrl, $classDesc);
                $defUrl = $this->sourceTree . $classData['file'] . '#L' . $classData['line'];
                $defLink = wf_Link($defUrl, $classData['file'] . ':' . $classData['line'], false, '', 'target="_blank"');

                $classArr[] = array(
                    $classString,
                    '',
                    $classDesc,
                    $defLink
                );
                if (!empty($classData['methods'])) {
                    foreach ($classData['methods'] as $methodName => $methodData) {
                        $methodString = $this->getFuncString($methodName, $methodData);
                        $methodDesc = zb_cutString($this->inspector->parseDocBlock($methodData['comment'], false), 80);
                        $descUrl = self::URL_ME . '&' . self::ROUTE_SCOPE . '=' . self::SCOPE_CLASS_DESC . '&' . self::ROUTE_CLASS_NAME . '=' . $className . '&' . self::ROUTE_METHOD_NAME . '=' . $methodName;
                        $descLink = wf_Link($descUrl, $methodDesc);
                        $defUrl = $this->sourceTree . $classData['file'] . '#L' . $methodData['line'];
                        $defLink = wf_Link($defUrl, $classData['file'] . ':' . $methodData['line'], false, '', 'target="_blank"');

                        $classArr[] = array(
                            $className,
                            $methodString,
                            $descLink,
                            $defLink
                        );
                    }
                }
            }
        }

        $columns = array('Class', 'Method name', 'Description', 'Definition');
        $opts = '"order": [[ 0, "asc" ]]';
        $result .= wf_JqDtEmbed($columns, $classArr, true, 'Classess and methods', 50, $opts);
        return ($result);
    }
}
