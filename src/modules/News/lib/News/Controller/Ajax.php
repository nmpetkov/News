<?php

/**
 * Zikula Application Framework
 *
 * @copyright  (c) Zikula Development Team
 * @link       http://www.zikula.org
 * @license    GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author     Mark West <mark@zikula.org>
 * @category   Zikula_3rdParty_Modules
 * @package    Content_Management
 * @subpackage News
 */
class News_Controller_Ajax extends Zikula_Controller_AbstractAjax
{

    /**
     * modify a news entry (incl. delete) via ajax
     *
     * @author Frank Schummertz
     * @param 'sid'   int the story id
     * @param 'page'   int the story page
     * @return string HTML string
     */
    public function modify()
    {
        $this->checkAjaxToken();

        $sid = $this->request->getPost()->get('sid');
        $page = $this->request->getPost()->get('page', 1);

        // Get the news article
        $item = ModUtil::apiFunc('News', 'User', 'get', array('sid' => $sid));
        if ($item == false) {
            throw new Zikula_Exception_NotFound($this->__('Error! No such article found.'));
        }

        // Security check
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('News::', "{$item['cr_uid']}::$sid", ACCESS_EDIT));

        // Get the format types. 'home' string is bits 0-1, 'body' is bits 2-3.
        $item['hometextcontenttype'] = ($item['format_type'] % 4);
        $item['bodytextcontenttype'] = (($item['format_type'] / 4) % 4);

        // Set the publishing date options.
        if (!isset($item['to'])) {
            if (DateUtil::getDatetimeDiff_AsField($item['from'], $item['cr_date'], 6) >= 0 && is_null($item['to'])) {
                $item['unlimited'] = 1;
                $item['tonolimit'] = 1;
            } elseif (DateUtil::getDatetimeDiff_AsField($item['from'], $item['cr_date'], 6) < 0 && is_null($item['to'])) {
                $item['unlimited'] = 0;
                $item['tonolimit'] = 1;
            }
        } else {
            $item['unlimited'] = 0;
            $item['tonolimit'] = 0;
        }

        Zikula_AbstractController::configureView();
        $this->view->setCaching(false);

        $modvars = $this->getVars();

        if ($modvars['enablecategorization']) {
            $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('News', 'news');
            $this->view->assign('catregistry', $catregistry);
        }

        // Assign the item to the template
        $this->view->assign('item', $item);

        // Assign the current page
        $this->view->assign('page', $page);

        // Assign the default languagecode
        $this->view->assign('lang', ZLanguage::getLanguageCode());

        // Assign the content format
        $formattedcontent = ModUtil::apiFunc('News', 'User', 'isformatted', array('func' => 'modify'));
        $this->view->assign('formattedcontent', $formattedcontent);

        //lock the page so others cannot edit it
        if (ModUtil::available('PageLock')) {
            $returnUrl = ModUtil::url('News', 'admin', 'view');
            ModUtil::apiFunc('PageLock', 'user', 'pageLock',
                            array('lockName' => "Newsnews{$item['sid']}",
                                'returnUrl' => $returnUrl));
        }

