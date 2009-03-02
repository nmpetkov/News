<?php
/**
 * Zikula Application Framework
 *
 * @copyright  (c) Zikula Development Team
 * @link       http://www.zikula.org
 * @version    $Id: pnuser.php 79 2009-02-25 17:41:17Z espaan $
 * @license    GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author     Mark West <mark@zikula.org>
 * @category   Zikula_3rdParty_Modules
 * @package    Content_Management
 * @subpackage News
 */

/**
 * the main user function
 *
 * @author Mark West
 * @return string HTML string
 */
function News_user_main()
{
    $args = array(
        'ihome' => 0,
        'itemsperpage' => pnModGetVar('News', 'storyhome', 10)
    );
    return News_user_view($args);
}

/**
 * add new item
 * This is a standard function that is called whenever an administrator
 * wishes to create a new module item
 * @author Mark West
 * @return string HTML string
 */
function News_user_new($args)
{
    // Security check
    if (!SecurityUtil::checkPermission('Stories::Story', '::', ACCESS_COMMENT)) {
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
        $item['ihome'] = 1;
        $item['language'] = '';
        $item['from'] = time();
        $item['to'] = time();
        $item['tonolimit'] = 1;
        $item['unlimited'] = 1;
        $item['published_status'] = 0;
    }

    $preview = '';
    if (isset($item['preview']) && $item['preview']) {
        $preview = News_user_preview(array('title' => $item['title'],
                                           'hometext' => $item['hometext'],
                                           'hometextcontenttype' => $item['hometextcontenttype'],
                                           'bodytext' => $item['bodytext'],
                                           'bodytextcontenttype' => $item['bodytextcontenttype'],
                                           'notes' => $item['notes']));
    }

    // Create output object
    if (strtolower($type) == 'admin') {
        $pnRender = pnRender::getInstance('News', false);
    } else {
        $pnRender = pnRender::getInstance('News');
    }

    // Get the module vars
    $modvars = pnModGetVar('News');

    if ($modvars['enablecategorization']) {
        // load the categories system
        if (!($class = Loader::loadClass('CategoryRegistryUtil'))) {
            pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'CategoryRegistryUtil')));
        }
        $catregistry  = CategoryRegistryUtil::getRegisteredModuleCategories ('News', 'stories');

        $pnRender->assign('catregistry', $catregistry);
    }

    $pnRender->assign($modvars);

    // Assign the default language
    $pnRender->assign('language', pnUserGetLang());

    // Assign the item to the template
    $pnRender->assign($item);

    // Assign the content format
    $formattedcontent = pnModAPIFunc('News', 'user', 'isformatted', array('func' => 'new'));
    $pnRender->assign('formattedcontent', $formattedcontent);

    $pnRender->assign('accessadd', 0);
    if (SecurityUtil::checkPermission('Stories::Story', '::', ACCESS_ADD)) {
        $pnRender->assign('accessadd', 1);
    }

    $pnRender->assign('preview', $preview);

    // Return the output that has been generated by this function
    if (strtolower($type) == 'admin') {
        return $pnRender->fetch('news_admin_new.htm');
    } else {
        return $pnRender->fetch('news_user_new.htm');
    }
}

/**
 * This is a standard function that is called with the results of the
 * form supplied by News_admin_new() to create a new item
 * @author Mark West
 * @param string 'title' the title of the news item
 * @param string 'language' the language of the news item
 * @param string 'hometext' the summary text of the news item
 * @param int 'hometextcontenttype' the content type of the summary text
 * @param string 'bodytext' the body text of the news item
 * @param int 'bodytextcontenttype' the content type of the body text
 * @param string 'notes' any administrator notes
 * @param int 'published_status' the published status of the item
 * @param int 'ihome' publish the article in the homepage
 * @return bool true
 */
