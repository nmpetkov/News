<?php
/**
 * Zikula Application Framework
 *
 * @copyright  (c) Zikula Development Team
 * @link       http://www.zikula.org
 * @version    $Id: encodeurl.php 433 2010-08-22 17:38:43Z espaan $
 * @license    GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author     Mark West <mark@zikula.org>
 * @category   Zikula_3rdParty_Modules
 * @package    Content_Management
 * @subpackage News
 */

/**
 * form custom url string
 *
 * @author Mark West
 * @return string custom url string
 */
function News_userapi_encodeurl($args)
{
    // check we have the required input
    if (!isset($args['modname']) || !isset($args['func']) || !isset($args['args'])) {
        return LogUtil::registerArgsError();
    }

    if (!isset($args['type'])) {
        $args['type'] = 'user';
    }

    // create an empty string ready for population
    $vars = '';

    // for the display function use the defined permalink structure
    if ($args['func'] == 'display' || $args['func'] == 'displaypdf') {
        // check for the generic object id parameter
        if (isset($args['args']['objectid'])) {
            $args['args']['sid'] = $args['args']['objectid'];
        }
        // check the permalink structure and obtain any missing vars
        $permalinkformat = pnModGetVar('News', 'permalinkformat');
        if (isset($args['args']['from']) && isset($args['args']['urltitle'])) {
            $date = getdate(strtotime($args['args']['from']));
            $in = array('%category%', '%articleid%', '%articletitle%', '%year%', '%monthnum%', '%monthname%', '%day%');
            $out = array((isset($args['args']['__CATEGORIES__']['Main']['path_relative']) ? $args['args']['__CATEGORIES__']['Main']['path_relative'] : null), $args['args']['sid'], $args['args']['urltitle'], $date['year'], $date['mon'], strtolower(substr($date['month'], 0 , 3)), $date['mday']);
            //$out = array(@$args['args']['__CATEGORIES__']['Main']['path_relative'], $args['args']['sid'], $args['args']['urltitle'], $date['year'], $date['mon'], strtolower(substr($date['month'], 0 , 3)), $date['mday']);
        } else {
            // get the item (will be cached by DBUtil)
            $item = pnModAPIFunc('News', 'user', 'get', array('sid' => $args['args']['sid']));
            $date = getdate(strtotime($item['from']));
            $in = array('%category%', '%articleid%', '%articletitle%', '%year%', '%monthnum%', '%monthname%', '%day%');
            $out = array((isset($item['__CATEGORIES__']['Main']['path_relative']) ? $item['__CATEGORIES__']['Main']['path_relative'] : null), $item['sid'], $item['urltitle'], $date['year'], $date['mon'], strtolower(substr($date['month'], 0 , 3)), $date['mday']);
            //$out = array(@$item['__CATEGORIES__']['Main']['path_relative'], $item['sid'], $item['urltitle'], $date['year'], $date['mon'], strtolower(substr($date['month'], 0 , 3)), $date['mday']);
        }
        $vars = str_replace($in, $out, $permalinkformat);
        if (isset($args['args']['page']) && $args['args']['page'] != 1) {
            $vars .= '/page/'.$args['args']['page'];
        }
    }

    // for the archives use year/month
    if ($args['func'] == 'archives' && isset($args['args']['year']) && isset($args['args']['month'])) {
        $vars = "{$args['args']['year']}/{$args['args']['month']}";
    }

    // add the category name to the view link
    if ($args['func'] == 'view' && isset($args['args']['prop'])) {
        $vars = $args['args']['prop'];
        $vars .= isset($args['args']['cat']) ? '/'.$args['args']['cat'] : '';
    }

    // view, main or now function pager
    if (isset($args['args']['page']) && is_numeric($args['args']['page']) &&
        ($args['func'] == '' || $args['func'] == 'main' || $args['func'] == 'view')) {
        if (!empty($vars)) {
            $vars .= "/page/{$args['args']['page']}";
        } else {
            $vars = "page/{$args['args']['page']}";
        }
    }

    // don't display the function name if either displaying an article or the normal overview
    if ($args['func'] == 'main' || $args['func'] == 'display') {
        $args['func'] = '';
    }

    // construct the custom url part
    if (empty($args['func']) && empty($vars)) {
        return $args['modname'] . '/';
    } elseif (empty($args['func'])) {
        return $args['modname'] . '/' . $vars . '/';
    } elseif (empty($vars)) {
        return $args['modname'] . '/' . $args['func'] . '/';
    } else {
        return $args['modname'] . '/' . $args['func'] . '/' . $vars . '/';
    }
}
