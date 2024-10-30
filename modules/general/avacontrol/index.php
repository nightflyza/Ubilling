<?php
if (cfr('UBIM')) {
    show_window(__('Avatar control'),web_avatarControlForm(ubRouting::get('back')));
} else {
    show_error(__('Access denied'));
}
