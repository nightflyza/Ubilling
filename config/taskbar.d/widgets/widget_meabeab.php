<?php

class widget_meabeab extends TaskbarWidget {

    public function render() {
        $result = '';
        $chance = rand(0, 10);
        if ($chance == 10) {
            //critical hit
            $result = wf_doSound('modules/jsc/sounds/meabeab.mp3');
        }
        return ($result);
    }

}

?>