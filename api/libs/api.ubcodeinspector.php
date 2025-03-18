<?php

/**
 * UBCodeInspector is responsible for inspecting and analyzing code base.
 */
class UBCodeInspector {
    protected $libsPath = 'api/libs/';
    protected $result = array();

    public function __construct() {
        $this->loadLibraries();
        $this->processClasses();
        $this->processFunctions();
    }


    protected function loadLibraries() {
        $allLibs = rcms_scandir($this->libsPath, '*.php');
        $loadedRaw = get_included_files();
        $loadedLibs = array();

        if (!empty($loadedRaw)) {
            foreach ($loadedRaw as $each) {
                $fileName = basename($each);
                $loadedLibs[$fileName] = $each;
            }
        }

        if (!empty($allLibs)) {
            foreach ($allLibs as $eachLib) {
                if (!isset($loadedLibs[$eachLib]) and !ispos($eachLib, 'maps') and !ispos($eachLib, 'oll')) {
                    require_once($this->libsPath . $eachLib);
                }
            }
        }
    }

    protected function processClasses() {
        $allClasses = get_declared_classes();
        if (!empty($allClasses)) {
            foreach ($allClasses as $eachClass) {
                $classRef = new ReflectionClass($eachClass);
                $classLibName = $classRef->getFileName();
                $defineStartLine = $classRef->getStartLine();
                if (!empty($classLibName)) {
                    $classLibName =  $this->cleanFilePath($classLibName);
                }
                $classMethods = get_class_methods($eachClass);
                $methodParams = array();
                if (!empty($classMethods)) {
                    foreach ($classMethods as $eachMethod) {
                        $methodRef = new ReflectionMethod($eachClass, $eachMethod);
                        $params = $methodRef->getParameters();
                        $methodDefineLine = $methodRef->getStartLine();
                        $methodComment = $methodRef->getDocComment();
                        if (!empty($params)) {
                            foreach ($params as $eachParam) {
                                $paramName = $eachParam->getName();
                                $paramOptional = $eachParam->isOptional();
                                $methodParams[$eachMethod]['params'][$paramName] = $paramOptional;
                            }
                            $methodParams[$eachMethod]['line'] = $methodDefineLine;
                            $methodParams[$eachMethod]['comment'] = $methodComment;
                        }
                    }
                }

                $classComment = $classRef->getDocComment();
                $this->result['classes'][$eachClass]['comment'] = $classComment;
                $this->result['classes'][$eachClass]['file'] = $classLibName;
                $this->result['classes'][$eachClass]['line'] = $defineStartLine;
                $this->result['classes'][$eachClass]['methods'] = $methodParams;
            }
        }
    }

    protected function processFunctions() {
        $allFunctions = get_defined_functions();
        if (!empty($allFunctions)) {
            $funcParams = array();
            $allDefinedFuncs = $allFunctions['user'];

            if (!empty($allDefinedFuncs)) {
                foreach ($allDefinedFuncs as $eachFuncName) {
                    $ref = new ReflectionFunction($eachFuncName);
                    $originalFuncName = $ref->getName();
                    $params = $ref->getParameters();
                    $fileName = $ref->getFileName();
                    $defineStartLine = $ref->getStartLine();
                    $funcComment = $ref->getDocComment();
                    if (!empty($fileName)) {
                        $fileName =   $this->cleanFilePath($fileName);
                    }
                    if (!empty($params)) {
                        foreach ($params as $eachParam) {
                            $paramName = $eachParam->getName();
                            $paramOptional = $eachParam->isOptional();
                            $funcParams[$originalFuncName]['params'][$paramName] = $paramOptional;
                        }
                    }

                    $funcParams[$originalFuncName]['comment'] = $funcComment;
                    $funcParams[$originalFuncName]['file'] = $fileName;
                    $funcParams[$originalFuncName]['line'] = $defineStartLine;
                }
            }

            $this->result['functions'] = $funcParams;

            $internalParams = array();
            $allInternalFuncs = $allFunctions['internal'];
            if (!empty($allInternalFuncs)) {
                foreach ($allInternalFuncs as $eachFuncName) {
                    $ref = new ReflectionFunction($eachFuncName);
                    $params = $ref->getParameters();
                    if (!empty($params)) {
                        foreach ($params as $eachParam) {
                            $paramName = $eachParam->getName();
                            $paramOptional = $eachParam->isOptional();
                            $internalParams[$eachFuncName][$paramName] = $paramOptional;
                        }
                    }
                }
            }
            $this->result['internal'] = $internalParams;
        }
    }

    public function parseDocBlock($docComment, $extensive = true) {
        $result = '';
        if ($docComment) {
            $docComment = preg_replace('/^\/\*\*|\*\/$/', '', $docComment);
            $lines = preg_split('/\R/', $docComment);
            $cleanedLines = array_map(fn($line) => preg_replace('/^\s*\*\s?/', '', $line), $lines);
            $cleanedComment = trim(implode("\n", $cleanedLines));

            preg_match_all('/@(\w+)\s+([^\n]+)/', $cleanedComment, $matches, PREG_SET_ORDER);

            if ($extensive) {
                $result .= __('Description') . ':' . PHP_EOL;
            }
            $result .= strtok($cleanedComment, '@') . "\n\n";

            if ($extensive) {
                $result .= __('Details') . ':' . PHP_EOL;
                foreach ($matches as $match) {
                    $result .= ucfirst($match[1]) . ": " . trim($match[2]) . "\n";
                }
            }
        }
        return ($result);
    }

    protected function cleanFilePath($path = '') {
        $result = '';
        if (!empty($path)) {
            $path = str_replace('/usr/local/www/apache24/data/', '', $path);
            $path = str_replace('/var/www/html/', '', $path);
            $path = str_replace('dev/ubilling/', '', $path);
            $path = str_replace('billing/', '', $path);
            $result = $path;
        }
        return ($result);
    }

    public function getCodeEnv() {
        return ($this->result);
    }
}