function News_user_create($args)
{
    // Get parameters from whatever input we need
    $story = FormUtil::getPassedValue('story', isset($args['story']) ? $args['story'] : null, 'POST');

    // Create the item array for processing
    $item = array('preview' => $story['preview'],
                  'title' => $story['title'],
                  'urltitle' => isset($story['urltitle']) ? $story['urltitle'] : '',
                  '__CATEGORIES__' => isset($story['__CATEGORIES__']) ? $story['__CATEGORIES__'] : null,
                  '__ATTRIBUTES__' => isset($story['attributes']) ? $story['attributes'] : null,
                  'language' => isset($story['language']) ? $story['language'] : '',
                  'hometext' => $story['hometext'],
                  'hometextcontenttype' => $story['hometextcontenttype'],
                  'bodytext' => $story['bodytext'],
                  'bodytextcontenttype' => $story['bodytextcontenttype'],
                  'notes' => $story['notes'],
                  'ihome' => isset($story['ihome']) ? $story['ihome'] : 0,
                  'from' => mktime($story['fromHour'], $story['fromMinute'], 0, $story['fromMonth'], $story['fromDay'], $story['fromYear']),
                  'tonolimit' => $story['tonolimit'],
                  'to' => mktime($story['toHour'], $story['toMinute'], 0, $story['toMonth'], $story['toDay'], $story['toYear']),
                  'unlimited' => isset($story['unlimited']) && $story['unlimited'] ? true : false,
                  'published_status' => isset($story['published_status']) ? $story['published_status'] : null);

    // Get the referer type for later use
    if (stristr(pnServerGetVar('HTTP_REFERER'), 'type=admin')) {
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
    if ($story['preview'] != 0 && empty($item['title'])) {
        $validationerror = _TITLE;
    }
    if ($story['preview'] != 0 && empty($item['hometext'])) {
        $validationerror = _NEWS_ARTICLECONTENT;
    }
    // See ticket #44, incorrect obligatory bodytext 
    //if ($story['preview'] != 0 && empty($item['bodytext'])) {
    //    $validationerror = _NEWS_EXTENDEDTEXT;
    //}

    // if the user has selected to preview the article we then route them back
    // to the new function with the arguments passed here
    if ($story['preview'] == 0 || $validationerror !== false) {
        // log the error found if any
        if ($validationerror !== false) {
            LogUtil::registerError(pnML('_NOFOUND', array('i' => $validationerror)));
        }
        // back to the referer form
        SessionUtil::setVar('newsitem', $item);
        return pnRedirect(pnModURL('News', $referertype, 'new'));

    } else {
        // Confirm authorisation code.
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(pnModURL('News', $referertype, 'view'));
        }

        // As we're not previewing the item let's remove it from the session
        SessionUtil::delVar('newsitem');
    }

    // Notable by its absence there is no security check here

    // Create the news story
    $sid = pnModAPIFunc('News', 'user', 'create', $item);

    if ($sid != false) {
        // Success
        LogUtil::registerStatus(pnML('_CREATEITEMSUCCEDED', array('i' => _NEWS_STORY)));
    }

    return pnRedirect(pnModURL('News', $referertype, 'view'));
}

/**
 * view items
 * This is a standard function to provide an overview of all of the items
 * available from the module.
 * @author Mark West
 * @param 'page' starting number for paged view
 * @return string HTML string
 */
