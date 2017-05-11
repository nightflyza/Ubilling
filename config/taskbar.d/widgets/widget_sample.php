<?php

class widget_sample extends TaskbarWidget {

    public function render() {
        $result = __('Sample text');
        return ($result);
    }

}

?>