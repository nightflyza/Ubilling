<?php

/**
 *  Generates uniqe id for element:
 * 
 *  @return  string
 */
function bootstrap_inputID() {
    // I know it looks really funny. 
    // You can also get a truly random values​by throwing dice ;)
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $result = "";
    for ( $p = 0; $p < 8; $p++ ) {
      $random  = mt_rand(0, (strlen($characters)-1));
      $result .= $characters[$random];
    }
    return $result;
}

function bootstrap_Modal($linkTitle, $modalTitle, $content, $linkClass = '', $width = '', $height = '') {
  $tagID = bootstrap_inputID(); 
  $linkClass = ( !empty($linkClass) ) ? ' class="' . $linkClass . '"' : '';
  $width  = ( !empty($width) )  ? $width  . 'px' : 'auto';
  $height = ( !empty($height) ) ? $height . 'px' : 'auto';
  $modal = '
    <div class="modal" id="' . $tagID . '" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="width: ' . $width . '">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
            <h4 class="modal-title" id="myModalLabel">' . __($modalTitle) . '</h4>
          </div>
          <div class="modal-body" style="height: ' . $height . '">
            ' . $content . '
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">' . __('Close') . '</button>
          </div>
        </div>
      </div>
    </div>
    <a href="#"' . $linkClass . ' data-toggle="modal" data-target="#' . $tagID . '">
      ' . __($linkTitle) . '
    </a>
  ';
  return $modal;
}
