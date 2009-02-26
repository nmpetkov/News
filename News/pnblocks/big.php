<?php
/**
 * Zikula Application Framework
 *
 * @copyright  (c) Zikula Development Team
 * @link       http://www.zikula.org
 * @version    $Id: big.php 75 2009-02-24 04:51:52Z mateo $
 * @license    GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author     Mark West <mark@zikula.org>
 * @category   Zikula_3rdParty_Modules
 * @package    Content_Management
 * @subpackage News
 */

/**
 * initialise block
 *
 * @author       The Zikula Development Team
 */
function News_bigblock_init()
{
    // Security
    SecurityUtil::registerPermissionSchema('Bigblock::', 'Block title::');
}

/**
 * get information on block
 *
 * @author       The Zikula Development Team
 * @return       array       The block information
 */
function News_bigblock_info()
{
    return array('text_type'      => 'Big',
                 'module'         => 'News',
                 'text_type_long' => 'Today\'s Big Story',
                 'allow_multiple' => true,
                 'form_content'   => false,
                 'form_refresh'   => false,
                 'show_preview'   => true);
}

/**
 * display block
 *
 * @author       The Zikula Development Team
 * @param        array       $blockinfo     a blockinfo structure
 * @return       output      the rendered bock
 */
function News_bigblock_display($blockinfo)
{
    // security check
    if (!SecurityUtil::checkPermission('Bigblock::', "$blockinfo[title]::", ACCESS_READ)) {
        return;
    }

    // get todays date
    $today = getdate();
    $day = $today['mday'];
    if ($day < 10) {
        $day = "0$day";
    }
    $month = $today['mon'];
    if ($month < 10) {
        $month = "0$month";
    }
    $year = $today['year'];
    $tdate = "$year-$month-$day";

    // call the API
    $articles = pnModAPIFunc('News', 'user', 'getall',
                             array('tdate' => $tdate, 'ihome' => 0, 'order' => 'counter', 'numitems' => 1));

    if (empty($articles)) {
        return;
    } else {
        $info = pnModAPIFunc('News', 'user', 'getArticleInfo', $row = $articles[0]);
        if (SecurityUtil::checkPermission('Stories::Story', "$info[aid]::$info[sid]", ACCESS_OVERVIEW)) {
            $links = pnModAPIFunc('News', 'user', 'getArticleLinks', $info);
            $preformat = pnModAPIFunc('News', 'user', 'getArticlePreformat', array('info' => $info, 'links' => $links));
        } else {
            return;
        }
    }

    if (empty($blockinfo['title'])) {
        $blockinfo['title'] = _TODAYBIG;
    }

    $pnRender = pnRender::getInstance('News');

    $pnRender->assign(array('info' => $info,
                            'links' => $links,
                            'preformat' => $preformat));

    $blockinfo['content'] = $pnRender->fetch('news_block_big.htm');
    return pnBlockThemeBlock($blockinfo);
}
