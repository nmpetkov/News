<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: pnadmin.php 24342 2008-06-06 12:03:14Z markwest $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author Mateo Tibaquira [mateo]
 * @author Erik Spaan [espaan]
 * @package Zikula_Value_Addons
 * @subpackage News
*/

/**
 * Content plugin class for news articles
 */
class News_contenttypesapi_NewsArticlesPlugin extends contentTypeBase
{
  // indispensable vars
  var $title;
  var $categories;
  var $status;
  var $show;
  var $limit;
  var $order;
  // config flags
  var $dayslimit;
  var $maxtitlelength;
  var $titlewraptext;
  var $disphometext;
  var $maxhometextlength;
  var $hometextwraptext;
  var $dispuname;
  var $dispdate;
  var $dateformat;
  var $dispreads;
  var $dispcomments;
  var $dispsplitchar;
  var $dispnewimage;
  var $newimagelimit;
  var $newimageset;
  var $newimagesrc;
  var $linktosubmit;

  function getModule() { return 'News'; }
  function getName() { return 'newsarticles'; }
  function getTitle() { return _NEWS_CONTENTTYPE_NEWSARTICLESTITLE; }
  function getDescription() { return _NEWS_CONTENTTYPE_NEWSARTICLESDESCR; }
  function isTranslatable() { return false; }

  /**
   * Load the data into the object
   */
  function loadData($data)
  {
    // Get the registrered categories for the News module
    Loader::loadClass('CategoryRegistryUtil');
    $catregistry  = CategoryRegistryUtil::getRegisteredModuleCategories ('News', 'stories');
    $properties = array_keys($catregistry);

    // indispensable vars
    $this->title = $data['title'];
    // Store the the seperate category related form returns in the categories array for News catfiltering
    $this->categories = array();
    foreach($properties as $prop) {
        $this->categories[$prop] = $data['category__'.$prop];
    }
    $this->status = $data['status'];
    $this->show = $data['show'];
    $this->limit = $data['limit'];
    $this->order = $data['order'];
    // config flags
    $this->dayslimit = $data['dayslimit'];
    $this->maxtitlelength = $data['maxtitlelength'];
    $this->titlewraptext = $data['titlewraptext'];
    $this->disphometext = $data['disphometext'];
    $this->maxhometextlength = $data['maxhometextlength'];
    $this->hometextwraptext = $data['hometextwraptext'];
    $this->dispuname = $data['dispuname'];
    $this->dispdate = $data['dispdate'];
    $this->dateformat = $data['dateformat'];
    $this->dispreads = $data['dispreads'];
    $this->dispcomments = $data['dispcomments'];
    $this->dispsplitchar = $data['dispsplitchar'];
    $this->dispnewimage = $data['dispnewimage'];
    $this->newimagelimit = $data['newimagelimit'];
    $this->newimageset = $data['newimageset'];
    $this->newimagesrc = $data['newimagesrc'];
    $this->linktosubmit = $data['linktosubmit'];
  }

