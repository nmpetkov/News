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

class News_Controller_User extends Zikula_Controller
{
    /**
     * the main user function
     *
     * @author Mark West
     * @return string HTML string
     */
    public function main()
    {
        $args = array(
                'hideonindex' => 0,
                'itemsperpage' => ModUtil::getVar('News', 'storyhome', 10)
        );
        return $this->view($args);
    }

    /**
     * add new item
     *
     * @author Mark West
     * @return string HTML string
     */
    public function newitem($args)
    {
        // Security check
        if (!SecurityUtil::checkPermission('News::', '::', ACCESS_COMMENT)) {
            return LogUtil::registerPermissionError();
        }

        // Any item set for preview will be stored in a session var
        // Once the new article is posted we'll clear the session var.
        $item = SessionUtil::getVar('newsitem');

        // Admin functions of this type can be called by other modules.
        extract($args);

        // get the type parameter so we can decide what template to use
        $type = FormUtil::getPassedValue('type', 'user', 'REQUEST');

        // Set the default values for the form. If not previewing an item prior
        // to submission these values will be null but do need to be set
        if (empty($item)) {
            $item = array();
            $item['__CATEGORIES__'] = array();
            $item['__ATTRIBUTES__'] = array();
            $item['title'] = '';
            $item['urltitle'] = '';
            $item['hometext'] = '';
            $item['hometextcontenttype'] = '';
            $item['bodytext'] = '';
            $item['bodytextcontenttype'] = '';
            $item['notes'] = '';
            $item['hideonindex'] = 1;
            $item['language'] = '';
            $item['disallowcomments'] = 1;
            $item['from'] = DateUtil::getDatetime(null, '%Y-%m-%d %H:%M');
            $item['to'] = DateUtil::getDatetime(null, '%Y-%m-%d %H:%M');
            $item['tonolimit'] = 1;
            $item['unlimited'] = 1;
            $item['weight'] = 0;
            $item['pictures'] = 0;
        }

        $preview = '';
        if (isset($item['action']) && $item['action'] == 0) {
            $preview = $this->preview(array('title' => $item['title'],
                    'hometext' => $item['hometext'],
                    'hometextcontenttype' => $item['hometextcontenttype'],
                    'bodytext' => $item['bodytext'],
                    'bodytextcontenttype' => $item['bodytextcontenttype'],
                    'notes' => $item['notes']));
        }

        // Create output object
        if (strtolower($type) == 'admin') {
            $this->view->setCaching(false);
        }

        // Get the module vars
        $modvars = $this->getVars();

        if ($modvars['enablecategorization']) {
            $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('News', 'news');
            $this->view->assign('catregistry', $catregistry);

            // add article attribute if morearticles is enabled and general setting is zero
            if ($modvars['enablemorearticlesincat'] && $modvars['morearticlesincat'] == 0) {
                $item['__ATTRIBUTES__']['morearticlesincat'] = 0;
            }
        }

        $this->view->assign($modvars);

        // Assign the default languagecode
        $this->view->assign('lang', ZLanguage::getLanguageCode());

        // Assign the item to the template
        $this->view->assign($item);

        // Assign the content format
        $formattedcontent = ModUtil::apiFunc('News', 'user', 'isformatted', array('func' => 'new'));
        $this->view->assign('formattedcontent', $formattedcontent);

        $this->view->assign('accessadd', 0);
        if (SecurityUtil::checkPermission('News::', '::', ACCESS_ADD)) {
            $this->view->assign('accessadd', 1);
            $this->view->assign('accesspicupload', 1);
            $this->view->assign('accesspubdetails', 1);
        } else {
            // if higher level access_add is not permitted, check for more specific permission rights
            if (SecurityUtil::checkPermission('News:pictureupload:', '::', ACCESS_ADD)) {
                $this->view->assign('accesspicupload', 1);
            } else {
                $this->view->assign('accesspubdetails', 0);
            }
            if (SecurityUtil::checkPermission('News:publicationdetails:', '::', ACCESS_ADD)) {
                $this->view->assign('accesspubdetails', 1);
            } else {
                $this->view->assign('accesspubdetails', 0);
            }
        }

        $this->view->assign('preview', $preview);

        // Return the output that has been generated by this function
        if (strtolower($type) == 'admin') {
            return $this->view->fetch('admin/create.tpl');
        } else {
            return $this->view->fetch('user/create.tpl');
        }
    }

