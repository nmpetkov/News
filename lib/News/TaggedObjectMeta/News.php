<?php

/**
 * Tag - a content-tagging module for the Zikukla Application Framework
 * 
 * @license MIT
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */
class News_TaggedObjectMeta_News extends Tag_AbstractTaggedObjectMeta
{

    function __construct($objectId, $areaId, $module, $urlString = null, Zikula_ModUrl $urlObject = null)
    {
        parent::__construct($objectId, $areaId, $module, $urlString, $urlObject);

        $newsItem = ModUtil::apiFunc('News', 'user', 'get', array('sid' => $this->getObjectId()));
        // the api takes care of the permissions check. we must check for pending/expiration & status
        $expired = (isset($newsItem['to']) && (strtotime($newsItem['to']) < strtotime("now")));
        $pending = (strtotime($newsItem['from']) > strtotime("now"));
        $statuspublished = ($newsItem['published_status'] == News_Api_User::STATUS_PUBLISHED);
        if ($newsItem && $statuspublished && !$pending && !$expired) {
            $this->setObjectAuthor($newsItem['contributor']);
            $this->setObjectDate($newsItem['from']);
            $this->setObjectTitle($newsItem['title']);
        }
    }

    public function setObjectTitle($title)
    {
        $this->title = $title;
    }

    public function setObjectDate($date)
    {
        $this->date = DateUtil::formatDatetime($date, 'datetimebrief');
    }

    public function setObjectAuthor($author)
    {
        $this->author = $author;
    }

}