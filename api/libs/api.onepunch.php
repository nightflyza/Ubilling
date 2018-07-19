<?php

class OnePunch {

    /**
     * Contains available punch scripts as alias=>data
     *
     * @var array
     */
    protected $punchScripts = array();

    /**
     * Contains default devconsole URL
     */
    const URL_DEVCON = '?module=sqlconsole&devconsole=true';

    /**
     * Creates new object instance
     */
    public function __construct() {
        $this->loadScripts();
    }

    /**
     * Loads existing punch scripts from database
     * 
     * @return void
     */
    protected function loadScripts() {
        $query = "SELECT * from `punchscripts`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->punchScripts[$each['alias']] = $each;
            }
        }
    }

    /**
     * Checks is some script name unused?
     * 
     * @param sring $alias
     * 
     * @return bool
     */
    protected function checkAlias($alias) {
        $alias = vf($alias);
        $result = true;
        if (isset($this->punchScripts[$alias])) {
            $result = false;
        }
        return ($result);
    }

    /**
     * Renders new script creation form
     * 
     * @return string
     */
    public function renderCreateForm() {
        $result = '';
        $inputs = '';
        $namePreset = (wf_CheckPost(array('newscriptname'))) ? $_POST['newscriptname'] : '';
        $aliasPreset = (wf_CheckPost(array('newscriptalias'))) ? $_POST['newscriptalias'] : '';
        $contentPreset = (wf_CheckPost(array('newscriptcontent'))) ? $_POST['newscriptcontent'] : '';

        $inputs.= wf_TextInput('newscriptname', __('Name'), $namePreset, true, 30);
        $inputs.= wf_TextInput('newscriptalias', __('Alias'), $aliasPreset, true, 15);
        $inputs.= wf_TextArea('newscriptcontent', '', $contentPreset, true, '80x15');
        $inputs.= wf_Submit(__('Create'));
        $result.= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Creates new script in database
     * 
     * @param string $alias
     * @param string $name
     * @param string $content
     * 
     * @return void/string on error
     */
    public function createScript($alias, $name, $content) {
        $result = '';
        $alias = vf($alias);
        $name = mysql_real_escape_string($name);
        $content = mysql_real_escape_string($content);
        if ($this->checkAlias($alias)) {
            $query = "INSERT INTO `punchscripts` (`id`,`alias`,`name`,`content`) VALUES ";
            $query.="(NULL,'" . $alias . "' ,'" . $name . "','" . $content . "');";
            nr_query($query);
            log_register('ONEPUNCH CREATE ALIAS `' . $alias . '`');
        } else {
            $result.=__('Something went wrong') . ': ' . __('Script with this alias already exists');
        }
        return ($result);
    }

}
