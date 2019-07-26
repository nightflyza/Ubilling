<?php

/**
 * Basic Ubilling GET/POST abstraction class
 */
class ubRouting {

    /**
     * Creates new Routing object instance
     */
    public function __construct() {
        //What did you expect to see here?
    }

    /**
     * Checks is all of variables array present in GET scope
     * 
     * @param array $params array of variable names to check
     * @param bool  $ignoreEmpty ignore or not existing variables with empty values (like wf_Check)
     * 
     * @return bool
     */
    public static function checkGet($params, $ignoreEmpty = true) {
        if ($ignoreEmpty) {
            $result = true;
            if (!empty($params)) {
                foreach ($params as $eachparam) {
                    if (isset($_GET[$eachparam])) {
                        if (empty($_GET[$eachparam])) {
                            $result = false;
                            break;
                        }
                    } else {
                        $result = false;
                        break;
                    }
                }
            }
        } else {
            $result = true;
            if (!empty($params)) {
                foreach ($params as $index => $eachVariable) {
                    if (!isset($_GET[$eachVariable])) {
                        $result = false;
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Checks is all of variables array present in POST scope
     * 
     * @param array $params array of variable names to check
     * @param bool  $ignoreEmpty ignore or not existing variables with empty values (like wf_Check)
     * 
     * @return bool
     */
    public static function checkPost($params, $ignoreEmpty = true) {
        if ($ignoreEmpty) {
            $result = true;
            if (!empty($params)) {
                foreach ($params as $eachparam) {
                    if (isset($_POST[$eachparam])) {
                        if (empty($_POST[$eachparam])) {
                            $result = false;
                            break;
                        }
                    } else {
                        $result = false;
                        break;
                    }
                }
            }
        } else {
            $result = true;
            if (!empty($params)) {
                foreach ($params as $index => $eachVariable) {
                    if (!isset($_POST[$eachVariable])) {
                        $result = false;
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Returns filtered data
     * 
     * @param type $rawData data to be filtered
     * @param string $filtering filtering options. Possible values: raw, int, mres, callback
     * @param string/array $callback callback function name or names array to filter variable value
     * 
     * @return mixed/false
     * 
     * @throws Exception
     */
    protected static function filters($rawData, $filtering = 'raw', $callback = '') {
        $result = false;
        switch ($filtering) {
            case 'raw':
                return($rawData);
                break;
            case 'int':
                return(vf($rawData, 3));
                break;
            case 'mres':
                return(mysql_real_escape_string($rawData));
                break;
            case 'callback':
                if (!empty($callback)) {
                    //single callback function
                    if (!is_array($callback)) {
                        if (function_exists($callback)) {
                            return($callback($rawData));
                        } else {
                            throw new Exception('EX_CALLBACK_NOT_DEFINED');
                        }
                    } else {
                        $filteredResult = $rawData;
                        //multiple callback functions
                        foreach ($callback as $io => $eachCallbackFunction) {
                            if (function_exists($eachCallbackFunction)) {
                                $filteredResult = $eachCallbackFunction($filteredResult);
                            } else {
                                throw new Exception('EX_CALLBACK_NOT_DEFINED');
                            }
                        }
                        return($filteredResult);
                    }
                } else {
                    throw new Exception('EX_CALLBACK_EMPTY');
                }
                break;
            default :
                throw new Exception('EX_WRONG_FILTERING_MODE');
                break;
        }
        return($result);
    }

    /**
     * Returns some variable value with optional filtering from GET scope
     * 
     * @param string $name name of variable to extract
     * @param string $filtering filtering options. Possible values: raw, int, mres, callback
     * @param string/array $callback callback function name or names array to filter variable value
     * 
     * @return mixed/false
     */
    public static function get($name, $filtering = 'raw', $callback = '') {
        $result = false;
        if (isset($_GET[$name])) {
            return(self::filters($_GET[$name], $filtering, $callback));
        }
        return($result);
    }

    /**
     * Returns some variable value with optional filtering from POST scope
     * 
     * @param string $name name of variable to extract
     * @param string $filtering filtering options. Possible values: raw, int, mres, callback
     * @param string $callback callback function name to filter variable value
     * 
     * @return mixed/false
     */
    public static function post($name, $filtering = 'raw', $callback = '') {
        $result = false;
        if (isset($_POST[$name])) {
            return(self::filters($_POST[$name], $filtering, $callback));
        }
        return($result);
    }

    /**
     * Short rcms_redirect replacement
     * 
     * @param string $url URL to perform redirect
     * 
     * @return void
     */
    public static function nav($url) {
        if (!empty($url)) {
            rcms_redirect($url);
        }
    }

    /**
     * Returns complete $_GET array as is
     * 
     * @return array
     */
    public static function rawGet() {
        return($_GET);
    }

    /**
     * Returns complete $_POST array as is
     * 
     * @return array
     */
    public static function rawPost() {
        return($_POST);
    }

}
