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
            $result = wf_CheckGet($params);
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
            $result = wf_CheckPost($params);
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
     * Returns some variable value with optional filtering from GET scope
     * 
     * @param string $name name of variable to extract
     * @param string $filtering filtering options. Possible values: raw, int, mres, callback
     * @param string $callback callback function name to filter variable value
     * 
     * @return mixed/false
     */
    public static function get($name, $filtering = 'raw', $callback = '') {
        $result = false;
        if (isset($_GET[$name])) {
            $rawData = $_GET[$name];
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
                        if (function_exists($callback)) {
                            return($callback($rawData));
                        } else {
                            throw new Exception('EX_CALLBACK_NOT_DEFINED');
                        }
                    } else {
                        throw new Exception('EX_CALLBACK_EMPTY');
                    }
                    break;
                default :
                    throw new Exception('EX_WRONG_FILTERING_MODE');
                    break;
            }
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
            $rawData = $_POST[$name];
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
                        if (function_exists($callback)) {
                            return($callback($rawData));
                        } else {
                            throw new Exception('EX_CALLBACK_NOT_DEFINED');
                        }
                    } else {
                        throw new Exception('EX_CALLBACK_EMPTY');
                    }
                    break;
                default :
                    throw new Exception('EX_WRONG_FILTERING_MODE');
                    break;
            }
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