function News_user_view($args = array())
{
    // Security check
    if (!SecurityUtil::checkPermission('Stories::Story', '::', ACCESS_OVERVIEW)) {
        return LogUtil::registerPermissionError();
    }

    // clean the session preview data
    SessionUtil::delVar('newsitem');

    // get all module vars for later use
    $modvars = pnModGetVar('News');

    // Get parameters from whatever input we need
    $page         = isset($args['page']) ? $args['page'] : (int)FormUtil::getPassedValue('page', 1, 'GET');
    $prop         = isset($args['prop']) ? $args['prop'] : (string)FormUtil::getPassedValue('prop', null, 'GET');
    $cat          = isset($args['cat']) ? $args['cat'] : (string)FormUtil::getPassedValue('cat', null, 'GET');
    $itemsperpage = isset($args['itemsperpage']) ? $args['itemsperpage'] : (int)FormUtil::getPassedValue('itemsperpage', $modvars['itemsperpage'], 'GET');

    // work out page size from page number
    $startnum = (($page - 1) * $itemsperpage) + 1;

    // default ihome argument
    $args['ihome'] = isset($args['ihome']) ? (int)$args['ihome'] : null;

    // check if categorization is enabled
    if ($modvars['enablecategorization']) {
        if (!($class = Loader::loadClass('CategoryUtil')) || !($class = Loader::loadClass('CategoryRegistryUtil'))) {
            pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'CategoryUtil | CategoryRegistryUtil')));
        }
        // get the categories registered for the News stories
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('News', 'stories');
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
            if (!empty($cat) && isset($cat['path'])) {
                // include all it's subcategories and build the filter
                $categories = categoryUtil::getCategoriesByPath($cat['path'], '', 'path');
                $catstofilter = array();
                foreach ($categories as $category) {
                    $catstofilter[] = $category['id'];
                }
                $catFilter = array($prop => $catstofilter); 
            } else {
                LogUtil::registerError(_NOTAVALIDCATEGORY);
            }
        }
    }

    // Get matching news stories
    $items = pnModAPIFunc('News', 'user', 'getall',
                          array('startnum' => $startnum,
                                'numitems' => $itemsperpage,
                                'status' => 0,
                                'ihome' => $args['ihome'],
                                'filterbydate' => true,
                                'category' => isset($catFilter) ? $catFilter : null,
                                'catregistry' => isset($catregistry) ? $catregistry : null));

    if ($items == false) {
        LogUtil::registerStatus(pnML('_NOFOUND', array('i' => _NEWS_STORIES)));
    }

    // Create output object
    $pnRender = pnRender::getInstance('News');

    // assign various useful template variables
    $pnRender->assign('startnum', $startnum);
    $pnRender->assign('lang', pnUserGetLang());
    $pnRender->assign($modvars);
    $pnRender->assign('shorturls', pnConfigGetVar('shorturls'));
    $pnRender->assign('shorturlstype', pnConfigGetVar('shorturlstype'));

    // assign the root category
    $pnRender->assign('category', $cat);

    $newsitems = array();
    // Loop through each item and display it
    foreach ($items as $item) {

        // display if it's published and the ihome match (if set)
        if (($item['published_status'] == 0) &&
           (!isset($args['ihome']) || $item['ihome'] == $args['ihome'])) {

            // $info is array holding raw information.
            // Used below and also passed to the theme - jgm
            $info = pnModAPIFunc('News', 'user', 'getArticleInfo', $item);

            // $links is an array holding pure URLs to
            // specific functions for this article.
            // Used below and also passed to the theme - jgm
            $links = pnModAPIFunc('News', 'user', 'getArticleLinks', $info);

            // $preformat is an array holding chunks of
            // preformatted text for this article.
            // Used below and also passed to the theme - jgm
            $preformat = pnModAPIFunc('News', 'user', 'getArticlePreformat',
                                       array('info' => $info,
                                             'links' => $links));

            $anonymous = pnConfigGetVar('anonymous');
            $pnRender->assign(array('info' => $info,
                                    'links' => $links,
                                    'preformat' => $preformat));
            $newsitems[] = $pnRender->fetch('news_user_index.htm', $item['sid']);
        }
    }

    // The items that are displayed on this overview page depend on the individual
    // user permissions. Therefor, we can not cache the whole page.
    // The single entries are cached, though.
    $pnRender->caching = false;

    // Display the entries
    $pnRender->assign('newsitems', $newsitems);

    // Count the number of Pending news items
    $pnRender->assign('pendingcount', pnModAPIFunc('News', 'user', 'countitems', 
                                                   array('status' => 2)));

    // Assign the values for the smarty plugin to produce a pager
    $pnRender->assign('pager', array('numitems' => pnModAPIFunc('News', 'user', 'countitems', 
                                                                array('status' => 0,
                                                                      'filterbydate' => true,
                                                                      'ihome' => isset($args['ihome']) ? $args['ihome'] : null,
                                                                      'category' => isset($catFilter) ? $catFilter : null)),
                                     'itemsperpage' => $itemsperpage));

    // Return the output that has been generated by this function
    return $pnRender->fetch('news_user_view.htm');
}

/**
 * display item
 * This is a standard function to provide detailed informtion on a single item
 * available from the module.
 * @author Mark West
 * @param 'sid' The article ID
 * @param 'objectid' generic object id maps to sid if present
 * @return string HTML string
 */