  /**
   * Display the data to the containing Content page
   */
  function display()
  {
    // Parameters for category related items properties like topicimage
    $lang = pnUserGetLang();
    $topicProperty = pnModGetVar('News', 'topicproperty');
    $topicField = empty($topicProperty) ? 'Main' : $topicProperty;

    // work out the parameters for the News api call
    $apiargs = array();
    switch ($this->show) {
        case 3: // non lead page articles
            $apiargs['ihome'] = 1;
            break;
        case 2: // lead page articles
            $apiargs['ihome'] = 0;
            break;
        // all - doesn't need ihome 
    }
    $apiargs['numitems'] = $this->limit; // Nr of articles to obtain
    $apiargs['status'] = $this->status; // Published status

    // Handle the sorting order
    switch ($this->order) {
        case 1:
            $apiargs['order'] = 'counter';
            break;
        case 0:
        default:
            // Use News module setting, so don't set apiargs[order]
    }

    $enablecategorization = pnModGetVar('News', 'enablecategorization');

    // Make a category filter only if categorization is enabled in News module
    if ($enablecategorization && $this->categories != null) {
        // load the categories system
        Loader::loadClass('CategoryRegistryUtil');
        // Get the registrered categories for the News module
        $catregistry  = CategoryRegistryUtil::getRegisteredModuleCategories ('News', 'stories');
        $apiargs['catregistry'] = $catregistry;
        $apiargs['category'] = $this->categories;
    }

    // Limit the shown articles in days using DateUtil
    if ((int)$this->dayslimit>0 && $vars['order']==0) {
        $apiargs['from'] = DateUtil::getDatetime_NextDay(-$this->dayslimit);
        $apiargs['to'] = DateUtil::getDatetime();
    }
    
    // Apply datefiltering
    $apiargs['filterbydate'] = true;    

    // call the News api and get the requested articles with the above arguments
    $items = pnModAPIFunc('News', 'user', 'getall', $apiargs);

    // create the output object
    $render = pnRender::getInstance('News', false);

    // UserUtil is not automatically loaded, so load it now if needed and set anonymous
    if ($this->dispuname) {
        Loader::loadClass('UserUtil');
        $anonymous = pnConfigGetVar('anonymous');
    }

    // check for an empty return
    if (!empty($items)) {
        // loop through the items and prepare for display
        foreach (array_keys($items) as $k) {
            // Get specific information from the article. It was a choice not to use the pnuserapi functions
            // GetArticleInfo, GetArticleLinks and getArticlesPreformat because of speed etc.
            // --- Check for Topic related properties like topicimage, topicsearchurl etc.
            if ($enablecategorization && !empty($items[$k]['__CATEGORIES__']) && isset($items[$k]['__CATEGORIES__'][$topicField])) {
                $items[$k]['topic'] = $items[$k]['__CATEGORIES__'][$topicField]['id'];
                $items[$k]['tid']   = $items[$k]['__CATEGORIES__'][$topicField]['id'];
                $items[$k]['topicname'] = isset($items[$k]['__CATEGORIES__'][$topicField]['display_name'][$lang]) ? $items[$k]['__CATEGORIES__'][$topicField]['display_name'][$lang] : $items[$k]['__CATEGORIES__'][$topicField]['name'];
                // set the topic image if topic_image category property exists
                $items[$k]['topicimage'] = (isset($items[$k]['__CATEGORIES__'][$topicField]['__ATTRIBUTES__']) && isset($items[$k]['__CATEGORIES__'][$topicField]['__ATTRIBUTES__']['topic_image'])) ? $items[$k]['__CATEGORIES__'][$topicField]['__ATTRIBUTES__']['topic_image'] : '';
                // set the topic description if exists
                $items[$k]['topictext'] = isset($items[$k]['__CATEGORIES__'][$topicField]['display_desc'][$lang]) ? $items[$k]['__CATEGORIES__'][$topicField]['display_desc'][$lang] : '';
                // set the path of the topic
                $items[$k]['topicpath']  = $items[$k]['__CATEGORIES__'][$topicField]['path_relative'];
                // set the url to search for this topic
                if (pnConfigGetVar('shorturls') && pnConfigGetVar('shorturlstype') == 0) {
                    $items[$k]['topicsearchurl'] = DataUtil::formatForDisplay(pnModURL('News', 'user', 'view', array('prop' => $topicField, 'cat' => $items[$k]['topicpath'])));
                } else {
                    $items[$k]['topicsearchurl'] = DataUtil::formatForDisplay(pnModURL('News', 'user', 'view', array('prop' => $topicField, 'cat' => $items[$k]['tid'])));
                }
            } else {
                $items[$k]['topic']      = null;
                $items[$k]['tid']        = null;
                $items[$k]['topicname']  = '';
                $items[$k]['topicimage'] = '';
                $items[$k]['topictext']  = '';
                $items[$k]['topicpath']  = '';
                $items[$k]['topicsearchurl'] = '';
            }
            // Optional new image if the difference in days from creation date and now < the specified limit
            $items[$k]['dispnewimage'] = ($this->dispnewimage && DateUtil::getDatetimeDiff_AsField($items[$k]['cr_date'], DateUtil::getDatetime(), 3) < (int)$this->newimagelimit);
            // Wrap the title if needed
            if ((int)$this->maxtitlelength > 0 && strlen($items[$k]['title']) > (int)$this->maxtitlelength)  {
                // wrap the title
                $items[$k]['title'] = substr($items[$k]['title'], 0, (int)$this->maxtitlelength);
                $items[$k]['titlewrapped'] = true;
                //$items[$k]['title'] .= $this->titlewraptext;
            }
            // Get the user information from the author id
            if ($this->dispuname) {
                if ($items[$k]['aid'] == 0) {
                    $items[$k]['aid_uname'] = $anonymous;
                    $items[$k]['aid_name'] = $anonymous;
                } else {
                    $user = UserUtil::getPNUser($items[$k]['aid']);
                    $items[$k]['aid_uname'] = $user['uname'];
                    $items[$k]['aid_name'] = $user['name'];
                }
            }
            // Get the optional commentcount if EZComments is available
            if ($this->dispcomments && pnModAvailable('EZComments') && pnModIsHooked('EZComments', 'News')) {
                $items[$k]['comments'] = pnModAPIFunc('EZComments', 'user', 'countitems', array('mod' => 'News', 'objectid' => $items[$k]['sid'], 'status' => 0));
            }
            // Optional display of the hometext (frontpage teaser)
            if ($this->disphometext) {
                if ($this->maxhometextlength>0 && strlen(strip_tags($items[$k]['hometext']))>(int)$this->maxhometextlength) {
                    $items[$k]['hometextwrapped'] = true;
                }
            }
            $items[$k]['readperm']  = SecurityUtil::checkPermission('Stories::Story', "$items[$k][aid]::$items[$k][sid]", ACCESS_READ);
        }
        if ($this->dispuname||$this->dispdate||$this->dispreads||$this->dispcomments) {
            $render->assign('dispinfo', true);
            $render->assign('dispuname', $this->dispuname);
            $render->assign('dispdate', $this->dispdate);
            $render->assign('dispreads', $this->dispreads);
            $render->assign('dispcomments', $this->dispcomments);
            $render->assign('dispsplitchar', $this->dispsplitchar);
        } else {
            $render->assign('dispinfo', false);
        }
        if ($this->dispnewimage) {
            $render->assign('newimageset', $this->newimageset);
            $render->assign('newimagesrc', $this->newimagesrc);
        }
        if ($this->disphometext) {
            $render->assign('disphometext', $this->disphometext);
            $render->assign('hometextwraptext', $this->hometextwraptext);
            $render->assign('maxhometextlength', $this->maxhometextlength);
        }
        $render->assign('titlewraptext', $this->titlewraptext);
        
    }
    $render->assign('linktosubmit',$this->linktosubmit);
    $render->assign('stories', $items);
    $render->assign('title', $this->title);
    return $render->fetch('contenttype/newsarticles_view.html');
  }

