<?php

/**
 * ConfigForge class for managing configuration files
 * 
 * This class provides functionality to load, edit, and save configuration files
 * based on a specification file.
 * available types: TEXT, CHECKBOX, RADIO, SELECT, TRIGGER, PASSWORD, SLIDER
 * available patterns for TEXT type:
 * - alpha: only Latin letters [a-zA-Z] (e.g., "abcDEF")
 * - alphanumeric: only Latin letters and numbers [a-zA-Z0-9] (e.g., "abc123")
 * - digits: only digits [0-9] (e.g., "12345")
 * - email: valid email address format (e.g., "user@domain.com")
 * - finance: decimal numbers with optional decimal point (e.g., "123.45", "100", "0.99")
 * - float: floating point numbers (e.g., "123.45", "0.001")
 * - fullpath: absolute Unix-style paths starting with / (e.g., "/var/www/html")
 * - geo: geographic coordinates (e.g., "40.7143528,-74.0059731")
 * - ip: IPv4 address format (e.g., "192.168.1.1")
 * - login: username format with letters, numbers and underscore (e.g., "user_123")
 * - mac: MAC address format with : or - separator (e.g., "00:1A:2B:3C:4D:5E")
 * - mobile: phone number with optional country code (e.g., "+380501234567")
 * - net-cidr: network CIDR notation, mask can't be /31 (e.g., "192.168.1.0/24")
 * - filepath: relative or absolute Unix-style paths (e.g., "dir/file.txt", "/etc/config")
 * - dirpath: relative or absolute Unix-style directories paths (e.g., "dir/", "/etc/")
 * - pathorurl: URLs with optional ports or paths (e.g., "http://example.com:8080", "some/dir/")
 * - sigint: signed integers (e.g., "-123", "456")
 * - url: HTTP/HTTPS URLs with optional port numbers (e.g., "http://example.com:8080")
 * 
 * Specification file example:  
 * 
 * [sectionname]
 * LABEL="Option label"
 * OPTION=SOME_OPTION
 * TYPE=CHECKBOX
 * DEFAULT=0
 * 
 * [sectionname2]
 * LABEL="Your sex?"
 * OPTION=SEX
 * TYPE=SELECT
 * VALUES="male,female,unknown"
 * DEFAULT="unknown"
 * SAVEFILTER="gigasafe"
 * 
 * [sectionname3]
 * LABEL="Option label 2"
 * OPTION=ANOTHER_OPTION
 * TYPE=TEXT
 * PATTERN="mac"
 * VALIDATOR="IsMacValid"
 * ONINVALID="This mac address is invalid"
 * DEFAULT="14:88:92:94:94:61"
 * 
 * [sectionname4]
 * LABEL="Volume level"
 * OPTION=VOLUME
 * VALUES="0..100"
 * TYPE=SLIDER
 * DEFAULT=50
 * 
 * 
 * class usage example:
 *     $configPath = 'config/test.ini';
 *     $specPath = 'config/test.spec';
 *     $forge = new ConfigForge($configPath, $specPath);
 *   $processResult = $forge->process();
 *   if (!empty($processResult)) {
 *       show_error($processResult);
 *   } elseif (ubRouting::post(ConfigForge::FORM_SUBMIT_KEY)==$forge->getInstanceId()) {
 *       ubRouting::nav('?module=testing');
 *   }
 *    
 *   show_window(__('Config Forge'), $forge->renderEditor());
 */
class ConfigForge {

    /**
     * Contains current config lines as index=>line
     *
     * @var array
     */
    protected $currentConfig = array();

    /**
     * Path to the config file
     * 
     * @var string
     */
    protected $configPath = '';

    /**
     * Contains parsed config data as section=>key=>value
     * 
     * @var array
     */
    protected $parsedConfig = array();

    /**
     * Contains comments for each config line
     * 
     * @var array
     */
    protected $lineComments = array();

    /**
     * Path to the spec file
     * 
     * @var string
     */
    protected $specPath = '';

    /**
     * Unique identifier for this instance
     * 
     * @var string
     */
    protected $instanceId = '';

