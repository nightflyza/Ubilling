function checkForCharacters(inputString, checkString, startingIndex)
{
  if (!startingIndex) startingIndex = 0;
  return inputString.indexOf(checkString);
}

is_firefox = checkForCharacters(navigator.userAgent, 'Firefox');
is_ie      = checkForCharacters(navigator.userAgent, 'MSIE')
is_opera   = checkForCharacters(navigator.userAgent, 'Opera')

function select_move_up_selected_el(object){
    if(object.selectedIndex != -1){
        var was_norm_t = object.options[object.selectedIndex].text;
        var was_norm_v = object.options[object.selectedIndex].value;
        var was_up_t = object.options[object.selectedIndex-1].text;
        var was_up_v = object.options[object.selectedIndex-1].value;
        object.options[object.selectedIndex-1].text = was_norm_t;
        object.options[object.selectedIndex-1].value = was_norm_v;
        object.options[object.selectedIndex].text = was_up_t;
        object.options[object.selectedIndex].value = was_up_v;
        object.selectedIndex = object.selectedIndex-1;
    }
}
function select_move_down_selected_el(object){
    if(object.selectedIndex != -1){
        var was_norm_t = object.options[object.selectedIndex].text;
        var was_norm_v = object.options[object.selectedIndex].value;
        var was_up_t = object.options[object.selectedIndex+1].text;
        var was_up_v = object.options[object.selectedIndex+1].value;
        object.options[object.selectedIndex+1].text = was_norm_t;
        object.options[object.selectedIndex+1].value = was_norm_v;
        object.options[object.selectedIndex].text = was_up_t;
        object.options[object.selectedIndex].value = was_up_v;
        object.selectedIndex = object.selectedIndex+1;
    }
}
function add_to_select_from_another(object_from, object_to){
    var newoption = document.createElement('option');
    if(object_from.selectedIndex != -1){
        newoption.text = object_from.options[object_from.selectedIndex].text;
        newoption.value = object_from.options[object_from.selectedIndex].value;
        if(is_ie == -1) object_to.add(newoption, null); else object_to.add(newoption, 0);
        object_from.remove(object_from.selectedIndex);
    }
}
function on_submit_prepare(object1, object2){
    if(object1 != null){
        object1.multiple = true;
        i = 0;
        for (i=0; i<object1.options.length; i++){
            object1.options[i].selected = true;
        }
    }
    if(object2 != null){
        object2.multiple = true;
        i = 0;
        for (i=0; i<object2.options.length; i++){
            object2.options[i].selected = true;
        }
    }
    return false;
}