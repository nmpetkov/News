<item>
    <title>{$info.title|safetext}</title>
    <link>{modurl modname='News' type='user' func='display' sid=$info.sid title=$info.urltitle fqurl=true}</link>
    <description>
        <![CDATA[
        {if $modvars.News.picupload_enabled AND $info.pictures gt 0}
            <img src="{$modvars.News.picupload_uploaddir}/pic_sid{$info.sid}-0-thumb.jpg" alt="{gt text='Picture %1$s for %2$s' tag1='0' tag2=$info.title}" />
        {/if}
        {$info.hometext|notifyfilters:'news.filter_hooks.articles.filter'}
        ]]>
    </description>
    {assign var='format' value='D, d M Y H:i:s O'}
    {assign var='date' value=$info.from|strtotime}
    <pubDate>{$format|date:$date}</pubDate>
    <guid>{modurl modname='News' type='user' func='display' sid=$info.sid title=$info.urltitle fqurl=true}</guid>
</item>