    /**
     * Form CSS class
     * 
     * @var string
     */
    protected $formClass = 'glamour';

    /**
     * Form submission identifier
     */
    const FORM_SUBMIT_KEY = 'configforge_submit';

    // ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣠⠀⠀⠀⠀⠀⠀
    // ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠰⣾⣷⣄⠀⠀⠀⠀⠀⠀⠀⠀⣴⡏⠀⠀⢀⠀⠀⠀
    // ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢻⣿⣿⣷⣄⠀⠀⠀⠀⠀⢀⣼⣿⁣⣤⡶⠁⠀⠀⠀
    // ⠀⠀⠀⠀⠀⠀⠀⢀⣀⣤⣶⣿⣿⣿⣿⣿⡄⠀⢠⣷⣾⣿⣿⣿⣿⣁⣀⡀⠀⠀
    // ⠀⠀⠀⢀⣠⣴⣾⡿⠟⠋⠉⠀⠈⢿⣿⣿⠷⠀⣾⠿⠛⣿⣿⠿⠛⠋⠁⠀⠀⠀
    // ⠀⢲⣿⡿⠟⠋⠁⠀⠀⠀⠀⢀⣀⣈⣁⣀⣀⣀⣀⣀⣀⣁⣀⣀⣀⡀⠀⠀⠀⠀
    // ⠀⠈⠁⠀⠀⠀⢠⣤⣤⣤⣤⣼⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣇⠀⠀⠀⠀
    // ⠀⠀⠀⠀⠀⠀⠀⠙⠿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣷⠄⠀
    // ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠙⢻⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡟⠋⠁⠀⠀
    // ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠘⠛⠛⢛⣿⣿⣿⣿⣿⣿⡟⠛⠛⠛⠃⠀⠀⠀⠀
    // ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣾⣿⣿⣿⣿⣿⣿⣿⣆⠀⠀⠀⠀⠀⠀⠀
    // ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣀⣴⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣷⣄⣀⡀⠀⠀⠀
    // ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠉⠉⠉⠉⠉⠉⠉⠉⠉⠉⠉⠉⠉⠉⠉⠉⠁⠀⠀⠀

    /**
     * Creates new ConfigForge instance
     * 
     * @param string $configPath Path to config file
     * @param string $specPath Path to spec file
     * @return void
     */
    public function __construct($configPath, $specPath) {
        $this->configPath = $configPath;
        $this->specPath = $specPath;
        $this->instanceId = md5($configPath . $specPath);
        $this->loadConfig($configPath);
    }

    /**
     * Loads config file content into protected properties
     * 
     * @param string $configPath Path to config file
     * @return void
     */
    protected function loadConfig($configPath) {
        if (is_readable($configPath)) {
            $configTmp = file_get_contents($configPath);
            if (!empty($configTmp)) {
                $this->currentConfig = explodeRows($configTmp);
                $this->parsedConfig = rcms_parse_ini_file($configPath, false);
                $this->extractComments();
            }
        }
    }

    /**
     * Extracts comments from config lines
     * 
     * @return void
     */
    protected function extractComments() {
        $this->lineComments = array();
        foreach ($this->currentConfig as $lineNum => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Check for standalone comments
            if (substr($line, 0, 1) === ';') {
                $this->lineComments[$lineNum] = $line;
                continue;
            }

            // Check for inline comments
            if (strpos($line, ';') !== false) {
                $parts = explode(';', $line, 2);
                $this->lineComments[$lineNum] = trim($parts[1]);
            }
        }
    }