  /**
   * In Content editing mode this shows the Content plugin contents
   */
  function displayEditing()
  {
    Loader::loadClass('CategoryUtil');
    $properties = array_keys($this->categories);
    $lang = pnUserGetLang();
    // Construct the selected categories array
    $catnames = array();
    foreach ($properties as $prop) {
		foreach ($this->categories[$prop] as $catid) {
			$cat = CategoryUtil::getCategoryByID($catid);
			$catnames[] = isset($cat['display_name'][$lang]) ? $cat['display_name'][$lang] : $cat['name'];
		}
    }
	$catname = implode(' | ',$catnames);
    $output = '<h4>' .  DataUtil::formatForDisplayHTML($this->title) . '</h4>';
    $output .= '<p>' . DataUtil::formatForDisplayHTML(pnML('_NEWS_CONTENTTYPE_NEWSARTICLESOFTHECAT', array('category'=>$catname))) . '</p>';
    return $output;
  }
  
  /**
   * Load the intial data into the object
   */
  function getDefaultData()
  { 
    return array('title' => '',
                 'categories' => null,
                 'status' => 0,
                 'show' => 1,
                 'limit' => 5,
                 'order' => 0,
                 'dayslimit' => 0,
                 'maxtitlelength' => 0,
                 'titlewraptext' => '...',
                 'disphometext' => false,
                 'maxhometextlength' => 300,
                 'hometextwraptext' => '['._NEWS_CONTENTTYPE_NEWSARTICLESREADMORE.']',
                 'dispuname' => false,
                 'dispdate' => true,
                 'dateformat' => '%x',
                 'dispreads' => false,
                 'dispcomments' => false,
                 'dispsplitchar' => ',',
                 'dispnewimage' => false,
                 'newimagelimit' => 3,
                 'newimageset' => 'global',
                 'newimagesrc' => 'new_3day.gif',
                 'linktosubmit' => true);
  }

