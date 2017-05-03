<?php
////////////////////////////////////////////////////////////////////////////////
//   Copyright (C) ReloadCMS Development Team                                 //
//   http://reloadcms.sf.net                                                  //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   This product released under GNU General Public License v2                //
////////////////////////////////////////////////////////////////////////////////
define('RSS_GENERATOR', 'ReloadCMS ' . RCMS_VERSION_A . '.'  . RCMS_VERSION_B . '.' . RCMS_VERSION_C . RCMS_VERSION_SUFFIX);

class rss_feed{
    var $title = '';
    var $url = '';
    var $description = '';
    var $encoding = '';
    var $language = '';
    var $copyright = '';
    var $generator = RSS_GENERATOR;
    var $items = array();
    
    
    function __construct($title, $url, $description, $encoding, $language, $copyright){
        $this->title = $title;
        $this->url = $url;
        $this->description = $description;
        $this->encoding = $encoding;
        $this->language = $language;
        $this->copyright = $copyright;
    }
    
    function addItem($title, $description, $link, $date, $category = ''){
        $this->items[] = array($title, $description, $link, $date, $category);
    }
    
    function showFeed(){
        echo "<?xml version=\"1.0\" encoding=\"{$this->encoding}\" ?>\r\n";
        echo "<rss version=\"2.0\">\n";
        echo "\t<channel>\n";
        echo "\t\t<title>{$this->title}</title>\n";
        echo "\t\t<link>{$this->url}</link>\n";
        echo "\t\t<description>{$this->description}</description>\n";
        echo "\t\t<language>{$this->language}</language>\n";
        echo "\t\t<copyright>{$this->copyright}</copyright>\n";
        echo "\t\t<lastBuildDate>" . date('r') . "</lastBuildDate>\n";
        echo "\t\t<generator>{$this->generator}</generator>\n";
        foreach ($this->items as $item){
            echo "\t\t<item>\n";
            echo "\t\t\t<title>{$item[0]}</title>\n";
            echo "\t\t\t<link>{$item[2]}</link>\n";
            echo "\t\t\t<description>{$item[1]}</description>\n";
            if(!empty($item[4])) echo "\t\t\t<category>{$item[4]}</category>\n";
            echo "\t\t\t<pubDate>" . date('r', $item[3]) . "</pubDate>\n";
            echo "\t\t</item>\n";
        }
        echo "\t</channel>\n";
        echo "</rss>\n";
    }
}
?>