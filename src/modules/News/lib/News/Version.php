<?php
/**
 * Zikula Application Framework
 *
 * @copyright  (c) Zikula Development Team
 * @link       http://www.zikula.org
 * @license    GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author     Mark West [markwest]
 * @author     Mateo Tibaquira [mateo]
 * @author     Erik Spaan [espaan]
 * @category   Zikula_3rdParty_Modules
 * @package    Content_Management
 * @subpackage News
 */

class News_Version extends Zikula_Version
{
    public function getMetaData()
    {
        $meta = array();
        $meta['displayname'] = $this->__('News publisher');
        $meta['description'] = $this->__('Provides the ability to publish and manage news articles contributed by site users, with support for news categories and various associated blocks.');
        $meta['version']     = '3.0.0';
        //! this defines the module's url
        $meta['url']            = $this->__('news');
        $meta['securityschema'] = array('News::' => 'Contributor ID::Article ID',
                                        'News:pictureupload:' => '::',
                                        'News:publicationdetails:' => '::');
        return $meta;
    }
}