    /**
     * This is a standard function that is called with the results of the
     * form supplied by News_admin_newitem() or News_user_newitem to create 
     * a new item.
     *
     * @author Mark West
     * @param string 'title' the title of the news item
     * @param string 'language' the language of the news item
     * @param string 'hometext' the summary text of the news item
     * @param int 'hometextcontenttype' the content type of the summary text
     * @param string 'bodytext' the body text of the news item
     * @param int 'bodytextcontenttype' the content type of the body text
     * @param string 'notes' any administrator notes
     * @param int 'published_status' the published status of the item
     * @param int 'hideonindex' hide the article on the index page
     * @return bool true
     */
    public function create($args)
    {
        // Get parameters from whatever input we need
        $story = FormUtil::getPassedValue('story', isset($args['story']) ? $args['story'] : null, 'POST');

        // Create the item array for processing
        $item = array('title' => $story['title'],
                'urltitle' => isset($story['urltitle']) ? $story['urltitle'] : '',
                '__CATEGORIES__' => isset($story['__CATEGORIES__']) ? $story['__CATEGORIES__'] : null,
                '__ATTRIBUTES__' => isset($story['attributes']) ? $story['attributes'] : null,
                'language' => isset($story['language']) ? $story['language'] : '',
                'hometext' => isset($story['hometext']) ? $story['hometext'] : '',
                'hometextcontenttype' => $story['hometextcontenttype'],
                'bodytext' => isset($story['bodytext']) ? $story['bodytext'] : '',
                'bodytextcontenttype' => $story['bodytextcontenttype'],
                'notes' => $story['notes'],
                'hideonindex' => isset($story['hideonindex']) ? $story['hideonindex'] : 0,
                'disallowcomments' => isset($story['disallowcomments']) ? $story['disallowcomments'] : 0,
                'from' => isset($story['from']) ? $story['from'] : null,
                'tonolimit' => isset($story['tonolimit']) ? $story['tonolimit'] : null,
                'to' => isset($story['to']) ? $story['to'] : null,
                'unlimited' => isset($story['unlimited']) && $story['unlimited'] ? true : false,
                'weight' => isset($story['weight']) ? $story['weight'] : 0,
                'action' => isset($story['action']) ? $story['action'] : 0);

        // Disable the non accessible fields for non editors
        if (!SecurityUtil::checkPermission('News::', '::', ACCESS_ADD)) {
            $item['notes'] = '';
            $item['hideonindex'] = 1;
            $item['disallowcomments'] = 1;
            $item['from'] = null;
            $item['tonolimit'] = true;
            $item['to'] = null;
            $item['unlimited'] = true;
            $item['weight'] = 0;
            if ($item['action'] > 1) {
                $item['action'] = 0;
            }
        }

        // Get the referer type for later use
        if (stristr(System::serverGetVar('HTTP_REFERER'), 'type=admin')) {
            $referertype = 'admin';
        } else {
            $referertype = 'user';
        }

        // Reformat the attributes array
        // from {0 => {name => '...', value => '...'}} to {name => value}
        if (isset($item['__ATTRIBUTES__'])) {
            $attributes = array();
            foreach ($item['__ATTRIBUTES__'] as $attr) {
                if (!empty($attr['name']) && !empty($attr['value'])) {
                    $attributes[$attr['name']] = $attr['value'];
                }
            }
            $item['__ATTRIBUTES__'] = $attributes;
        }

        // Validate the input
        $validationerror = false;
        if ($item['action'] != 0 && empty($item['title'])) {
            $validationerror = $this->__f('Error! You did not enter a %s.', $this->__('title'));
        }
        // both text fields can't be empty
        if ($item['action'] != 0 && empty($item['hometext']) && empty($item['bodytext'])) {
            $validationerror = $this->__f('Error! You did not enter the minimum necessary %s.', $this->__('article content'));
        }

        // if the user has selected to preview the article we then route them back
        // to the new function with the arguments passed here
        if ($item['action'] == 0 || $validationerror !== false) {
            // log the error found if any
            if ($validationerror !== false) {
                LogUtil::registerError($validationerror);
            }
            // back to the referer form
            SessionUtil::setVar('newsitem', $item);
            return System::redirect(ModUtil::url('News', $referertype, 'new'));

        } else {
            // Confirm authorisation code.
            if (!SecurityUtil::confirmAuthKey()) {
                return LogUtil::registerAuthidError(ModUtil::url('News', $referertype, 'view'));
            }

            // As we're not previewing the item let's remove it from the session
            SessionUtil::delVar('newsitem');
        }

        // get all module vars
        $modvars = $this->getVars();

        // count the attached pictures (credit msshams)
        if ($modvars['picupload_enabled']) {
            $sizedpics = array();
            $allowedExtensions = $modvars['picupload_allowext'];
            $allowedExtensionsArray = explode(',', $allowedExtensions);
            foreach ($_FILES['news_files']['error'] as $key => $error) {
                if ($error == UPLOAD_ERR_OK) {
                    if ($_FILES['news_files']['size'][$key] <= $modvars['picupload_maxfilesize']) {
                        $file_extension = FileUtil::getExtension($_FILES['news_files']['name'][$key]);
                        if (!in_array(strtolower($file_extension), $allowedExtensionsArray) && !in_array(strtoupper(($file_extension)), $allowedExtensionsArray)) {
                            LogUtil::registerStatus($this->__f('Picture %s is not uploaded, since the file extension is now allowed (only %s is allowed).', array($key+1, $modvars['picupload_allowext'])));
                        } else {
                            $sizedpics[] = $key;
                        }
                    } else {
                        LogUtil::registerStatus($this->__f('Picture %s is not uploaded, since the filesize was too large (max. %s kB).', array($key+1, $modvars['picupload_maxfilesize']/1000)));
                    }
                } else {
                    LogUtil::registerStatus($this->__f('Picture %s gave an error during uploading.', $key+1));
                }
            }
            $item['pictures'] = count($sizedpics);
        } else {
            $item['pictures'] = 0;
        }

        // Notable by its absence there is no security check here

        // Create the news story
        $sid = ModUtil::apiFunc('News', 'user', 'create', $item);

        if ($sid != false) {
            // Success
            LogUtil::registerStatus($this->__('Done! Created new article.'));

            // notify the configured addresses of a new Pending Review article
            $notifyonpending = ModUtil::getVar('News', 'notifyonpending', false);
            if ($notifyonpending && ($item['action'] == 1 || $item['action'] == 4)) {
                $sitename = System::getVar('sitename');
                $adminmail = System::getVar('adminmail');
                $fromname    = !empty($modvars['notifyonpending_fromname']) ? $modvars['notifyonpending_fromname'] : $sitename;
                $fromaddress = !empty($modvars['notifyonpending_fromaddress']) ? $modvars['notifyonpending_fromaddress'] : $adminmail;
                $toname    = !empty($modvars['notifyonpending_toname']) ? $modvars['notifyonpending_toname'] : $sitename;
                $toaddress = !empty($modvars['notifyonpending_toaddress']) ? $modvars['notifyonpending_toaddress'] : $adminmail;
                $subject     = $modvars['notifyonpending_subject'];
                $html        = $modvars['notifyonpending_html'];
                if (!UserUtil::isLoggedIn()) {
                    $contributor = System::getVar('anonymous');
                } else {
                    $contributor = UserUtil::getVar('uname');
                }
                if ($html) {
                    $body = $this->__f('<br />A News Publisher article <strong>%1$s</strong> has been submitted by %2$s for review on website %3$s.<br />Index page teaser text of the article:<br /><hr />%4$s<hr /><br /><br />Go to the <a href="%5$s">news publisher admin</a> pages to review and publish the <em>Pending Review</em> article(s).<br /><br />Regards,<br />%6$s', array($item['title'], $contributor, $sitename, $item['hometext'], ModUtil::url('News', 'admin', 'view', array('news_status' => 2), null, null, true), $sitename));
                } else {
                    $body = $this->__f('
A News Publisher article \'%1$s\' has been submitted by %2$s for review on website %3$s.
Index page teaser text of the article:
--------
%4$s
--------

Go to the <a href="%5$s">news publisher admin</a> pages to review and publish the \'Pending Review\' article(s).

Regards,
%6$s', array($item['title'], $contributor, $sitename, $item['hometext'], ModUtil::url('News', 'admin', 'view', array('news_status' => 2), null, null, true), $sitename));
                }
                $sent = ModUtil::apiFunc('Mailer', 'user', 'sendmessage', array('toname'     => $toname,
                        'toaddress'  => $toaddress,
                        'fromname'   => $fromname,
                        'fromaddress'=> $fromaddress,
                        'subject'    => $subject,
                        'body'       => $body,
                        'html'       => $html));
                if ($sent) {
                    LogUtil::registerStatus($this->__('Done! E-mail about new pending article is sent.'));
                } else {
                    LogUtil::registerStatus($this->__('Warning! E-mail about new pending article could not be sent.'));
                }
            }

            // Process the uploaded picture and copy to the upload directory (credit msshams)
            if ($modvars['picupload_enabled']) {
                // include the phpthumb library for thumbnail generation
                require_once ('modules/News/lib/vendor/phpthumb/ThumbLib.inc.php');
                $uploaddir = $modvars['picupload_uploaddir'] . '/';
                foreach ($sizedpics as $piccount => $key) {
                    $tmp_name = $_FILES['news_files']['tmp_name'][$key];
                    $name = $_FILES['news_files']['name'][$key];

                    $thumb = PhpThumbFactory::create($tmp_name);
                    if ($modvars['sizing'] == 0) {
                        $thumb->Resize($modvars['picupload_picmaxwidth'],$modvars['picupload_picmaxheight']);
                    } else {
                        $thumb->adaptiveResize($modvars['picupload_picmaxwidth'],$modvars['picupload_picmaxheight']);
                    }
                    $thumb->save($uploaddir.'pic_sid'.$sid.'-'.$piccount.'-norm.png', 'png');

                    $thumb1 = PhpThumbFactory::create($tmp_name);
                    if ($modvars['sizing'] == 0) {
                        $thumb1->Resize($modvars['picupload_thumbmaxwidth'],$modvars['picupload_thumbmaxheight']);
                    } else {
                        $thumb1->adaptiveResize($modvars['picupload_thumbmaxwidth'],$modvars['picupload_thumbmaxheight']);
                    }
                    $thumb1->save($uploaddir.'pic_sid'.$sid.'-'.$piccount.'-thumb.png', 'png');

                    // for index page picture create an extra thumbnail
                    if ($piccount==0){
                        $thumb2 = PhpThumbFactory::create($tmp_name);
                        if ($modvars['sizing'] == 0) {
                            $thumb2->Resize($modvars['picupload_thumb2maxwidth'],$modvars['picupload_thumb2maxheight']);
                        } else {
                            $thumb2->adaptiveResize($modvars['picupload_thumb2maxwidth'],$modvars['picupload_thumb2maxheight']);
                        }
                        $thumb2->save($uploaddir.'pic_sid'.$sid.'-'.$piccount.'-thumb2.png', 'png');
                    }
                }
                LogUtil::registerStatus($this->_fn('%s out of %s picture was uploaded and resized.', '%s out of %s pictures were uploaded and resized.', $sizedpictures, array(count($sizedpics), count($_FILES['news_files']['error']))));
            }
        }
        return System::redirect(ModUtil::url('News', $referertype, 'view'));
    }

    /**
     * view items
     *
     * @author Mark West
     * @param 'page' starting number for paged view
     * @return string HTML string
     */
    public function view($args = array())
    {
        // Security check
        if (!SecurityUtil::checkPermission('News::', '::', ACCESS_OVERVIEW)) {
            return LogUtil::registerPermissionError();
        }

        // clean the session preview data
        SessionUtil::delVar('newsitem');

        // get all module vars for later use
        $modvars = $this->getVars();

        // Get parameters from whatever input we need
        $page         = isset($args['page']) ? $args['page'] : (int)FormUtil::getPassedValue('page', 1, 'GET');
        $prop         = isset($args['prop']) ? $args['prop'] : (string)FormUtil::getPassedValue('prop', null, 'GET');
        $cat          = isset($args['cat']) ? $args['cat'] : (string)FormUtil::getPassedValue('cat', null, 'GET');
        $itemsperpage = isset($args['itemsperpage']) ? $args['itemsperpage'] : (int)FormUtil::getPassedValue('itemsperpage', $modvars['itemsperpage'], 'GET');

        // work out page size from page number
        $startnum = (($page - 1) * $itemsperpage) + 1;

        // default hideonindex argument
        $args['hideonindex'] = isset($args['hideonindex']) ? (int)$args['hideonindex'] : null;

        $lang = ZLanguage::getLanguageCode();

        // check if categorization is enabled
        if ($modvars['enablecategorization']) {
            // get the categories registered for News
            $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('News', 'news');
            $properties = array_keys($catregistry);

            // validate the property
            // and build the category filter - mateo
            if (!empty($prop) && in_array($prop, $properties) && !empty($cat)) {
                if (!is_numeric($cat)) {
                    $rootCat = CategoryUtil::getCategoryByID($catregistry[$prop]);
                    $cat = CategoryUtil::getCategoryByPath($rootCat['path'].'/'.$cat);
                } else {
                    $cat = CategoryUtil::getCategoryByID($cat);
                }
                $catname = isset($cat['display_name'][$lang]) ? $cat['display_name'][$lang] : $cat['name'];

                if (!empty($cat) && isset($cat['path'])) {
                    // include all it's subcategories and build the filter
                    $categories = CategoryUtil::getCategoriesByPath($cat['path'], '', 'path');
                    $catstofilter = array();
                    foreach ($categories as $category) {
                        $catstofilter[] = $category['id'];
                    }
                    $catFilter = array($prop => $catstofilter);
                } else {
                    LogUtil::registerError($this->__('Error! Invalid category passed.'));
                }
            }
        }

        // get matching news articles
        $items = ModUtil::apiFunc('News', 'user', 'getall',
                array('startnum'     => $startnum,
                'numitems'     => $itemsperpage,
                'status'       => 0,
                'hideonindex'  => $args['hideonindex'],
                'filterbydate' => true,
                'category'     => isset($catFilter) ? $catFilter : null,
                'catregistry'  => isset($catregistry) ? $catregistry : null));

        if ($items == false) {
            if ($modvars['enablecategorization'] && isset($catFilter)) {
                LogUtil::registerStatus($this->__f('No articles currently published under the \'%s\' category.', $catname));
            } else {
                LogUtil::registerStatus($this->__('No articles currently published.'));
            }
        }

        // assign various useful template variables
        $this->view->assign('startnum', $startnum);
        $this->view->assign('lang', $lang);
        $this->view->assign($modvars);
        $this->view->assign('shorturls', System::getVar('shorturls'));
        $this->view->assign('shorturlstype', System::getVar('shorturlstype'));

        // assign the root category
        $this->view->assign('category', $cat);
        $this->view->assign('catname', isset($catname) ? $catname : '');

        $newsitems = array();
        // Loop through each item and display it
        foreach ($items as $item)
        {
            // display if it's published and the hideonindex match (if set)
            if (($item['published_status'] == 0) &&
                    (!isset($args['hideonindex']) || $item['hideonindex'] == $args['hideonindex'])) {

                // $info is array holding raw information.
                // Used below and also passed to the theme - jgm
                $info = ModUtil::apiFunc('News', 'user', 'getArticleInfo', $item);

                // $links is an array holding pure URLs to
                // specific functions for this article.
                // Used below and also passed to the theme - jgm
                $links = ModUtil::apiFunc('News', 'user', 'getArticleLinks', $info);

                // $preformat is an array holding chunks of
                // preformatted text for this article.
                // Used below and also passed to the theme - jgm
                $preformat = ModUtil::apiFunc('News', 'user', 'getArticlePreformat',
                        array('info' => $info,
                        'links' => $links));

                $this->view->assign(array('info' => $info,
                        'links' => $links,
                        'preformat' => $preformat));

                $newsitems[] = $this->view->fetch('user/index.tpl', $item['sid']);
            }
        }

        // The items that are displayed on this overview page depend on the individual
        // user permissions. Therefor, we can not cache the whole page.
        // The single entries are cached, though.
        $this->view->setCaching(false);

        // Display the entries
        $this->view->assign('newsitems', $newsitems);

        // Assign the values for the smarty plugin to produce a pager
        $this->view->assign('pager', array('numitems' => ModUtil::apiFunc('News', 'user', 'countitems',
                array('status' => 0,
                'filterbydate' => true,
                'hideonindex' => isset($args['hideonindex']) ? $args['hideonindex'] : null,
                'category' => isset($catFilter) ? $catFilter : null)),
                'itemsperpage' => $itemsperpage));

        // Return the output that has been generated by this function
        return $this->view->fetch('user/view.tpl');
    }

    /**
     * display item
     *
     * @author Mark West
     * @param 'sid' The article ID
     * @param 'objectid' generic object id maps to sid if present
     * @return string HTML string
     */
    public function display($args)
    {
        // Get parameters from whatever input we need
        $sid       = (int)FormUtil::getPassedValue('sid', null, 'REQUEST');
        $objectid  = (int)FormUtil::getPassedValue('objectid', null, 'REQUEST');
        $page      = (int)FormUtil::getPassedValue('page', 1, 'REQUEST');
        $title     = FormUtil::getPassedValue('title', null, 'REQUEST');
        $year      = FormUtil::getPassedValue('year', null, 'REQUEST');
        $monthnum  = FormUtil::getPassedValue('monthnum', null, 'REQUEST');
        $monthname = FormUtil::getPassedValue('monthname', null, 'REQUEST');
        $day       = FormUtil::getPassedValue('day', null, 'REQUEST');

        // User functions of this type can be called by other modules
        extract($args);

        // At this stage we check to see if we have been passed $objectid, the
        // generic item identifier
        if ($objectid) {
            $sid = $objectid;
        }

        // Validate the essential parameters
        if ((empty($sid) || !is_numeric($sid)) && (empty($title))) {
            return LogUtil::registerArgsError();
        }
        if (!empty($title)) {
            unset($sid);
        }

        // Set the default page number
        if ($page < 1 || !is_numeric($page)) {
            $page = 1;
        }

        // increment the read count
        if ($page == 1) {
            if (isset($sid)) {
                ModUtil::apiFunc('News', 'user', 'incrementreadcount', array('sid' => $sid));
            } else {
                ModUtil::apiFunc('News', 'user', 'incrementreadcount', array('title' => $title));
            }
        }

        // For caching reasons you must pass a cache ID
        if (isset($sid)) {
            $this->view->cache_id = $sid.$page;
        } else {
            $this->view->cache_id = $title.$page;
        }

        // check out if the contents is cached.
        if ($this->view->is_cached('user/article.tpl')) {
            return $this->view->fetch('user/article.tpl');
        }

        // Get the news story
        if (!SecurityUtil::checkPermission('News::', "::", ACCESS_ADD)) {
            if (isset($sid)) {
                $item = ModUtil::apiFunc('News', 'user', 'get',
                        array('sid'       => $sid,
                        'status'    => 0));
            } else {
                $item = ModUtil::apiFunc('News', 'user', 'get',
                        array('title'     => $title,
                        'year'      => $year,
                        'monthname' => $monthname,
                        'monthnum'  => $monthnum,
                        'day'       => $day,
                        'status'    => 0));
                $sid = $item['sid'];
                System::queryStringSetVar('sid', $sid);
            }
        } else {
            if (isset($sid)) {
                $item = ModUtil::apiFunc('News', 'user', 'get',
                        array('sid'       => $sid));
            } else {
                $item = ModUtil::apiFunc('News', 'user', 'get',
                        array('title'     => $title,
                        'year'      => $year,
                        'monthname' => $monthname,
                        'monthnum'  => $monthnum,
                        'day'       => $day));
                $sid = $item['sid'];
                System::queryStringSetVar('sid', $sid);
            }
        }

        if ($item === false) {
            return LogUtil::registerError($this->__('Error! No such article found.'), 404);
        }

        // Explode the review into an array of seperate pages
        $allpages = explode('<!--pagebreak-->', $item['bodytext']);

        // Set the item bodytext to be the required page
        // nb arrays start from zero, pages from one
        $item['bodytext'] = $allpages[$page-1];
        $numpages = count($allpages);
        unset($allpages);

        // If the pagecount is greater than 1 and we're not on the frontpage
        // don't show the hometext
        if ($numpages > 1  && $page > 1) {
            $item['hometext'] = '';
        }

        // $info is array holding raw information.
        // Used below and also passed to the theme - jgm
        $info = ModUtil::apiFunc('News', 'user', 'getArticleInfo', $item);

        // $links is an array holding pure URLs to specific functions for this article.
        // Used below and also passed to the theme - jgm
        $links = ModUtil::apiFunc('News', 'user', 'getArticleLinks', $info);

        // $preformat is an array holding chunks of preformatted text for this article.
        // Used below and also passed to the theme - jgm
        $preformat = ModUtil::apiFunc('News', 'user', 'getArticlePreformat',
                array('info'  => $info,
                'links' => $links));

        // set the page title
        if ($numpages <= 1) {
            PageUtil::setVar('title', $info['title']);
        } else {
            PageUtil::setVar('title', $info['title'] . $this->__f(' :: page %s', $page));
        }

        // Assign the story info arrays
        $this->view->assign(array('info'      => $info,
                'links'     => $links,
                'preformat' => $preformat,
                'page'      => $page));

        $modvars = $this->getVars();
        $this->view->assign($modvars);
        $this->view->assign('lang', ZLanguage::getLanguageCode());

        // get more articletitles in the categories of this article
        if ($modvars['enablecategorization'] && $modvars['enablemorearticlesincat']) {
            // check how many articles to display
            if ($modvars['morearticlesincat'] > 0) {
                $morearticlesincat = $modvars['morearticlesincat'];
            } elseif ($modvars['morearticlesincat'] == 0 && array_key_exists('morearticlesincat', $info['attributes'])) {
                $morearticlesincat = $info['attributes']['morearticlesincat'];
            } else {
                $morearticlesincat = 0;
            }
            if ($morearticlesincat > 0) {
                // get the categories registered for News
                $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('News', 'news');
                foreach (array_keys($catregistry) as $property) {
                    $catFilter[$property] = $info['categories'][$property]['id'];
                }
                // get matching news articles
                // TODO exclude current article, query does not work yet :-(
                $morearticlesincat = ModUtil::apiFunc('News', 'user', 'getall',
                        array('numitems'     => $morearticlesincat,
                        'status'       => 0,
                        'filterbydate' => true,
                        'category'     => $catFilter,
                        'catregistry'  => $catregistry,
                        'query'        => array('sid', '!=', $sid)));
                $this->view->assign('morearticlesincat', $morearticlesincat);
            }
        }

        // Now lets assign the informatation to create a pager for the review
        $this->view->assign('pager', array('numitems'     => $numpages,
                'itemsperpage' => 1));

        // Return the output that has been generated by this function
        return $this->view->fetch('user/article.tpl');
    }

    /**
     * display article archives
     *
     * @author Andreas Krapohl
     * @author Mark West
     * @return string HTML string
     */
    public function archives($args)
    {
        // Get parameters from whatever input we need
        $year  = (int)FormUtil::getPassedValue('year', null, 'REQUEST');
        $month = (int)FormUtil::getPassedValue('month', null, 'REQUEST');
        $day   = '31';

        // Security check
        if (!SecurityUtil::checkPermission('News::', '::', ACCESS_OVERVIEW)) {
            return LogUtil::registerPermissionError();
        }

        // Dates validation
        $currentdate = explode(',', DateUtil::getDatetime('', '%Y,%m,%d'));
        if (!empty($year) || !empty($month)) {
            if ((empty($year) || empty($month)) ||
                    ($year > (int)$currentdate[0] || ($year == (int)$currentdate[0] && $month > (int)$currentdate[1]))) {
                System::redirect(ModUtil::url('News', 'user', 'archives'));
            } elseif ($year == (int)$currentdate[0] && $month == (int)$currentdate[1]) {
                $day = (int)$currentdate[2];
            }
        }

        // Load localized month names
        $monthnames = explode(' ', $this->__('January February March April May June July August September October November December'));

        // Create output object
        $cacheid = "$month|$year";
        $this->view = Zikula_View::getInstance('News', null, $cacheid);

        // output vars
        $archivemonths = array();
        $archiveyears = array();

        if (!empty($year) && !empty($month)) {
            $items = ModUtil::apiFunc('News', 'user', 'getall',
                    array('order'  => 'from',
                    'from'   => "$year-$month-01 00:00:00",
                    'to'     => "$year-$month-$day 23:59:59",
                    'status' => 0));
            $this->view->assign('year', $year);
            $this->view->assign('month', $monthnames[$month-1]);

        } else {
            // get all matching news articles
            $monthsyears = ModUtil::apiFunc('News', 'user', 'getMonthsWithNews');

            foreach ($monthsyears as $monthyear) {
                $month = DateUtil::getDatetime_Field($monthyear, 2);
                $year  = DateUtil::getDatetime_Field($monthyear, 1);
                $dates[$year][] = $month;
            }
            foreach ($dates as $year => $years) {
                foreach ($years as $month)
                {
                    //$linktext = $monthnames[$month-1]." $year";
                    $linktext = $monthnames[$month-1];
                    $nrofarticles = ModUtil::apiFunc('News', 'user', 'countitems',
                            array('from'   => "$year-$month-01 00:00:00",
                            'to'     => "$year-$month-$day 23:59:59",
                            'status' => 0));

                    $archivemonths[$year][$month] = array('url'          => ModUtil::url('News', 'user', 'archives', array('month' => $month, 'year' => $year)),
                            'title'        => $linktext,
                            'nrofarticles' => $nrofarticles);
                }
            }
            $items = false;
        }

        $this->view->assign('archivemonths', $archivemonths);
        $this->view->assign('archiveitems', $items);
        $this->view->assign('enablecategorization', ModUtil::getVar('News', 'enablecategorization'));

        // Return the output that has been generated by this function
        return $this->view->fetch('user/archives.tpl');
    }

    /**
     * preview article
     *
     * @author Mark West
     * @return string HTML string
     */
    public function preview($args)
    {
        // Get parameters from whatever input we need
        $title               = FormUtil::getPassedValue('title', null, 'REQUEST');
        $hometext            = FormUtil::getPassedValue('hometext', null, 'REQUEST');
        $hometextcontenttype = FormUtil::getPassedValue('hometextcontenttype', null, 'REQUEST');
        $bodytext            = FormUtil::getPassedValue('bodytext', null, 'REQUEST');
        $bodytextcontenttype = FormUtil::getPassedValue('bodytextcontenttype', null, 'REQUEST');
        $notes               = FormUtil::getPassedValue('notes', null, 'REQUEST');

        // User functions of this type can be called by other modules
        extract($args);

        // format the contents if needed
        if ($hometextcontenttype == 0) {
            $hometext = nl2br($hometext);
        }
        if ($bodytextcontenttype == 0) {
            $bodytext = nl2br($bodytext);
        }

        $this->view = Zikula_View::getInstance('News', false);

        $this->view->assign('preview', array('title'    => $title,
                'hometext' => $hometext,
                'bodytext' => $bodytext,
                'notes'    => $notes));

        return $this->view->fetch('user/preview.tpl');
    }

    /**
     * display available categories in News
     *
     * @author Erik Spaan [espaan]
     * @return string HTML string
     */
    public function categorylist($args)
    {
        // Security check
        if (!SecurityUtil::checkPermission('News::', '::', ACCESS_OVERVIEW)) {
            return LogUtil::registerPermissionError();
        }

        $enablecategorization = ModUtil::getVar('News', 'enablecategorization');
        if (UserUtil::isLoggedIn()) {
            $uid = SessionUtil::getVar('uid');
        } else {
            $uid = 0;
        }

        if ($enablecategorization) {
            // Get the categories registered for News
            $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('News', 'news');
            $properties  = array_keys($catregistry);
            $propertiesdata = array();
            foreach ($properties as $property)
            {
                $rootcat = CategoryUtil::getCategoryByID($catregistry[$property]);
                if (!empty($rootcat)) {
                    $rootcat['path'] .= '/';
                    // Get all categories in this category property
                    $catcount = $this->_countcategories($rootcat, $property, $catregistry, $uid);
                    $rootcat['news_articlecount'] = $catcount['category']['news_articlecount'];
                    $rootcat['news_totalarticlecount'] = $catcount['category']['news_totalarticlecount'];
                    $rootcat['news_yourarticlecount'] = $catcount['category']['news_yourarticlecount'];
                    $rootcat['subcategories'] = $catcount['subcategories'];
                    // Store data per property for listing in the overview
                    $propertiesdata[] = array('name'     => $property,
                            'category' => $rootcat);
                }
            }
            // Assign property & category related vars
            $this->view->assign('propertiesdata', $propertiesdata);
        }

        // Assign the config vars
        $this->view->assign('enablecategorization', $enablecategorization);
        $this->view->assign('shorturls', System::getVar('shorturls'));
        $this->view->assign('shorturlstype', System::getVar('shorturlstype'));
        $this->view->assign('lang', ZLanguage::getLanguageCode());
        $this->view->assign('catimagepath', ModUtil::getVar('News', 'catimagepath'));

        // Return the output that has been generated by this function
        return $this->view->fetch('user/categorylist.tpl');
    }

    /**
     * display article as pdf
     *
     * @author Erik Spaan
     * @param 'sid' The article ID
     * @param 'objectid' generic object id maps to sid if present
     * @return string HTML string
     */
    public function displaypdf($args)
    {
        // Get parameters from whatever input we need
        $sid       = (int)FormUtil::getPassedValue('sid', null, 'REQUEST');
        $objectid  = (int)FormUtil::getPassedValue('objectid', null, 'REQUEST');
        $title     = FormUtil::getPassedValue('title', null, 'REQUEST');
        $year      = FormUtil::getPassedValue('year', null, 'REQUEST');
        $monthnum  = FormUtil::getPassedValue('monthnum', null, 'REQUEST');
        $monthname = FormUtil::getPassedValue('monthname', null, 'REQUEST');
        $day       = FormUtil::getPassedValue('day', null, 'REQUEST');

        // User functions of this type can be called by other modules
        extract($args);

        // get all module vars for later use
        $modvars = $this->getVars();

        // At this stage we check to see if we have been passed $objectid, the
        // generic item identifier
        if ($objectid) {
            $sid = $objectid;
        }

        // Validate the essential parameters
        if ((empty($sid) || !is_numeric($sid)) && (empty($title))) {
            return LogUtil::registerArgsError();
        }
        if (!empty($title)) {
            unset($sid);
        }

        // Include the TCPDF class from the configured path
        include_once $modvars['pdflink_tcpdfpath'];
        include_once $modvars['pdflink_tcpdflang'];

        // Get the news story
        if (isset($sid)) {
            $item = ModUtil::apiFunc('News', 'user', 'get',
                    array('sid'       => $sid,
                    'status'    => 0));
        } else {
            $item = ModUtil::apiFunc('News', 'user', 'get',
                    array('title'     => $title,
                    'year'      => $year,
                    'monthname' => $monthname,
                    'monthnum'  => $monthnum,
                    'day'       => $day,
                    'status'    => 0));
            $sid = $item['sid'];
            System::queryStringSetVar('sid', $sid);
        }
        if ($item === false) {
            return LogUtil::registerError($this->__('Error! No such article found.'), 404);
        }

        // Explode the review into an array of seperate pages
        $allpages = explode('<!--pagebreak-->', $item['bodytext']);

        // Set the item hometext to be the required page
        // nb arrays start from zero, pages from one
        //$item['bodytext'] = $allpages[$page-1];
        $numpages = count($allpages);
        //unset($allpages);

        // $info is array holding raw information.
        $info = ModUtil::apiFunc('News', 'user', 'getArticleInfo', $item);

        // $links is an array holding pure URLs to specific functions for this article.
        $links = ModUtil::apiFunc('News', 'user', 'getArticleLinks', $info);

        // $preformat is an array holding chunks of preformatted text for this article.
        $preformat = ModUtil::apiFunc('News', 'user', 'getArticlePreformat',
                array('info'  => $info,
                'links' => $links));

        // Assign the story info arrays
        $this->view->assign(array('info'      => $info,
                'links'     => $links,
                'preformat' => $preformat));

        $this->view->assign('enablecategorization', $modvars['enablecategorization']);
        $this->view->assign('catimagepath', $modvars['catimagepath']);
        $this->view->assign('pdflink', $modvars['pdflink']);

        // Store output in variable
        $articlehtml = $this->view->fetch('user/articlepdf.tpl');

        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set pdf document information
        $pdf->SetCreator(System::getVar('sitename'));
        $pdf->SetAuthor($info['contributor']);
        $pdf->SetTitle($info['title']);
        $pdf->SetSubject($info['cattitle']);
        //$pdf->SetKeywords($info['cattitle']);

        // set default header data
        //$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
        $sitename = System::getVar('sitename');
        /*    $pdf->SetHeaderData(
                $modvars['pdflink_headerlogo'],
                $modvars['pdflink_headerlogo_width'],
                $this->__f('Article %1$s by %2$s', array($info['title'], $info['contributor'])),
                $sitename . ' :: ' . $this->__('News publisher'));*/
        $pdf->SetHeaderData(
                $modvars['pdflink_headerlogo'],
                $modvars['pdflink_headerlogo_width'],
                '',
                $sitename . ' :: ' . $info['cattitle']. ' :: ' . $info['topicname']);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        //set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        //set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        //set some language-dependent strings
        $pdf->setLanguageArray($l);

        // set font, freeserif is big !
        //$pdf->SetFont('freeserif', '', 10);
        // For Unicode data put dejavusans in tcpdf_config.php
        $pdf->SetFont(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN);

        // add a page
        $pdf->AddPage();

        // output the HTML content
        $pdf->writeHTML($articlehtml, true, 0, true, 0);

        // reset pointer to the last page
        $pdf->lastPage();

        //Close and output PDF document
        $pdf->Output($info['urltitle'].'.pdf', 'I');

        // Since the output doesn't need the theme wrapped around it,
        // let the theme know that the function is already finished
        return true;
    }

    /**
     * Internal function to count categories including subcategories
     *
     * @author Erik Spaan [espaan]
     * @return array
     */
    private function _countcategories($category, $property, $catregistry, $uid)
    {
        // Get the number of articles in this category within this category property
        $news_articlecount = ModUtil::apiFunc('News', 'user', 'countitems',
                array('status'       => 0,
                'filterbydate' => true,
                'category'     => array($property => $category['id']),
                'catregistry'  => $catregistry));

        $news_totalarticlecount = $news_articlecount;

        // Get the number of articles by the current uid in this category within this category property
        if ($uid > 0) {
            $news_yourarticlecount = ModUtil::apiFunc('News', 'user', 'countitems',
                    array('status'       => 0,
                    'filterbydate' => true,
                    'uid'          => $uid,
                    'category'     => array($property => $category['id']),
                    'catregistry'  => $catregistry));
        } else {
            $news_yourarticlecount = 0;
        }

        // Check if this category is a leaf/endnode
        $subcats = CategoryUtil::getCategoriesByParentID($category['id']);
        if (!$category['is_leaf'] && !empty($subcats)) {
            $subcategories = array();
            foreach ($subcats as $cat) {
                $count = $this->_countcategories($cat, $property, $catregistry, $uid);
                // Add the subcategories count to this category
                $news_totalarticlecount += $count['category']['news_totalarticlecount'];
                $news_yourarticlecount  += $count['category']['news_yourarticlecount'];
                $subcategories[] = $count;
            }
        } else {
            $subcategories = null;
        }

        $category['news_articlecount'] = $news_articlecount;
        $category['news_totalarticlecount'] = $news_totalarticlecount;
        $category['news_yourarticlecount'] = $news_yourarticlecount;
        // if a category image is available, store it for easy reuse
        if (isset($category['__ATTRIBUTES__']) && isset($category['__ATTRIBUTES__']['topic_image'])) {
            $category['catimage'] = $category['__ATTRIBUTES__']['topic_image'];
        }

        $return = array('category'      => $category,
                'subcategories' => $subcategories);

        return $return;
    }
}