function News_user_display($args)
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
        return LogUtil::registerError(_MODARGSERROR);
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
            pnModAPIFunc('News', 'user', 'incrementreadcount', array('sid' => $sid));
        } else {
            pnModAPIFunc('News', 'user', 'incrementreadcount', array('title' => $title));
        }
    }

    // Create output object
    $pnRender = pnRender::getInstance('News');

    // For caching reasons you must pass a cache ID
    if (isset($sid)) {
        $pnRender->cache_id = $sid.$page;
    } else {
        $pnRender->cache_id = $title.$page;
    }

    // check out if the contents is cached.
    if ($pnRender->is_cached('news_user_article.htm')) {
       return $pnRender->fetch('news_user_article.htm');
    }

    // Get the news story
    if (isset($sid)) {
        $item = pnModAPIFunc('News', 'user', 'get', array('sid' => $sid));
    } else {
        $item = pnModAPIFunc('News', 'user', 'get', 
                             array('title'     => $title,
                                   'year'      => $year,
                                   'monthname' => $monthname,
                                   'monthnum'  => $monthnum,
                                   'day'       => $day));
        $sid = $item['sid'];
        pnQueryStringSetVar('sid', $sid);
    }

    if ($item === false) {
        return LogUtil::registerError(pnML('_NOSUCHITEM', array('i' => _NEWS_STORY)), 404);
    }

    // Explode the review into an array of seperate pages
    $allpages = explode('<!--pagebreak-->', $item['bodytext']);

    // Set the item hometext to be the required page
    // nb arrays start from zero, pages from one
    $item['bodytext'] = $allpages[$page-1];
    $numitems = count($allpages);
    unset($allpages);

    // If the pagecount is greater than 1 and we're not on the frontpage
    // don't show the hometext
    if ($numitems > 1  && $page > 1) {
        $item['hometext'] = '';
    }

    // $info is array holding raw information.
    // Used below and also passed to the theme - jgm
    $info = pnModAPIFunc('News', 'user', 'getArticleInfo', $item);

    // $links is an array holding pure URLs to
    // specific functions for this article.
    // Used below and also passed to the theme - jgm
    $links = pnModAPIFunc('News', 'user', 'getArticleLinks', $info);

    // $preformat is an array holding chunks of
    // preformatted text for this article.
    // Used below and also passed to the theme - jgm
    $preformat = pnModAPIFunc('News', 'user', 'getArticlePreformat',
                              array('info'  => $info,
                                    'links' => $links));

    // set the page title
    PageUtil::setVar('title', $info['title']);

    // Assign the story info arrays
    $pnRender->assign(array('info'      => $info,
                            'links'     => $links,
                            'preformat' => $preformat,
                            'page'      => $page));
    $pnRender->assign('enablecategorization', pnModGetVar('News', 'enablecategorization'));

    // Now lets assign the informatation to create a pager for the review
    $pnRender->assign('pager', array('numitems'     => $numitems,
                                     'itemsperpage' => 1));

    // Return the output that has been generated by this function
    return $pnRender->fetch('news_user_article.htm');
}

/**
 * display article archives
 * @author Andreas Krapohl
 * @author Mark West
 * @return string HTML string
 */
