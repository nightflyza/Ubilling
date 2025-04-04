<?php

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
     * Ubilling messages helper placeholder
     *
     * @var object
     */
    protected $messages='';

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
        $this->initMessages();
    }

    /**
     * Initializes messages helper placeholder
     *
     * @return void
     */

    protected function initMessages() {
        $this->messages=new UbillingMessageHelper();
    }

    /**
     * Loads config file content into protected properties
     * 
     * @param string $configPath Path to config file
     * @return void
     */
    public function loadConfig($configPath) {
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
    public function getConfigAsText() {
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
                    
                    // Check if we have a POST value for this option
                    if (ubRouting::checkPost(array('configforge_submit'))) {
                        $submitId = ubRouting::post('configforge_submit');
                        if ($submitId === $this->instanceId) {
                            $postData = ubRouting::rawPost();
                            if (isset($postData[$option])) {
                                $value = $postData[$option];
                            }
                        }
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
     * Saves current config back to file preserving comments
     * 
     * @return bool
     */
    public function saveConfig() {
        $result = false;
        if (is_writable($this->configPath)) {
            $configContent = $this->getConfigAsText();
            file_put_contents($this->configPath, $configContent);
            $result = true;
        }
        return $result;
    }

    /**
     * Gets config value by key
     * 
     * @param string $key Config key name
     * @param string $default Default value to return if key doesn't exist
     * @return mixed
     */
    public function getValue($key, $default = false) {
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
    public function setValue($key, $value) {
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
            $errors[] = $this->messages->getStyledMessage(__('Spec file does not exist').': '.$this->specPath, 'error');
        } elseif (!is_readable($this->specPath)) {
            $errors[] = $this->messages->getStyledMessage(__('Spec file is not readable').': '.$this->specPath, 'error');
        }
        
        // If there are errors, display them and return
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $result .= $error . wf_delimiter(0);
            }
            return $result;
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
                            $result .= wf_Trigger($option, $labelText, $currentValue);
                            $result .= wf_delimiter(0);
                            break;
                            
                        case 'CHECKBOX':
                            $values = !empty($sectionData['VALUES']) ? explode(',', $sectionData['VALUES']) : array('1', '0');
                            $isChecked = ($currentValue == $values[0]);
                            $result .= wf_CheckInput($option, $labelText, false, $isChecked);
                            $result .= wf_delimiter(0);
                            break;
                            
                        case 'RADIO':
                            $values = !empty($sectionData['VALUES']) ? explode(',', $sectionData['VALUES']) : array();
                            $result .= $labelText . wf_delimiter(0);
                            foreach ($values as $value) {
                                $isChecked = ($currentValue == $value);
                                $result .= wf_RadioInput($option, $value, $value, false, $isChecked);
                                $result .= wf_delimiter(0);
                            }
                            break;
                            
                        case 'SELECT':
                            $values = !empty($sectionData['VALUES']) ? explode(',', $sectionData['VALUES']) : array();
                            $params = array();
                            foreach ($values as $value) {
                                $params[$value] = $value;
                            }
                            $result .= wf_Selector($option, $params, $labelText, $currentValue);
                            $result .= wf_delimiter(0);
                            break;
                            
                        case 'TEXT':
                            $pattern = (!empty($sectionData['PATTERN'])) ? $sectionData['PATTERN'] : '';
                            $result .= wf_TextInput($option, $labelText, $currentValue, false, '', $pattern);
                            $result .= wf_delimiter(0);
                            break;
                            
                        default:
                            $result .= wf_TextInput($option, $labelText, $currentValue);
                            $result .= wf_delimiter(0);
                    }
                }
                
                // Add hidden input to identify ConfigForge form submission and instance
                $result .= wf_HiddenInput('configforge_submit', $this->instanceId);
                
                // Submit button
                $result .= wf_Submit('Save');
                
                // Wrap in form
                $result = wf_Form('', 'POST', $result, $this->formClass);
            } else {
                $result .= $this->messages->getStyledMessage(__('Spec file is empty or invalid'), 'error');
            }
        }
        return $result;
    }

    /**
     * Returns the text representation of the edited config
     * 
     * @return string
     */
    public function getConfigText() {
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
                    
                    // Check if we have a POST value for this option
                    if (ubRouting::checkPost(array('configforge_submit'))) {
                        $submitId = ubRouting::post('configforge_submit');
                        if ($submitId === $this->instanceId) {
                            $postData = ubRouting::rawPost();
                            if (isset($postData[$option])) {
                                $value = $postData[$option];
                            }
                        }
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
     * Handles form submission and updates config
     * 
     * @return string
     */
    public function handleSubmit() {
        $result = '';
        
        // Check if this is a ConfigForge form submission for this instance
        if (ubRouting::checkPost(array('configforge_submit'))) {
            $submitId = ubRouting::post('configforge_submit');
            if ($submitId === $this->instanceId) {
                $postData = ubRouting::rawPost();
                
                if (!empty($postData) and is_readable($this->specPath)) {
                    $specData = rcms_parse_ini_file($this->specPath, true);
                    $updated = false;
                    
                    // Process each option from spec file
                    foreach ($specData as $section => $props) {
                        if (isset($props['OPTION'])) {
                            $option = $props['OPTION'];
                            
                            // If this option was submitted in the form
                            if (isset($postData[$option])) {
                                $value = $postData[$option];
                                
                                // Handle checkbox values
                                if (isset($props['TYPE']) and $props['TYPE'] === 'CHECKBOX') {
                                    $values = !empty($props['VALUES']) ? explode(',', $props['VALUES']) : array('1', '0');
                                    $value = $value ? $values[0] : $values[1];
                                }
                                
                                // Handle trigger values
                                if (isset($props['TYPE']) and $props['TYPE'] === 'TRIGGER') {
                                    $values = !empty($props['VALUES']) ? explode(',', $props['VALUES']) : array('1', '0');
                                    $value = $value ? $values[0] : $values[1];
                                }
                                
                                // Validate value if validator exists
                                if (!empty($props['VALIDATOR'])) {
                                    $validator = $props['VALIDATOR'];
                                    $validationError = '';
                                    
                                    // Check if validator is a method in this class
                                    if (method_exists($this, $validator)) {
                                        if (!$this->$validator($value)) {
                                            $validationError = __('Validation failed for') . ' ' . $option;
                                        }
                                    } 
                                    // Check if validator is a global function
                                    else if (function_exists($validator)) {
                                        if (!$validator($value)) {
                                            $validationError = __('Validation failed for') . ' ' . $option;
                                        }
                                    }
                                    // If validator exists but neither method nor function found
                                    else {
                                        $validationError = __('Validator not found') . ': ' . $validator . ' ' . __('for option') . ' ' . $option;
                                    }
                                    
                                    if (!empty($validationError)) {
                                        return $this->messages->getStyledMessage($validationError, 'error');
                                    }
                                }
                                
                                // Set the value in our parsed config
                                $this->setValue($option, $value);
                                $updated = true;
                            }
                        }
                    }
                    
                    if ($updated) {
                        return $this->messages->getStyledMessage(__('Config updated successfully'), 'success');
                    }
                }
            }
        }
        
        return $result;
    }
}

