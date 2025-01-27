<?php

/**
 * Basic Ubilling GET/POST abstraction and filtering class
 */
class ubRouting {
    /**
     *    .- <O> -.        .-====-.      ,-------.      .-=<>=-.
     *   /_-\'''/-_\      / / '' \ \     |,-----.|     /__----__\
     *  |/  o) (o  \|    | | ')(' | |   /,'-----'.\   |/ (')(') \|
     *   \   ._.   /      \ \    / /   {_/(') (')\_}   \   __   /
     *   ,>-_,,,_-<.       >'=jf='<     `.   _   .'    ,'--__--'.
     *  /      .      \    /        \     /'-___-'\    /    :|    \
     * (_)     .     (_)  /          \   /         \  (_)   :|   (_)
     * \_-----'____--/  (_)        (_) (_)_______(_)   |___:|____|
     *  \___________/     |________|     \_______/     |_________|
     */

    /**
     * Creates new Routing object instance
     */
    public function __construct() {
        //What did you expect to see here?
    }

    /**
     * Checks is all of variables array present in GET scope
     * 
     * @param array/string $params array of variable names to check or single variable name as string
     * @param bool  $ignoreEmpty ignore or not existing variables with empty values (like wf_Check)
     * @param bool $atleastOneExists returns "true" when encounters the very first existent GET param. Respects the $ignoreEmpty parameter.
     * 
     * @return bool
     */
    public static function checkGet($params, $ignoreEmpty = true, $atleastOneExists = false) {
        if (!empty($params)) {
            if (!is_array($params)) {
                //single param check
                $params = array($params);
            }

            foreach ($params as $eachparam) {
                if (!isset($_GET[$eachparam])) {
                    if ($atleastOneExists) {
                        // traversing through the array till existent GET param found or the end of the array
                        continue;
                    } else {
                        return (false);
                    }
                } elseif ($atleastOneExists and !$ignoreEmpty) {
                    return (true);
                }

                if ($ignoreEmpty) {
                    if (empty($_GET[$eachparam])) {
                        if ($atleastOneExists) {
                            // traversing through the array till existent and non-empty GET param found or the end of the array
                            continue;
                        } else {
                            return (false);
                        }
                    } elseif ($atleastOneExists) {
                        return (true);
                    }
                }
            }

            if ($atleastOneExists) {
                // if "$atleastOneExists = true" and we got here - none of the GET parameters were existent or non-empty then,
                // and we failed to find at least one
                return (false);
            } else {
                return (true);
            }
        } else {
            throw new Exception('EX_PARAMS_EMPTY');
        }
    }

    /**
     * Checks is all of variables array present in POST scope
     *
     * @param array/string $params array of variable names to check or single variable name as string
     * @param bool  $ignoreEmpty ignore or not existing variables with empty values (like wf_Check)
     * @param bool $atleastOneExists returns "true" when encounters the very first existent GET param. Respects the $ignoreEmpty parameter.
     *
     * @return bool
     */
    public static function checkPost($params, $ignoreEmpty = true, $atleastOneExists = false) {
        if (!empty($params)) {
            if (!is_array($params)) {
                //single param check
                $params = array($params);
            }

            foreach ($params as $eachparam) {
                if (!isset($_POST[$eachparam])) {
                    if ($atleastOneExists) {
                        // traversing through the array till existent POST param found or the end of the array
                        continue;
                    } else {
                        return (false);
                    }
                } elseif ($atleastOneExists and !$ignoreEmpty) {
                    return (true);
                }

                if ($ignoreEmpty) {
                    if (empty($_POST[$eachparam])) {
                        if ($atleastOneExists) {
                            // traversing through the array till existent and non-empty POST param found or the end of the array
                            continue;
                        } else {
                            return (false);
                        }
                    } elseif ($atleastOneExists) {
                        return (true);
                    }
                }
            }

            if ($atleastOneExists) {
                // if "$atleastOneExists = true" and we got here - none of the POST parameters were existent or non-empty then,
                // and we failed to find at least one
                return (false);
            } else {
                return (true);
            }
        } else {
            throw new Exception('EX_PARAMS_EMPTY');
        }
    }