function News_user_archives($args)
{
    // Get parameters from whatever input we need
    $year  = (int)FormUtil::getPassedValue('year', null, 'REQUEST');
    $month = (int)FormUtil::getPassedValue('month', null, 'REQUEST');
    $day = '31';

    // Security check
    if (!SecurityUtil::checkPermission('Stories::Story', '::', ACCESS_OVERVIEW)) {
        return LogUtil::registerPermissionError();
    }

    // Dates validation
    $currentdate = explode(',', adodb_strftime('%Y,%m,%d', time()));
    if (!empty($year) || !empty($month)) {
        if ((empty($year) || empty($month)) ||
            ($year > (int)$currentdate[0] || ($year == (int)$currentdate[0] && $month > (int)$currentdate[1]))) {
                pnRedirect(pnModURL('News', 'user', 'archives'));
        } elseif ($year == (int)$currentdate[0] && $month == (int)$currentdate[1]) {
            $day = (int)$currentdate[2];
        }
    }

    // Load localized month names
    $months = explode(' ', _MONTH_LONG);

    // Create output object
    $cacheid = "$month|$year";
    $pnRender = pnRender::getInstance('News', null, $cacheid);

    // output vars
    $archivemonths = array();

    if (!empty($year) && !empty($month)) {
        $items = pnModAPIFunc('News', 'user', 'getall',
                              array('order' => 'from',
                                    'from' => "$year-$month-01 00:00:00",
                                    'to' => "$year-$month-$day 23:59:59"));
        $pnRender->assign('year', $year);
        $pnRender->assign('month', $months[$month-1]);
    } else {
        // get all matching news stories
        $items = pnModAPIFunc('News', 'user', 'getMonthsWithNews');

        foreach ($items as $item) {
            $month = DateUtil::getDatetime_Field($item, 2);
            $year = DateUtil::getDatetime_Field($item, 1);
            $linktext = $months[$month-1];
            $linktext .= " $year";
            $archivemonths[] = array('url' => pnModURL('News', 'user', 'archives', array( 'month' => $month, 'year' => $year)),
                                     'title' => $linktext);
        }
        $items = false;
    }
    $pnRender->assign('archivemonths', $archivemonths);
    $pnRender->assign('archiveitems', $items);
    $pnRender->assign('enablecategorization', pnModGetVar('News', 'enablecategorization'));

    // Return the output that has been generated by this function
    return $pnRender->fetch('news_user_archives.htm');
}

/**
 * display article
 * @author Mark West
 * @return string HTML string
 */
function News_user_preview($args)
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

    $pnRender = pnRender::getInstance('News', false);

    $pnRender->assign('preview', array('title'    => $title,
                                       'hometext' => $hometext,
                                       'bodytext' => $bodytext,
                                       'notes'    => $notes));

    return $pnRender->fetch('news_user_preview.htm');
}

/**
 * display available categories in News
 * @author Erik Spaan [espaan]
 * @return string HTML string
 */
function News_user_categorylist($args)
{
    // Security check
    if (!SecurityUtil::checkPermission('Stories::Story', '::', ACCESS_OVERVIEW)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = pnRender::getInstance('News');

    $enablecategorization = pnModGetVar('News', 'enablecategorization');
    if ($enablecategorization) {
        if (!($class = Loader::loadClass ('CategoryRegistryUtil')) || !($class = Loader::loadClass ('CategoryUtil'))) {
            pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'CategoryRegistryUtil | CategoryUtil')));
        }
        // Get the categories registered for News
        $catregistry = CategoryRegistryUtil::getRegisteredModuleCategories('News', 'stories');
        $properties  = array_keys($catregistry);

        $propertiesdata = array();
        foreach ($properties as $property) {
            $rootcat = CategoryUtil::getCategoryByID($catregistry[$property]);
            if (!empty($rootcat)) {
                $rootcat['path'] .= '/';
                // Get all categories in this category property
                $subcategories    = CategoryUtil::getCategoriesByParentID($rootcat['id']);
                foreach ($subcategories as $category) {
                    // Get the number of articles in this category within this category property
                    $nrofarticles[$category['id']] = pnModAPIFunc('News', 'user', 'countitems',
                                                                  array('status' => 0,
                                                                        'ihome' => null,
                                                                        'filterbydate' => true,
                                                                        'category' => array($property => $category['id']),
                                                                        'catregistry' => $catregistry));
                }
                // Store data per property for listing in the overview
                $propertiesdata[] = array('name' => $property,
                                          'rootcat' => $rootcat,
                                          'subcategories' => $subcategories,
                                          'nrofarticles' => $nrofarticles);
            }
        }

        // Assign category related vars
        $pnRender->assign('properties', $properties);
        $pnRender->assign('propertiesdata', $propertiesdata);
    }

    // Assign the config vars
    $pnRender->assign('enablecategorization', $enablecategorization);
    $pnRender->assign('shorturls', pnConfigGetVar('shorturls'));
    $pnRender->assign('shorturlstype', pnConfigGetVar('shorturlstype'));
    $pnRender->assign('language', pnUserGetLang());

    // Return the output that has been generated by this function
    return $pnRender->fetch('news_user_categorylist.htm');
}