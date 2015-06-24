<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2001, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id: function.prevpostlink.php 75 2009-02-24 04:51:52Z mateo $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_3rdParty_Modules
 * @subpackage News
*/

/**
 * view plugin
 *
 * This file is a plugin for view, the Zikula implementation of Smarty
 *
 * @package Zikula_3rdParty_Modules
 * @subpackage News
 * @version $Id: function.prevpostlink.php 75 2009-02-24 04:51:52Z mateo $
 * @author The Zikula development team
 * @link http://www.zikula.org The Zikula Home Page
 * @copyright Copyright (C) 2002 by the Zikula Development Team
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * Smarty function to display a link to the previous post
 *
 * Example
 * {prevpostlink sid=$info.sid layout='<span class="news_metanav">&laquo;</span> %link%'}
 *
 * @author Mark West
 * @since 3/7/2007
 * @see function.prevpostlink.php::smarty_function_prevpostlink()
 * @param array $params All attributes passed to this function from the template
 * @param object &$smarty Reference to the Smarty object
 * @param integer $sid article id
 * @param string $layout HTML string in which to insert link
 * @return string the results of the module function
 */
function smarty_function_prevpostlink($params, &$smarty)
{
    if (!isset($params['sid'])) {
        // get the info template var
        $info = $smarty->get_template_vars('info');
        $params['sid'] = $info['sid'];
    }

    if (!isset($params['layout'])) {
        $params['layout'] = '<span class="news_metanav">&laquo;</span> %link%';
    }

    $linkTitle = isset($params['title']) ? $params['title'] : '';

    $article = ModUtil::apiFunc('News', 'user', 'getall',
                            array('query' => array(array('sid', '<', $params['sid'])),
                                  'orderdir' => 'DESC',
                                  'numitems' => 1));

    if (!$article) {
        return;
    }

    $article = $article[0];

    if ($linkTitle == '') {
        $linkTitle = $article['title'];
    }

    $articlelink = ModUtil::url('News', 'user', 'display', array('sid' => $article['sid'], 'from' => $article['from'], 'urltitle' => $article['urltitle']));
    $articlelink = '<a href="'.DataUtil::formatForDisplay($articlelink).'">'.DataUtil::formatForDisplay($linkTitle).'</a>';

    $articlelink = str_replace('%link%', $articlelink, $params['layout']);

    if (isset($params['assign'])) {
        $smarty->assign($params['assign'], $articlelink);
    } else {
        return $articlelink;
    }
}