  /**
   * Fill some variables before the editing of the plugin parameters
   */
  function startEditing(&$render)
  {
    // Get the News categorization setting
    $enablecategorization = pnModGetVar('News', 'enablecategorization');
    // Select categories only if enabled for the News module, otherwise selector will not be shown in modify template
    if ($enablecategorization) {
        // load the categories system
        if (!($class = Loader::loadClass('CategoryRegistryUtil'))) {
            pn_exit (pnML('_UNABLETOLOADCLASS', array('s' => 'CategoryRegistryUtil')));
        }
        // Get the registrered categories for the News module
        $catregistry  = CategoryRegistryUtil::getRegisteredModuleCategories ('News', 'stories');
        $render->assign('catregistry', $catregistry);
    }
    $render->assign('enablecategorization', $enablecategorization);

    $showoptions = array();
    $showoptions[] = array('value' => 1, 'text' => _ALL);
    $showoptions[] = array('value' => 2, 'text' => _NEWS_CONTENTTYPE_NEWSARTICLESFRONTPAGE);
    $showoptions[] = array('value' => 3, 'text' => _NEWS_CONTENTTYPE_NEWSARTICLESNONFRONTPAGE);

    $statusoptions = array();
    $statusoptions[] = array('value' => 0, 'text' => _NEWS_CONTENTTYPE_PUBLISHED);
    $statusoptions[] = array('value' => 1, 'text' => _NEWS_CONTENTTYPE_REJECTED);
    $statusoptions[] = array('value' => 2, 'text' => _NEWS_CONTENTTYPE_PENDING);
    $statusoptions[] = array('value' => 3, 'text' => _NEWS_CONTENTTYPE_ARCHIVED);

    $orderoptions = array();
    $orderoptions[] = array('value' => 0, 'text' => _NEWS_CONTENTTYPE_ORDERBYNEWSSETTING);
    $orderoptions[] = array('value' => 1, 'text' => _NEWS_CONTENTTYPE_ORDERBYCOUNTER);

    $render->assign('showoptions', $showoptions);
    $render->assign('statusoptions', $statusoptions);
    $render->assign('orderoptions', $orderoptions);
  }
  
  /**
   * Optional checking of the entered data
   */
  function isValid(&$data, &$message)
  {
    /*$r = '/\?v=([-a-zA-Z0-9_]+)(&|$)/';
    if (preg_match($r, $data['url'], $matches))
    {
      $this->videoId = $data['videoId'] = $matches[1];
      return true;
    }
    $message = _NEWS_CONTENTTYPE_NEWSARTICLESNOTVALIDURL;
    return false;*/
    return true;
  }
}

/**
 * Main function that instantiates the class
 */
function News_contenttypesapi_NewsArticles($args)
{
  return new News_contenttypesapi_NewsArticlesPlugin($args['data']);
}