    /**
     * Returns filtered data
     * 
     * @param mixed $rawData data to be filtered
     * @param string $filtering filtering options. Possible values: raw, int, mres, callback, fi, vf, nb, float, login, safe, gigasafe
     * @param string|array/filter name $callback callback function name or names array to filter variable value. Or const filter name of php.net/filter
     * 
     * @return mixed|false
     * 
     * @throws Exception
     */
    public static function filters($rawData, $filtering = 'raw', $callback = '') {
        $result = false;
        switch ($filtering) {
            case 'raw':
                return ($rawData);
                break;
            case 'int':
                return (preg_replace("#[^0-9]#Uis", '', $rawData));
                break;
            case 'mres':
                return (mysql_real_escape_string($rawData));
                break;
            case 'vf':
                return (preg_replace("#[~@\+\?\%\/\;=\*\>\<\"\'\-]#Uis", '', $rawData));
                break;
            case 'nb':
                return (preg_replace('/\0/s', '', $rawData));
                break;
            case 'float':
                $filteredResult = preg_replace("#[^0-9.]#Uis", '', $rawData);
                if (is_numeric($filteredResult)) {
                    return ($filteredResult);
                } else {
                    return (false);
                }
                break;
            case 'login':
                $filteredResult = str_replace(' ', '_', $rawData);
                $loginAllowedChars = 'a-z0-9A-Z_\.' . $callback;
                $filteredLogin = preg_replace("#[^" . $loginAllowedChars . "]#Uis", '', $filteredResult);
                return ($filteredLogin);
                break;
            case 'safe':
                $rawData = preg_replace('/\0/s', '', $rawData);
                if (strpos($callback, 'HTML') !== false) {
                    $callback = str_replace('HTML', '', $rawData);
                } else {
                    $rawData = self::replaceQuotes($rawData);
                    $rawData = strip_tags($rawData);
                    $rawData = str_replace(array("'", '`'), '’', $rawData);
                }

                $allowedChars = 'a-zA-Z0-9А-Яа-яЁёЇїІіЄєҐґ\w++«»№’=_\ ,\.\-:;!?\(\){}\/\r\n\x{200d}\x{2600}-\x{1FAFF}' . $callback;
                $regex = '#[^' . $allowedChars . ']#u';
                $filteredData = preg_replace($regex, '', $rawData);
                $filteredData = str_replace('--', '', $filteredData);
                return ($filteredData);
            case 'gigasafe':
                $rawData = preg_replace('/\0/s', '', $rawData);
                $allowedChars = 'a-zA-Z0-9' . $callback;
                $regex = '#[^' . $allowedChars . ']#u';
                return (preg_replace($regex, '', $rawData));
            case 'fi':
                if (!empty($callback)) {
                    return (filter_var($rawData, $callback));
                } else {
                    throw new Exception('EX_FILTER_EMPTY');
                }
                break;
            case 'callback':
                if (!empty($callback)) {
                    //single callback function
                    if (!is_array($callback)) {
                        if (function_exists($callback)) {
                            return ($callback($rawData));
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
                        return ($filteredResult);
                    }
                } else {
                    throw new Exception('EX_CALLBACK_EMPTY');
                }
                break;

            default:
                throw new Exception('EX_WRONG_FILTERING_MODE');
                break;
        }
        return ($result);
    }
    /**
     * Replaces double quotes in a string with special characters.
     *
     * This method takes a string as input and replaces all occurrences of double quotes with special characters.
     *
     * @param string $string The input string to be processed.
     * @return string The processed string with double quotes replaced by special characters.
     */

    public static function replaceQuotes($string) {
        return (preg_replace('/"([^"]*)"/', '«$1»', $string));
    }

    /**
     * Returns some variable value with optional filtering from GET scope
     * 
     * @param string $name name of variable to extract
     * @param string $filtering filtering options. Possible values: raw, int, mres, callback, fi, vf, nb, float, login, safe, gigasafe
     * @param string|array $callback callback function name or names array to filter variable value
     * 
     * @return mixed|false
     */
    public static function get($name, $filtering = 'raw', $callback = '') {
        $result = false;
        if (isset($_GET[$name])) {
            return (self::filters($_GET[$name], $filtering, $callback));
        }
        return ($result);
    }

    /**
     * Returns some variable value with optional filtering from POST scope
     * 
     * @param string $name name of variable to extract
     * @param string $filtering filtering options. Possible values: raw, int, mres, callback, fi, vf, nb, float, login, safe, gigasafe
     * @param string $callback callback function name to filter variable value
     * 
     * @return mixed|false
     */
    public static function post($name, $filtering = 'raw', $callback = '') {
        $result = false;
        if (isset($_POST[$name])) {
            return (self::filters($_POST[$name], $filtering, $callback));
        }
        return ($result);
    }

    /**
     * Redirects user to some specified URL
     * 
     * @param string $url URL to perform redirect
     * @param bool $header Use header redirect instead of JS document.location
     * 
     * @return void
     */
    public static function nav($url, $header = false) {
        if (!empty($url)) {
            if ($header) {
                @header('Location: ' . $url);
            } else {
                print('<script language="javascript">document.location.href="' . $url . '";</script>');
            }
        }
    }

    /**
     * Returns complete $_GET array as is
     * 
     * @return array
     */
    public static function rawGet() {
        return ($_GET);
    }

    /**
     * Returns complete $_POST array as is
     * 
     * @return array
     */
    public static function rawPost() {
        return ($_POST);
    }

    /**
     * Checks is all of options array present in CLI command line options as --optionname=
     * 
     * @global array $argv
     * 
     * @param array|string $params array of variable names to check or single variable name as string
     * @param bool  $ignoreEmpty ignore or not existing variables with empty values 
     * 
     * @return bool
     */
    public static function optionCliCheck($params, $ignoreEmpty = true) {
        global $argv;
        $result = false;
        if (!empty($params)) {
            if (!is_array($params)) {
                //single param check
                $params = array($params);
            }

            foreach ($params as $eachparam) {
                if (!empty($argv)) {
                    foreach ($argv as $io => $eachArg) {
                        $result = false; //each new arg drops to false
                        $fullOptMask = '--' . $eachparam . '='; //yeah, opts like --optioname=value
                        $shortOptMask = '--' . $eachparam; //but we checks just for --optionname at start
                        if (ispos($eachArg, $shortOptMask)) {
                            if ($ignoreEmpty) {
                                if (ispos($eachArg, $fullOptMask)) {
                                    $optValue = str_replace($fullOptMask, '', $eachArg);
                                    if (!empty($optValue)) {
                                        $result = true;
                                    } else {
                                        $result = false;
                                    }
                                } else {
                                    $result = false;
                                }
                            } else {
                                $result = true;
                                return ($result);
                            }
                        }
                    }
                }
            }
            return ($result);
        } else {
            throw new Exception('EX_PARAMS_EMPTY');
        }
    }

    /**
     * Returns some variable value with optional filtering from CLI option
     * 
     * @global array $argv
     * 
     * @param string $name name of variable to extract from CLI options
     * @param string $filtering filtering options. Possible values: raw, int, mres, callback, fi, vf, nb, float, login, safe, gigasafe
     * @param string $callback callback function name to filter variable value
     * 
     * @return mixed|false
     */
    public static function optionCli($name, $filtering = 'raw', $callback = '') {
        global $argv;
        $result = false;
        if (!empty($argv)) {
            foreach ($argv as $io => $eachArg) {
                $fullOptMask = '--' . $name . '=';
                if (ispos($eachArg, $fullOptMask)) {
                    $optValue = str_replace($fullOptMask, '', $eachArg);
                    return (self::filters($optValue, $filtering, $callback));
                }
            }
        }
        return ($result);
    }

    /**
     * Returns current CLI application name
     * 
     * @global array $argv
     * 
     * @return string|false
     */
    public static function optionCliMe() {
        global $argv;
        $result = false;
        if (!empty($argv)) {
            if (isset($argv[0])) {
                $result = $argv[0];
            }
        }
        return ($result);
    }

    /**
     * Returns count of available CLI options
     * 
     * @global array $argc
     * 
     * @return int
     */
    public static function optionCliCount() {
        global $argc;
        return ($argc);
    }
}
