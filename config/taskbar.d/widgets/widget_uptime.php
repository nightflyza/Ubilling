<?php

class widget_uptime extends TaskbarWidget {

    public function render() {
        $command = 'uptime';
        $result = $this->widgetContainer(shell_exec($command));
        return ($result);
    }

}

?>