        // Return the output that has been generated by this function
        return new Zikula_Response_Ajax(array('result' => $this->view->fetch('ajax/modify.tpl')));
    }

    /**
     * This is the Ajax function that is called with the results of the
     * form supplied by news_ajax_modify() to update a current item
     * The following parameters are received in an array 'story'!
     *
     * @param int 'sid' the id of the item to be updated
     * @param string 'title' the title of the news item
     * @param string 'urltitle' the title of the news item formatted for the url
     * @param string 'language' the language of the news item
     * @param string 'bodytext' the summary text of the news item
     * @param int 'bodytextcontenttype' the content type of the summary text
     * @param string 'extendedtext' the body text of the news item
     * @param int 'extendedtextcontenttype' the content type of the body text
     * @param string 'notes' any administrator notes
     * @param int 'published_status' the published status of the item
     * @param int 'hideonindex' hide the article on the index page
     * @param string 'action' the action to perform, either 'update', 'delete' or 'pending'
     * @author Mark West
     * @author Frank Schummertz
     * @return array(output, action) with output being a rendered template or a simple text and action the performed action
     */
    public function update()
    {
        $this->checkAjaxToken();
        $story = $this->request->getPost()->get('story');
        $action = $this->request->getPost()->get('action');
        $page = (int) $this->request->getPost()->get('page', 1);

        // Get the current news article
        $item = ModUtil::apiFunc('News', 'User', 'get', array('sid' => $story['sid']));
        if ($item == false || !$action) {
            throw new Zikula_Exception_NotFound($this->__('Error! No such article found.'));
        }

        $output = $action;
        $oldurltitle = $item['urltitle'];

        switch ($action)
        {
            case 'update':
                $this->throwForbiddenUnless(SecurityUtil::checkPermission('News::', $item['cr_uid'] . '::' . $item['sid'], ACCESS_EDIT), LogUtil::getErrorMsgPermission());
                // Update the story
                
                // TODO: See Admin Controller on usage of News_ImageUtil::
                // to accomplish the code that has been removed from here to
                // accomodate images

                if (ModUtil::apiFunc('News', 'admin', 'update',
                                array('sid' => $story['sid'],
                                    'title' => $story['title'],
                                    'urltitle' => $story['urltitle'],
                                    '__CATEGORIES__' => $story['__CATEGORIES__'],
                                    '__ATTRIBUTES__' => (isset($story['attributes'])) ? News_Util::reformatAttributes($story['attributes']) : null,
                                    'language' => isset($story['language']) ? $story['language'] : '',
                                    'hometext' => $story['hometext'],
                                    'hometextcontenttype' => $story['hometextcontenttype'],
                                    'bodytext' => $story['bodytext'],
                                    'bodytextcontenttype' => $story['bodytextcontenttype'],
                                    'notes' => $story['notes'],
                                    'hideonindex' => isset($story['hideonindex']) ? $story['hideonindex'] : 1,
                                    'disallowcomments' => isset($story['disallowcomments']) ? $story['disallowcomments'] : 0,
                                    'unlimited' => isset($story['unlimited']) ? $story['unlimited'] : null,
                                    'from' => isset($story['from']) ? $story['from'] : null,
                                    'tonolimit' => isset($story['tonolimit']) ? $story['tonolimit'] : null,
                                    'to' => isset($story['to']) ? $story['to'] : null,
                                    'weight' => $story['weight'],
                                    'pictures' => $story['pictures'],
                                    'published_status' => $story['published_status']))) {

                    // Success
                    // reload the news story and ignore the DBUtil SQLCache
                    $item = ModUtil::apiFunc('News', 'User', 'get', array('sid' => $story['sid'], 'SQLcache' => false));

                    if ($item == false) {
                        throw new Zikula_Exception_NotFound($this->__('Error! No such article found.'));
                    }

                    // Explode the news article into an array of seperate pages
                    $allpages = explode('<!--pagebreak-->', $item['bodytext']);

                    // Set the item hometext to be the required page
                    // no arrays start from zero, pages from one
                    $item['bodytext'] = $allpages[$page - 1];
                    $numitems = count($allpages);
                    unset($allpages);

                    // $info is array holding raw information.
                    $info = ModUtil::apiFunc('News', 'User', 'getArticleInfo', $item);

                    // $links is an array holding pure URLs to
                    // specific functions for this article.
                    $links = ModUtil::apiFunc('News', 'User', 'getArticleLinks', $info);

                    // $preformat is an array holding chunks of
                    // preformatted text for this article.
                    $preformat = ModUtil::apiFunc('News', 'User', 'getArticlePreformat',
                                    array('info' => $info,
                                        'links' => $links));

                    Zikula_AbstractController::configureView();
                    $this->view->setCaching(false);

                    // Assign the story info arrays
                    $this->view->assign(array('info' => $info,
                        'links' => $links,
                        'preformat' => $preformat,
                        'page' => $page));
                    // Some vars
                    $modvars = $this->getVars();
                    $this->view->assign('enablecategorization', $modvars['enablecategorization']);
                    $this->view->assign('catimagepath', $modvars['catimagepath']);
                    $this->view->assign('enableajaxedit', $modvars['enableajaxedit']);

                    // Now lets assign the information to create a pager for the review
                    $this->view->assign('pager', array('numitems' => $numitems,
                        'itemsperpage' => 1));

                    // we do not increment the read count!!!
                    // when urltitle has changed, do a reload with the full url and switch to no shorturl usage
                    if (strcmp($oldurltitle, $item['urltitle']) != 0) {
                        $reloadurl = ModUtil::url('News', 'user', 'display', array('sid' => $info['sid'], 'page' => $page), null, null, true, true);
                    } else {
                        $reloadurl = '';
                    }

                    // Return the output that has been generated by this function
                    $output = $this->view->fetch('user/articlecontent.tpl');
                } else {
                    $output = DataUtil::formatForDisplayHTML($this->__('Error! Could not save your changes.'));
                }
                break;

            case 'pending':
                // Security check
                $this->throwForbiddenUnless(SecurityUtil::checkPermission('News::', "{$item['cr_uid']}::{$story['sid']}", ACCESS_EDIT));

                // set published_status to 2 to make the story a pending story
                $object = array('published_status' => 2,
                    'sid' => $story['sid']);

                if (DBUtil::updateObject($object, 'news', '', 'sid') == false) {
                    $output = DataUtil::formatForDisplayHTML($this->__('Error! Could not save your changes.'));
                } else {
                    // Success
                    // the url for reloading, after setting to pending refer to the news index since this article is not visible any more
                    $reloadurl = ModUtil::url('News', 'user', 'view', array(), null, null, true);
                    $output = DataUtil::formatForDisplayHTML($this->__f('Done! Saved your changes.'));
                }
                break;

            case 'delete':
                // Security check inside of the API func
                if (ModUtil::apiFunc('News', 'Admin', 'delete', array('sid' => $story['sid']))) {
                    // Success
                    // the url for reloading, after deleting refer to the news index
                    $reloadurl = ModUtil::url('News', 'user', 'view', array(), null, null, true);
                    $output = DataUtil::formatForDisplayHTML($this->__f('Done! Deleted article.'));
                } else {
                    $output = DataUtil::formatForDisplayHTML($this->__('Error! Could not delete article.'));
                }
                break;

            default:
        }

        // release pagelock
        if (ModUtil::available('PageLock')) {
            ModUtil::apiFunc('PageLock', 'user', 'releaseLock',
                            array('lockName' => "Newsnews{$story['sid']}"));
        }

        return new Zikula_Response_Ajax(array('result' => $output,
            'action' => $action,
            'reloadurl' => $reloadurl));
    }

    /**
     * This is the Ajax function that is called with the results of the
     * form supplied by news_<user/admin>_new() to create a new draft item
     * The following parameters are received in an array 'story'!
     *
     * @param string 'title' the title of the news item
     *
     * @author Erik Spaan
     * @return array(output, etc) with output being a rendered template or a simple text and action the performed action
     */
    public function savedraft()
    {
        $this->checkAjaxToken();
        $title = $this->request->getPost()->get('title', null);
        $sid = $this->request->getPost()->get('sid', 0);
        $story = $this->request->getPost()->get('story', null);

        $output = $title;
        $slug = '';
        $fullpermalink = '';
        $showslugedit = false;
        // Permalink display length, only needed for 2 column layout later.
        //$permalinkmaxdisplay = 40;
        // Check  if the article is already saved as draft
        if ($sid > 0) {
            // Get the current news article
            $item = ModUtil::apiFunc('News', 'User', 'get', array('sid' => $sid));
            if ($item == false) {
                throw new Zikula_Exception_NotFound($this->__('Error! No such article found.'));
            }
            // Security check
            $this->throwForbiddenUnless(SecurityUtil::checkPermission('News::', "{$item['cr_uid']}::$sid", ACCESS_EDIT));

            if (!ModUtil::apiFunc('News', 'admin', 'update',
                            array('sid' => $sid,
                                'title' => $story['title'],
                                'urltitle' => $story['urltitle'],
                                '__CATEGORIES__' => $story['__CATEGORIES__'],
                                'language' => isset($story['language']) ? $story['language'] : '',
                                'hometext' => $story['hometext'],
                                'hometextcontenttype' => $story['hometextcontenttype'],
                                'bodytext' => $story['bodytext'],
                                'bodytextcontenttype' => $story['bodytextcontenttype'],
                                'notes' => $story['notes'],
                                'hideonindex' => isset($story['hideonindex']) ? $story['hideonindex'] : 1,
                                'disallowcomments' => isset($story['disallowcomments']) ? $story['disallowcomments'] : 0,
                                'unlimited' => isset($story['unlimited']) ? $story['unlimited'] : null,
                                'from' => $story['from'],
                                'tonolimit' => isset($story['tonolimit']) ? $story['tonolimit'] : null,
                                'to' => $story['to'],
                                'weight' => $story['weight'],
                                'pictures' => isset($story['pictures']) ? $story['pictures'] : 0))) {

                $output = DataUtil::formatForDisplayHTML($this->__('Error! Could not save your changes.'));
            } else {
                $output = $this->__f('Draft updated at %s', DateUtil::getDatetime_Time('', '%H:%M'));
                // Return the permalink (domain shortened) and the slug of the permalink
                $slug = $item['urltitle'];
                $fullpermalink = DataUtil::formatForDisplayHTML(ModUtil::url('News', 'user', 'display', array('sid' => $sid)));
                // limit the display length of the permalink
                //if (strlen($fullpermalink) > $permalinkmaxdisplay) {
                //    $fullpermalink = '...' . substr($fullpermalink, strlen($fullpermalink) - $permalinkmaxdisplay, $permalinkmaxdisplay);
                //}
                // Only show "edit the slug" if the shorturls are active
                $showslugedit = (System::getVar('shorturls') && System::getVar('shorturlstype') == 0);
            }
        } else {
            // Create a first draft version of the story
            $sid = ModUtil::apiFunc('News', 'User', 'create', array(
                        'title' => $title,
                        '__CATEGORIES__' => isset($story['__CATEGORIES__']) ? $story['__CATEGORIES__'] : null,
                        'language' => isset($story['language']) ? $story['language'] : '',
                        'hometext' => isset($story['hometext']) ? $story['hometext'] : '',
                        'hometextcontenttype' => isset($story['hometextcontenttype']) ? $story['hometextcontenttype'] : 0,
                        'bodytext' => isset($story['bodytext']) ? $story['bodytext'] : '',
                        'bodytextcontenttype' => isset($story['bodytextcontenttype']) ? $story['bodytextcontenttype'] : 0,
                        'notes' => isset($story['notes']) ? $story['notes'] : '',
                        'hideonindex' => isset($story['hideonindex']) ? $story['hideonindex'] : 1,
                        'disallowcomments' => isset($story['disallowcomments']) ? $story['disallowcomments'] : 0,
                        'unlimited' => isset($story['unlimited']) ? $story['unlimited'] : null,
                        'from' => isset($story['from']) ? $story['from'] : null,
                        'tonolimit' => isset($story['tonolimit']) ? $story['tonolimit'] : null,
                        'to' => isset($story['to']) ? $story['to'] : null,
                        'weight' => isset($story['weight']) ? $story['weight'] : 0,
                        'pictures' => isset($story['pictures']) ? $story['pictures'] : 0,
                        'published_status' => 4));
            if (!empty($sid)) {
                // Success and now reload the news story
                $item = ModUtil::apiFunc('News', 'User', 'get', array('sid' => $sid));
                if ($item == false) {
                    throw new Zikula_Exception_NotFound($this->__('Error! No such article found.'));
                } else {
                    // Return the Draft creation date
                    $output = $this->__f('Draft saved at %s', DateUtil::getDatetime_Time($item['cr_date'], '%H:%M'));
                    // Return the permalink (domain shortened) and the slug of the permalink
                    $slug = $item['urltitle'];
                    $fullpermalink = DataUtil::formatForDisplayHTML(ModUtil::url('News', 'user', 'display', array('sid' => $sid)));
                    // limit the display length of the permalink
                    //if (strlen($fullpermalink) > $permalinkmaxdisplay) {
                    //    $fullpermalink = '...' . substr($fullpermalink, strlen($fullpermalink) - $permalinkmaxdisplay, $permalinkmaxdisplay);
                    //}
                    // Only show "edit the slug" if the shorturls are active
                    $showslugedit = (System::getVar('shorturls') && System::getVar('shorturlstype') == 0);
                }
            } else {
                $output = DataUtil::formatForDisplayHTML($this->__('Error! Could not save your changes.'));
            }
        }
        //lock the page so others cannot edit it
        if (ModUtil::available('PageLock')) {
            $returnUrl = ModUtil::url('News', 'admin', 'view');
            ModUtil::apiFunc('PageLock', 'user', 'pageLock', array(
                'lockName' => "Newsnews{$sid}",
                'returnUrl' => $returnUrl));
        }

        return new Zikula_Response_Ajax(array(
            'result' => $output,
            'sid' => $sid,
            'slug' => $slug,
            'fullpermalink' => $fullpermalink,
            'showslugedit' => $showslugedit));
    }

    /**
     * make the permalink from the title
     *
     * @author Erik Spaan
     * @param 'title'   int the story id
     * @return string HTML string
     */
    public function updatepermalink()
    {
        $this->checkAjaxToken();
        $title = $this->request->getPost()->get('title', '');

        // define the lowercase permalink, using the title as slug, if not present
//    if (!isset($args['urltitle']) || empty($args['urltitle'])) {
//        $args['urltitle'] = strtolower(DataUtil::formatPermalink($args['title']));
//    }
        // Construct the lowercase permalink, using the title as slug
        $permalink = strtolower(DataUtil::formatPermalink($title));

        return new Zikula_Response_Ajax(array('result' => $permalink));
    }

    /**
     * check on the fly if the chosen picture upload directory is writable
     *
     * @author Erik Spaan
     * @param 'folder'   string: the folder name
     * @return boolean
     */
    function checkpicuploadfolder()
    {
        $this->checkAjaxToken();
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('News::', '::', ACCESS_DELETE));

        $folder = $this->request->getPost()->get('folder');
        $enabled = false;
        if (!empty($folder)) {
            if ($folder[0] == '/') {
                $output = '<img src="images/icons/extrasmall/cancel.png" width="16" height="16" />' . ' ' . DataUtil::formatForDisplayHTML($this->__("Specified path appears to be 'above' the DOCUMENT_ROOT. Please choose a path relative to the webserver (e.g. images/news_picupload)."));
            } else {
                if (is_dir($folder)) {
                    if (is_writable($folder)) {
                        $output = '<img src="images/icons/extrasmall/ok.png" width="16" height="16" />' . ' ' . DataUtil::formatForDisplayHTML($this->__f('Specified path [%s] does exist and is writable.', $folder));
                    } else {
                        $output = '<img src="images/icons/extrasmall/cancel.png" width="16" height="16" />' . ' ' . DataUtil::formatForDisplayHTML($this->__f('Specified path [%s] does exist, but is not writable. Make sure that this path is writable by the webserver.', $folder));
                    }
                } else {
                    $output = '<img src="images/icons/extrasmall/cancel.png" width="16" height="16" />' . ' ' . DataUtil::formatForDisplayHTML($this->__f('Specified path [%s] does not exist yet. News pubisher will create this path if you check the field below.', $folder));
                    $enabled = true;
                }
            }
        } else {
            $output = '<img src="images/icons/extrasmall/cancel.png" width="16" height="16" />' . ' ' . DataUtil::formatForDisplayHTML($this->__('Specified path is an empty string, please fill in a path (e.g. images/news_picupload).'));
        }
        $response = array(
            'result' => $output,
            'folder' => $folder,
            'enabled' => $enabled);
        return new Zikula_Response_Ajax($response);
    }

    /**
     * update the story author
     */
    function updateauthor()
    {
        $this->checkAjaxToken();
        $uid = $this->request->getPost()->get('uid');
        $sid = $this->request->getPost()->get('sid');
        $dest = $this->request->getPost()->get('dest', 'form');

        // Security check
        $this->throwForbiddenUnless(SecurityUtil::checkPermission('News::', "::", ACCESS_ADMIN));

        if (!isset($uid) || !isset($sid)) {
            return false;
        }
        $cont = UserUtil::getVar('uname', $uid);
        $obj = array('sid' => $sid,
            'cr_uid' => $uid,
            'contributor' => $cont);
        if ($res = DBUtil::updateObject($obj, 'news', '', 'sid')) {
            return new Zikula_Response_Ajax(array('uid' => $uid,
                'cont' => $cont,
                'dest' => $dest));
        } else {
            return false;
        }
    }

}