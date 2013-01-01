/**
 * News
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