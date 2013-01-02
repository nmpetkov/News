/**
 * On the first keypress (only), this function checks if any textareas have been
 * assigned a Javascript editor via scribite, which is indicated by an element
 * with id='scribiteeditorused.<textareaid>'. If so, it sets the corresponding
 * textarea's News 'contenttype' to 'html' and then disables the form field.
 * This is to improve UI and prevent the user from mistakenly setting the field
 * to 'text'. This value however is not passed (as it is disabled) so the value
 * from the 'scribiteeditorused' element is used instead in the controller.
 */
function initEditorCheck() 
{
    var keyPressHandler = function(event) {
        // unregister the handler for future keypress events
        $('news_user_newform').stopObserving('keypress', keyPressHandler)
        // check each textarea for editor usage via scribite
        var textareas = $$('textarea');
        for(i = 0; i < textareas.length; i++) {
            if ($('scribiteeditorused.'+textareas[i].id)) { // if element exists then editor in use
                $(textareas[i].id+'contenttype').value = "1";
                $(textareas[i].id+'contenttype').disable();
            }
        }

    }
    // catch first keypress and check for editor usage to improve UI
    $('news_user_newform').observe('keypress', keyPressHandler)
}

Event.observe(window, 'load', initEditorCheck);