    /**
     * Returns the current config as text for debugging
     * 
     * @return string
     */
    protected function getConfigAsText() {
        $configContent = '';
        $processedOptions = array();

        // First, process all lines in their original order
        foreach ($this->currentConfig as $lineNum => $line) {
            $line = trim($line);
            if (empty($line)) {
                $configContent .= PHP_EOL;
                continue;
            }

            // Handle standalone comments
            if (substr($line, 0, 1) === ';') {
                $configContent .= $line . PHP_EOL;
                continue;
            }

            // Handle key-value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // If this option exists in our parsed config, use the updated value
                if (isset($this->parsedConfig[$key])) {
                    $newValue = $this->parsedConfig[$key];
                    // Escape all non-numeric values (except 0 and 1) with quotes
                    if (!is_numeric($newValue) and $newValue !== '0' and $newValue !== '1' and !empty($newValue)) {
                        $newValue = '"' . $newValue . '"';
                    }
                    $line = $key . '=' . $newValue;
                }

                // Add inline comment if exists
                if (isset($this->lineComments[$lineNum])) {
                    $line .= ' ;' . $this->lineComments[$lineNum];
                }

                $configContent .= $line . PHP_EOL;
                $processedOptions[] = $key;
            }
        }

        // Add any new options from spec file that weren't in the original config
        if (is_readable($this->specPath)) {
            $specData = rcms_parse_ini_file($this->specPath, true);
            foreach ($specData as $section => $props) {
                if (isset($props['OPTION']) and !in_array($props['OPTION'], $processedOptions)) {
                    $option = $props['OPTION'];
                    $value = '';

                    // First try to get value from POST if available
                    if (ubRouting::checkPost(array(self::FORM_SUBMIT_KEY))) {
                        $submitId = ubRouting::post(self::FORM_SUBMIT_KEY);
                        if ($submitId === $this->instanceId) {
                            $postData = ubRouting::rawPost();
                            $uniqueInputName = $option . '_' . $this->instanceId;
                            if (isset($postData[$uniqueInputName])) {
                                $value = $postData[$uniqueInputName];
                            }
                        }
                    }

                    // For non-TEXT and non-PASSWORD types, use default if value is empty
                    if (empty($value) and isset($props['TYPE']) and !in_array($props['TYPE'], array('TEXT', 'PASSWORD')) and isset($props['DEFAULT'])) {
                        $value = $props['DEFAULT'];
                    }

                    // Handle checkbox and trigger values
                    if (isset($props['TYPE']) and ($props['TYPE'] === 'CHECKBOX' or $props['TYPE'] === 'TRIGGER')) {
                        $values = !empty($props['VALUES']) ? explode(',', $props['VALUES']) : array('1', '0');
                        $value = $value ? $values[0] : $values[1];
                    }

                    // Optional pre-save filter
                    if (isset($props['SAVEFILTER'])) {
                        $value=ubRouting::filters($value,$props['SAVEFILTER']);
                    }

                    // Escape all non-numeric values (except 0 and 1) with quotes, but only if not empty
                    if (!is_numeric($value) and $value !== '0' and $value !== '1' and !empty($value)) {
                        $value = '"' . $value . '"';
                    }

                    $line = $option . '=' . $value;
                    $configContent .= $line . PHP_EOL;
                }
            }
        }

        $configContent = rtrim($configContent);
        return ($configContent . PHP_EOL);
    }

    /**
     * Saves current config back to file preserving comments
     * 
     * @return string Empty string on success, error message on failure
     */
    protected function saveConfig() {
        if (!is_writable($this->configPath)) {
            return (__('Failed to save config file') . ': ' . $this->configPath);
        }

        $configContent = $this->getConfigAsText();
        if (file_put_contents($this->configPath, $configContent) === false) {
            return (__('Failed to write config file') . ': ' . $this->configPath);
        }

        return ('');
    }

    /**
     * Gets config value by key
     * 
     * @param string $key Config key name
     * @param string $default Default value to return if key doesn't exist
     * @return mixed
     */
    protected function getValue($key, $default = false) {
        $result = $default;
        if (isset($this->parsedConfig[$key])) {
            $result = $this->parsedConfig[$key];
        }
        return $result;
    }

    /**
     * Sets config value for key
     * 
     * @param string $key Config key name
     * @param string $value New value to set
     * @return bool
     */
    protected function setValue($key, $value) {
        $this->parsedConfig[$key] = $value;
        return true;
    }

    /**
     * Returns instance identifier
     * 
     * @return string
     */
    public function getInstanceId() {
        return $this->instanceId;
    }

    /**
     * Sets form CSS class
     * 
     * @param string $class CSS class name
     * @return void
     */
    public function setFormClass($class) {
        $this->formClass = $class;
    }

    /**
     * Renders form editor for config file based on spec file
     * 
     * @return string
     */
    public function renderEditor() {
        $result = '';
        $errors = array();

        // Check if spec file exists and is readable
        if (!file_exists($this->specPath)) {
            $errors[] = __('Spec file does not exist') . ': ' . $this->specPath;
        } elseif (!is_readable($this->specPath)) {
            $errors[] = __('Spec file is not readable') . ': ' . $this->specPath;
        }

        // If there are errors, display them and return
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $result .= $error . wf_delimiter(0);
            }
            return ($result);
        }

        // If spec file is readable, proceed with rendering the form
        if (is_readable($this->specPath)) {
            $specData = rcms_parse_ini_file($this->specPath, true);
            if (!empty($specData)) {
                foreach ($specData as $section => $sectionData) {
                    // Skip if section data is not an array
                    if (!is_array($sectionData)) {
                        continue;
                    }

                    // Get the option name and properties
                    $option = isset($sectionData['OPTION']) ? $sectionData['OPTION'] : '';
                    if (empty($option)) {
                        continue;
                    }

                    // Create unique input name by appending instance ID
                    $uniqueInputName = $option . '_' . $this->instanceId;

                    // Get current value from config file
                    $currentValue = $this->getValue($option, false);

                    // If option doesn't exist in config, use DEFAULT from spec for input state
                    if ($currentValue === false and isset($sectionData['DEFAULT'])) {
                        $currentValue = $sectionData['DEFAULT'];
                    }

                    // Label - use LABEL from spec if available, otherwise use OPTION name
                    $labelText = (!empty($sectionData['LABEL'])) ? __($sectionData['LABEL']) : $option;

                    // Input field based on type
                    $type = isset($sectionData['TYPE']) ? $sectionData['TYPE'] : 'TEXT';
                    switch ($type) {
                        case 'TRIGGER':
                            $values = !empty($sectionData['VALUES']) ? explode(',', $sectionData['VALUES']) : array('1', '0');
                            $result .= wf_Trigger($uniqueInputName, $labelText, $currentValue);
                            $result .= wf_delimiter(0);
                            break;

                        case 'CHECKBOX':
                            $values = !empty($sectionData['VALUES']) ? explode(',', $sectionData['VALUES']) : array('1', '0');
                            $isChecked = ($currentValue == $values[0]);
                            $result .= wf_CheckInput($uniqueInputName, $labelText, false, $isChecked);
                            $result .= wf_delimiter(0);
                            break;

                        case 'RADIO':
                            $values = !empty($sectionData['VALUES']) ? explode(',', $sectionData['VALUES']) : array();
                            $result .= wf_tag('label') . $labelText . wf_tag('label', true) . wf_delimiter(0);
                            foreach ($values as $value) {
                                $isChecked = ($currentValue == $value);
                                $result .= wf_RadioInput($uniqueInputName, $value, $value, false, $isChecked);
                                $result .= wf_delimiter(0);
                            }
                            break;

                        case 'SELECT':
                            $values = !empty($sectionData['VALUES']) ? explode(',', $sectionData['VALUES']) : array();
                            $params = array();
                            foreach ($values as $value) {
                                $params[$value] = $value;
                            }
                            $result .= wf_Selector($uniqueInputName, $params, $labelText, $currentValue);
                            $result .= wf_delimiter(0);
                            break;

                        case 'PASSWORD':
                            $result .= wf_PasswordInput($uniqueInputName, $labelText, $currentValue, false, '', false);
                            $result .= wf_delimiter(0);
                            break;

                        case 'TEXT':
                            $pattern = (!empty($sectionData['PATTERN'])) ? $sectionData['PATTERN'] : '';
                            $result .= wf_TextInput($uniqueInputName, $labelText, $currentValue, false, '', $pattern);
                            $result .= wf_delimiter(0);
                            break;

                        case 'SLIDER':
                            // Parse range from VALUES or use default 0..100
                            $range = array(0, 100); // default range
                            if (!empty($sectionData['VALUES'])) {
                                $rangeStr = trim($sectionData['VALUES']);
                                if (preg_match('/^(\d+)\.\.(\d+)$/', $rangeStr, $matches)) {
                                    $range = array(intval($matches[1]), intval($matches[2]));
                                }
                            }
                            // Ensure current value is within range
                            $currentValue = intval($currentValue);
                            if ($currentValue < $range[0]) {
                                $currentValue = $range[0];
                            } elseif ($currentValue > $range[1]) {
                                $currentValue = $range[1];
                            }
                            $result .= wf_SliderInput($uniqueInputName, $labelText, $currentValue, $range[0], $range[1]);
                            $result .= wf_delimiter(0);
                            break;

                        default:
                            $result .= wf_TextInput($uniqueInputName, $labelText, $currentValue);
                            $result .= wf_delimiter(0);
                    }
                }

                // Add hidden input to identify ConfigForge form submission and instance
                $result .= wf_HiddenInput(self::FORM_SUBMIT_KEY, $this->instanceId);

                // Submit button
                $result .= wf_Submit('Save');

                // Wrap in form
                $result = wf_Form('', 'POST', $result, $this->formClass);
            } else {
                $result .= __('Spec file is empty or invalid');
            }
        }
        return ($result);
    }

    /**
     * Returns the text representation of the edited config
     * 
     * @return string
     */
    protected function getConfigText() {
        $configContent = '';
        $processedOptions = array();

        // First, process all lines in their original order
        foreach ($this->currentConfig as $lineNum => $line) {
            $line = trim($line);
            if (empty($line)) {
                $configContent .= PHP_EOL;
                continue;
            }

            // Handle standalone comments
            if (substr($line, 0, 1) === ';') {
                $configContent .= $line . PHP_EOL;
                continue;
            }

            // Handle key-value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // If this option exists in our parsed config, use the updated value
                if (isset($this->parsedConfig[$key])) {
                    $newValue = $this->parsedConfig[$key];
                    // Escape all non-numeric values (except 0 and 1) with quotes
                    if (!is_numeric($newValue) and $newValue !== '0' and $newValue !== '1' and !empty($newValue)) {
                        $newValue = '"' . $newValue . '"';
                    }
                    $line = $key . '=' . $newValue;
                }

                // Add inline comment if exists
                if (isset($this->lineComments[$lineNum])) {
                    $line .= ' ;' . $this->lineComments[$lineNum];
                }

                $configContent .= $line . PHP_EOL;
                $processedOptions[] = $key;
            }
        }

        // Add any new options from spec file that weren't in the original config
        if (is_readable($this->specPath)) {
            $specData = rcms_parse_ini_file($this->specPath, true);
            foreach ($specData as $section => $props) {
                if (isset($props['OPTION']) and !in_array($props['OPTION'], $processedOptions)) {
                    $option = $props['OPTION'];
                    $value = '';

                    // First try to get value from POST if available
                    if (ubRouting::checkPost(array(self::FORM_SUBMIT_KEY))) {
                        $submitId = ubRouting::post(self::FORM_SUBMIT_KEY);
                        if ($submitId === $this->instanceId) {
                            $postData = ubRouting::rawPost();
                            $uniqueInputName = $option . '_' . $this->instanceId;
                            if (isset($postData[$uniqueInputName])) {
                                $value = $postData[$uniqueInputName];
                            }
                        }
                    }

                    // If no POST value, try to get default from spec
                    if (empty($value) and isset($props['DEFAULT'])) {
                        $value = $props['DEFAULT'];
                    }

                    // Handle checkbox and trigger values
                    if (isset($props['TYPE']) and ($props['TYPE'] === 'CHECKBOX' or $props['TYPE'] === 'TRIGGER')) {
                        $values = !empty($props['VALUES']) ? explode(',', $props['VALUES']) : array('1', '0');
                        $value = $value ? $values[0] : $values[1];
                    }

                    // Escape all non-numeric values (except 0 and 1) with quotes, but only if not empty
                    if (!is_numeric($value) and $value !== '0' and $value !== '1' and !empty($value)) {
                        $value = '"' . $value . '"';
                    }

                    $line = $option . '=' . $value;
                    $configContent .= $line . PHP_EOL;
                }
            }
        }

        return $configContent;
    }

    /**
     * Process config editing request
     * Handles form submission and config saving in one place
     * 
     * @return string Empty string on success, error message on failure
     */
    public function process() {
        // Check if this is a ConfigForge form submission for this instance
        if (!ubRouting::checkPost(array(self::FORM_SUBMIT_KEY))) {
            return ('');
        }

        $submitId = ubRouting::post(self::FORM_SUBMIT_KEY);
        if ($submitId !== $this->instanceId) {
            return ('');
        }

        $postData = ubRouting::rawPost();
        if (empty($postData)) {
            return (__('No data received'));
        }

        if (!is_readable($this->specPath)) {
            return (__('Spec file is not readable') . ': ' . $this->specPath);
        }

        $specData = rcms_parse_ini_file($this->specPath, true);
        if (empty($specData)) {
            return (__('Spec file is empty or invalid') . ': ' . $this->specPath);
        }

        $updated = false;

        // Process each option from spec file
        foreach ($specData as $section => $props) {
            if (!isset($props['OPTION'])) {
                continue;
            }

            $option = $props['OPTION'];
            $uniqueInputName = $option . '_' . $this->instanceId;

            // For checkboxes, handle both present and not present in POST data
            if (isset($props['TYPE']) and $props['TYPE'] === 'CHECKBOX') {
                $values = !empty($props['VALUES']) ? explode(',', $props['VALUES']) : array('1', '0');
                $value = isset($postData[$uniqueInputName]) ? $values[0] : $values[1];
                
                // Applying optional save filter if exists
                if (!empty($props['SAVEFILTER'])) {
                    $value = ubRouting::filters($value, $props['SAVEFILTER']);
                }
                
                $this->setValue($option, $value);
                $updated = true;
                continue;
            }

            // For other types, process only if present in POST
            if (isset($postData[$uniqueInputName])) {
                $value = $postData[$uniqueInputName];

                // Handle trigger values
                if (isset($props['TYPE']) and $props['TYPE'] === 'TRIGGER') {
                    $values = !empty($props['VALUES']) ? explode(',', $props['VALUES']) : array('1', '0');
                    $value = $value ? $values[0] : $values[1];
                }

                // Validate value if validator exists
                if (!empty($props['VALIDATOR'])) {
                    $validator = $props['VALIDATOR'];
                    $validatorPassed = false;

                    // Default validation error notice
                    $onInvalidMessage = __('Validation failed for') . ' ' . $option;

                    // Or custom one
                    if (!empty($props['ONINVALID'])) {
                        $onInvalidMessage = __($props['ONINVALID']);
                    }

                    // Check if validator is a method in this class
                    if (method_exists($this, $validator)) {
                        if (!$this->$validator($value)) {
                            return ($onInvalidMessage);
                        } else {
                            $validatorPassed = true;
                        }
                    }

                    // Check if validator is a global function
                    if (function_exists($validator)) {
                        if (!$validator($value)) {
                            return ($onInvalidMessage);
                        } else {
                            $validatorPassed = true;
                        }
                    }

                    // If validator set but neither method nor function found
                    if (!$validatorPassed) {
                        return (__('Validator method not found') . ': ' . $validator . ' ' . __('for option') . ' ' . $option);
                    }
                }

                // Applying optional save filter if exists
                if (!empty($props['SAVEFILTER'])) {
                    $value = ubRouting::filters($value, $props['SAVEFILTER']);
                }

                // Set the value in our parsed config
                $this->setValue($option, $value);
                $updated = true;
            }
        }

        if ($updated) {
            // Try to save and check for errors
            $saveResult = $this->saveConfig();
            if (!empty($saveResult)) {
                return ($saveResult); // Return error message if save failed
            }
        }

        return ('');
    }
}
