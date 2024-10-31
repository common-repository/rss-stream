<?php
/*
Plugin Name: RSS Stream
Version: 1.0.3
Plugin URI: http://rick.jinlabs.com/code/rss-stream
Description: Displays your social feeds in a lifestream way.
Author: Ricardo Gonz&aacute;lez
Author URI: http://rick.jinlabs.com/
*/

/*  Copyright 2007  Ricardo González Castro (rick[at]jinlabs.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


//define('MAGPIE_CACHE_AGE', 120);
define('MAGPIE_CACHE_ON', 0); //2.7 Cache Bug
define('MAGPIE_INPUT_ENCODING', 'UTF-8');
define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');

include_once(ABSPATH . WPINC . '/rss.php');
$lifestream = array();

function RSSS_URL() {
  return trailingslashit(get_settings('siteurl')) . 'wp-content/plugins/rss-stream';
}

// Display Plurk messages
function rss_stream_plurk($username = '') {

	global $lifestream;

	$messages = fetch_rss('http://www.plurk.com/user/'.$username.'.xml');
	if ($username != '') {
    foreach ( $messages->items as $message ) {
      $msg = $message['atom_content'];
      $link = 'http://www.plurk.com'.$message['link_'];
      $date = strtotime($message['published']);

      $lifestream[$date]['date'] = $date;	
      $lifestream[$date]['type'] = 'plurk';		  	
      $lifestream[$date]['link'] = $link;
      $lifestream[$date]['msg'] =  $msg;
			            
    }
	}   
}

function rss_stream_twitter($username = '', $hyperlinks = true, $twitter_users = true) {

	global $lifestream;
	
	$messages = fetch_rss('http://twitter.com/statuses/user_timeline/'.$username.'.rss');

	if ($username != '') {
		foreach ( $messages->items as $message ) {
			$msg = " ".substr(strstr($message['description'],': '), 2, strlen($message['description']))." ";
			$date = strtotime($message['pubdate']);
			$link = $message['link'];
		
			$lifestream[$date]['date'] = $date;	
	  	$lifestream[$date]['type'] = 'twitter';		  	
			$lifestream[$date]['link'] = $link;

            if ($hyperlinks) { $msg = rss_stream_hyperlinks($msg); }
            if ($twitter_users)  { $msg = rss_stream_twitter_users($msg); }

			$lifestream[$date]['msg'] =  $msg;
		}
	}
}

function rss_stream_jaiku($username = '', $hyperlinks = true, $jaiku_users = true) {

	global $lifestream;
	
	$messages = fetch_rss('http://'.$username.'.jaiku.com/feed/rss');

	if ($username != '') {
		foreach ( $messages->items as $message ) {
			$msg = " ".$message['title']." ";
			$date = strtotime($message['pubdate']);
			$link = $message['link'];
		
			$lifestream[$date]['date'] = $date;	
	  		$lifestream[$date]['type'] = 'jaiku';		  	
			$lifestream[$date]['link'] = $link;

            if ($hyperlinks) { $msg = rss_stream_hyperlinks($msg); }
            if ($jaiku_users)  { $msg = rss_stream_twitter_users($msg); }

			$lifestream[$date]['msg'] =  $msg;
		}
	}
}

function rss_stream_hyperlinks($text) {
    // match protocol://address/path/file.extension?some=variable&another=asf%
    $text = preg_replace("/\s([a-zA-Z]+:\/\/[a-z][a-z0-9\_\.\-]*[a-z]{2,6}[a-zA-Z0-9\/\*\-\?\&\%]*)([\s|\.|\,])/i"," <a href=\"$1\" class=\"twitter-link\">$1</a>$2", $text);
    // match www.something.domain/path/file.extension?some=variable&another=asf%
    $text = preg_replace("/\s(www\.[a-z][a-z0-9\_\.\-]*[a-z]{2,6}[a-zA-Z0-9\/\*\-\?\&\%]*)([\s|\.|\,])/i"," <a href=\"http://$1\" class=\"twitter-link\">$1</a>$2", $text);      
    // match name@address
    $text = preg_replace("/\s([a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]*\@[a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]{2,6})([\s|\.|\,])/i"," <a href=\"mailto://$1\" class=\"twitter-link\">$1</a>$2", $text);    
    return $text;
}

function rss_stream_twitter_users($text) {
       $text = preg_replace('/([\.|\,|\:|\¡|\¿|\>|\{|\(]?)@{1}(\w*)([\.|\,|\:|\!|\?|\>|\}|\)]?)\s/i', "$1<a href=\"http://twitter.com/$2\" class=\"twitter-user\">@$2</a>$3 ", $text);
       return $text;
}    

function rss_stream_delicious($username = '', $tags = false, $filtertag = '', $displaydesc = false ) {
	
	global $lifestream;
	
	$rss = 'http://feeds.delicious.com/v2/rss/'.$username;
	
	if($filtertag != '') { $rss .= '/'.$filtertag; }

	$bookmarks = fetch_rss($rss);

	if ($username != '') {
		foreach ( $bookmarks->items as $bookmark ) {
			$msg = $bookmark['title'];
			$date = strtotime($bookmark['pubdate']);
			$link = $bookmark['link'];
			$desc = $bookmark['description'];
	
			$lifestream[$date]['date'] = $date;
			$lifestream[$date]['type'] = 'delicious';		  	
			$lifestream[$date]['link'] = $link;
			$lifestream[$date]['msg'] = '<a href="'.$link.'" class="delicious-link">'.$msg.'</a>';
			if ($displaydesc && $desc != '') {
				$lifestream[$date]['msg'] .= '<br /><span class="delicious-desc">'.$desc.'</span>';
			}
			
			if ($tags) {
				$lifestream[$date]['msg'] .= '<br /><div class="delicious-tags">';
				$tagged = explode(' ', htmlentities($bookmark['dc']['subject'],ENT_NOQUOTES ,'UTF-8'));
				foreach ($tagged as $tag) {
	       			$lifestream[$date]['msg'] .= '<a href="http://www.delicious.com/tag/'.$tag.'" class="delicious-link-tag">'.$tag.'</a> '; // Puts a link to the tag.
				}
				$lifestream[$date]['msg'] .= '</div>';
			}
	
		}
	}
}

function rss_stream_lastfm($username = '') {
	global $lifestream;

	$songs = fetch_rss('http://ws.audioscrobbler.com/1.0/user/'.$username.'/recenttracks.rss');

	if ($username != '') {
		foreach ( $songs->items as $song ) {
			$msg = $song['title'];
			$date = strtotime($song['pubdate']);
			$link = $song['link'];

			$lifestream[$date]['date'] = $date;
			$lifestream[$date]['type'] = 'lastfm';		  	
			$lifestream[$date]['link'] = $link;
			
		    $lifestream[$date]['msg'] = '<a href="'.$link.'" class="lastfm-link">'.$msg.'</a>'; 

		}

	}
}

function rss_stream_blog($feed = '', $showautor = false) {
	global $lifestream;

	$blog = fetch_rss($feed);

	if ($feed != '') {
		foreach ( $blog->items as $post ) {
			$title = $post['title'];
			$date = strtotime($post['pubdate']);
			$link = $post['link'];
			$autor = $post['dc']['creator'];
			$msg = '<a href="'.$link.'" class="blog-link">'.$title.'</a>';
			if($showautor) $msg .= " by $autor";		

			$lifestream[$date]['date'] = $date;
			$lifestream[$date]['type'] = 'blog';		  	
			$lifestream[$date]['link'] = $link;
			
		    $lifestream[$date]['msg'] = $msg; 

		}

	}
}

function rss_stream_flickr($userid = '') {
	
	global $lifestream;
	
	$rss = 'http://api.flickr.com/services/feeds/photos_public.gne?id=' . $userid . '&format=rss_200';

	$flickr = fetch_rss($rss);

	if ($userid != '') {
		foreach ( $flickr->items as $foto ) {
			$msg = $foto['title'];
			$date = strtotime($foto['pubdate']);
			$link = $foto['link'];
	
			$lifestream[$date]['date'] = $date;
			$lifestream[$date]['type'] = 'flickr';		  	
			$lifestream[$date]['link'] = $link;
			
			$lifestream[$date]['msg'] = '<a href="'.$link.'" class="flickr-link">'.$msg.'</a>';

		}
	}
}

function rss_stream_photobucket($feed = '') {
	global $lifestream;

	$photobucket = fetch_rss($feed);

	if ($feed != '') {
		foreach ( $photobucket->items as $photo ) {
			$title = $photo['title'];
			$date = strtotime($photo['pubdate']);
			$link = htmlspecialchars($photo['link']);
			$msg = '<a href="'.$link.'" class="photobucket-link">'.$title.'</a>';

			$lifestream[$date]['date'] = $date;
			$lifestream[$date]['type'] = 'photobucket';
			$lifestream[$date]['link'] = $link;
			
		    $lifestream[$date]['msg'] = $msg; 

		}
	}
}

function rss_stream_generic($feed = '', $nicon = '0') {
	global $lifestream;

	$generic = fetch_rss($feed);

	if ($feed != '') {
		foreach ( $generic->items as $thing ) {
			$title = $thing['title'];
			$date = strtotime($thing['pubdate']);
			$link = htmlspecialchars($thing['link']);
			$msg = '<a href="'.$link.'" class="generic-link">'.$title.'</a>';

			$lifestream[$date]['date'] = $date;
			$lifestream[$date]['type'] = 'generic'.$nicon;
			$lifestream[$date]['link'] = $link;
			
		  $lifestream[$date]['msg'] = $msg; 

		}
	}
}

function rss_stream_facebook($feed = '') {
	global $lifestream;

	$facebook = fetch_rss($feed);

	if ($feed != '') {
		foreach ( $facebook->items as $status ) {
			$title = $status['title'];
			$date = strtotime($status['pubdate']);
			$link = htmlspecialchars($status['link']);
			$msg = '<a href="'.$link.'" class="facebook-link">'.$title.'</a>';

			$lifestream[$date]['date'] = $date;
			$lifestream[$date]['type'] = 'facebook';		  	
			$lifestream[$date]['link'] = $link;
			
		    $lifestream[$date]['msg'] = $msg; 

		}

	}
}


function rss_stream_pownce($username = '', $reply = true, $linked = true, $diff=0) {

	global $lifestream;
	
	$messages = fetch_rss("http://pownce.com/feeds/public/$username/");
	
	if ($username != '') {
	
		foreach ( $messages->items as $message ) {
			$text = $message['summary'];
			$related = $message['link_related'];
			$event_name = $message['pownce']['event_name'];
			$event_location = $message['event_location'];
			$event_date = strtotime($diff.' hours', strtotime($message['event_date']));
			$replies = $message['pownce']['replies'];
			$replies_text = ($replies == 1) ? ' Reply' : ' Replies';
      $date = strtotime($diff.' hours',strtotime($message['updated']));
      $link = $message['link'];
		
			$msg = $text;
			if ($linked) $msg = rss_stream_hyperlinks(' '.$msg.' ');
			if ($event_name) $msg .= ' <span class="pownce-event">(Event: ' . $event_name;
			if ($event_location && $event_name) $msg .= ' at ' . $event_location;
			if ($event_date && $event_name) $msg .= ' on ' . $event_date;
			if ($event_name) $msg .= ')</span>';
			if ($reply) $msg .= ' <span class="pownce-replies">(<a href="' . $link . '" title="' . $replies . $replies_text . '">' . $replies . '</a>)</span>';
			
			$lifestream[$date]['date'] = $date;
			$lifestream[$date]['type'] = 'pownce';		  	
			$lifestream[$date]['link'] = $link;
			
		    $lifestream[$date]['msg'] = $msg;

		}
	}
}

function RSS_Stream() {
	
	global $lifestream;
	
	if (get_option('RSS_Stream_hour')=='') update_option('RSS_Stream_hour', "g:ia");
	if (get_option('RSS_Stream_date')=='') update_option('RSS_Stream_date', "%B %e");
	 
	if(function_exists('date_default_timezone_set')) date_default_timezone_set(get_option('RSS_Stream_timezone'));
	setlocale(LC_TIME, get_locale());

	if(get_option('RSS_Stream_plurkuser')!='') { rss_stream_plurk(get_option('RSS_Stream_plurkuser')); }
	if(get_option('RSS_Stream_twitteruser')!='') { rss_stream_twitter(get_option('RSS_Stream_twitteruser'), get_option('RSS_Stream_twitterhyperlinks'), get_option('RSS_Stream_twitterreplies')); }
	if(get_option('RSS_Stream_jaikuuser')!='') { rss_stream_jaiku(get_option('RSS_Stream_jaikuuser'), get_option('RSS_Stream_jaikuhyperlinks'), get_option('RSS_Stream_jaikureplies')); }
	if(get_option('RSS_Stream_delicioususer')!='') { rss_stream_delicious(get_option('RSS_Stream_delicioususer'), get_option('RSS_Stream_deliciousshowtags'), get_option('RSS_Stream_deliciousfiltertag'), get_option('RSS_Stream_deliciousshowdesc')); }
	if(get_option('RSS_Stream_lastfmuser')!='') { rss_stream_lastfm(get_option('RSS_Stream_lastfmuser')); }
	if(get_option('RSS_Stream_blogfeed')!='') { rss_stream_blog(get_option('RSS_Stream_blogfeed'), get_option('RSS_Stream_blogshowautor')); }
	if(get_option('RSS_Stream_flickruser')!='') { rss_stream_flickr(get_option('RSS_Stream_flickruser')); }
	if(get_option('RSS_Stream_photobucketfeed')!='') { rss_stream_photobucket(get_option('RSS_Stream_photobucketfeed')); }
	if(get_option('RSS_Stream_facebookfeed')!='') { rss_stream_facebook(get_option('RSS_Stream_facebookfeed')); }
	if(get_option('RSS_Stream_pownceuser')!='') { rss_stream_pownce(get_option('RSS_Stream_pownceuser'),  get_option('RSS_Stream_pownceshowreplies'), get_option('RSS_Stream_powncelink'), get_option('RSS_Stream_powncediff')); }
  if(get_option('RSS_Stream_genericfeednumber')!='0') { 
    for($i=1;$i<=get_option('RSS_Stream_genericfeednumber');$i++) {
        if (get_option('RSS_Stream_genericfeed'.$i) != '') { rss_stream_generic(get_option('RSS_Stream_genericfeed'.$i), $i);}
      }
  }	
	
?>

<div id="RSS_Stream">
<table class="hcalendar RSSS_table">
<?php
krsort( $lifestream );

$timelapse = get_option('RSS_Stream_timelapse');
$expiration  = mktime(0, 0, 0, date("m")  , date("d")-$timelapse, date("Y"));

$day = '';
if(get_option('RSS_Stream_twitteruser')!='' || get_option('RSS_Stream_jaikuuser')!='' || get_option('RSS_Stream_delicioususer')!='' || get_option('RSS_Stream_lastfmuser')!='' || get_option('RSS_Stream_blogfeed')!='' || get_option('RSS_Stream_genericfeednumber')!='0' || get_option('RSS_Stream_flickruser')!='' || get_option('RSS_Stream_photobucketfeed')!='' || get_option('RSS_Stream_facebookfeed')!='' || get_option('RSS_Stream_pownceuser')!='' || get_option('RSS_Stream_plurkuser')!='') {
	foreach ( $lifestream as $timestamp => $item ) {
        if ($timestamp >= $expiration) {
	    $this_day = ucfirst(htmlentities(strftime(get_option('RSS_Stream_date'),$timestamp )));
	    	if ( $day != $this_day ) {
	?>
	<tr>
	<th colspan="3">
	<h2 class="RSSS_date"><?php echo $this_day; ?></h2>
	</th>
	</tr>
	<?php $day = $this_day; } ?>
	<tr>
	<td class="RSSS_icon">
		<a href="<?php echo $item[link]; ?>" title="<?php echo $item[type]; ?>"><img src="<?php echo RSSS_URL(). '/images/'. $item["type"] . '.png'; ?>" alt="<?php echo $item["type"]; ?>" /></a>
	</td>
	<td class="RSSS_hour RSSS_<?php  echo $item["type"]; ?>">
	    	<abbr class="dtstart" title="<?php echo date("c",$timestamp); ?>"><?php echo date(get_option('RSS_Stream_hour'), $timestamp); ?></abbr>
	</td>
	<td class="RSSS_msg">
	    <?php if(get_option('RSS_Stream_UTF8')) { echo utf8_encode($item["msg"]); } else { echo $item["msg"]; } ?>
	</td>
	</tr>
	<?php
    
    }
	}
}
?>
</table>
<p class="RSSS_credits">By <a href="http://rick.jinlabs.com/code/rss-stream" title="RSS Stream">RSS Stream</a></p>
</div>
<?php	
}

function RSS_Stream_subpanel() {

  load_plugin_textdomain('RSS-Stream', 'wp-content/plugins/rss-stream/locales');
	date_default_timezone_set(get_option('RSS_Stream_timezone'));
	setlocale(LC_TIME, get_locale());
	   		
     if (isset($_POST['update_RSS_Stream'])) {
       $option_RSS_Stream_date = $_POST['RSS_Stream_date']; 
       $option_RSS_Stream_hour = $_POST['RSS_Stream_hour']; 
       $option_RSS_Stream_timezone = $_POST['RSS_Stream_timezone'];  
       $option_RSS_Stream_UTF8 = $_POST['RSS_Stream_UTF8'];  
       $option_RSS_Stream_timelapse = $_POST['RSS_Stream_timelapse']; 
     
       update_option('RSS_Stream_date', $option_RSS_Stream_date);
       update_option('RSS_Stream_hour', $option_RSS_Stream_hour);
       update_option('RSS_Stream_timezone', $option_RSS_Stream_timezone);
       update_option('RSS_Stream_UTF8', $option_RSS_Stream_UTF8);
       update_option('RSS_Stream_timelapse', $option_RSS_Stream_timelapse);
       
       $option_RSS_Stream_plurkuser = $_POST['RSS_Stream_plurkuser'];
       update_option('RSS_Stream_plurkuser', $option_RSS_Stream_plurkuser);
       
       $option_RSS_Stream_twitteruser = $_POST['RSS_Stream_twitteruser'];
       $option_RSS_Stream_twitterhyperlinks = $_POST['RSS_Stream_twitterhyperlinks'];
       $option_RSS_Stream_twitterreplies = $_POST['RSS_Stream_twitterreplies'];   	          
       update_option('RSS_Stream_twitteruser', $option_RSS_Stream_twitteruser);
       update_option('RSS_Stream_twitterhyperlinks', $option_RSS_Stream_twitterhyperlinks);
       update_option('RSS_Stream_twitterreplies', $option_RSS_Stream_twitterreplies);

       $option_RSS_Stream_jaikuuser = $_POST['RSS_Stream_jaikuuser'];
       $option_RSS_Stream_jaikuhyperlinks = $_POST['RSS_Stream_jaikuhyperlinks'];
       $option_RSS_Stream_jaikureplies = $_POST['RSS_Stream_jaikureplies'];   	          
       update_option('RSS_Stream_jaikuuser', $option_RSS_Stream_jaikuuser);
       update_option('RSS_Stream_jaikuhyperlinks', $option_RSS_Stream_jaikuhyperlinks);
       update_option('RSS_Stream_jaikureplies', $option_RSS_Stream_jaikureplies);
       
       $option_RSS_Stream_delicioususer = $_POST['RSS_Stream_delicioususer'];
       $option_RSS_Stream_deliciousshowtags = $_POST['RSS_Stream_deliciousshowtags'];
       $option_RSS_Stream_deliciousfiltertag = $_POST['RSS_Stream_deliciousfiltertag'];
       $option_RSS_Stream_deliciousshowdesc = $_POST['RSS_Stream_deliciousshowdesc'];   	          
       update_option('RSS_Stream_delicioususer', $option_RSS_Stream_delicioususer);
       update_option('RSS_Stream_deliciousshowtags', $option_RSS_Stream_deliciousshowtags);
       update_option('RSS_Stream_deliciousfiltertag', $option_RSS_Stream_deliciousfiltertag);
       update_option('RSS_Stream_deliciousshowdesc', $option_RSS_Stream_deliciousshowdesc);

       $option_RSS_Stream_lastfmuser = $_POST['RSS_Stream_lastfmuser'];         
       update_option('RSS_Stream_lastfmuser', $option_RSS_Stream_lastfmuser);

       $option_RSS_Stream_flickruser = $_POST['RSS_Stream_flickruser'];         
       update_option('RSS_Stream_flickruser', $option_RSS_Stream_flickruser);

      $option_RSS_Stream_photobucketfeed = $_POST['RSS_Stream_photobucketfeed'];         
       update_option('RSS_Stream_photobucketfeed', $option_RSS_Stream_photobucketfeed);

       $option_RSS_Stream_blogfeed = $_POST['RSS_Stream_blogfeed'];         
       $option_RSS_Stream_blogshowautor = $_POST['RSS_Stream_blogshowautor'];         
       update_option('RSS_Stream_blogfeed', $option_RSS_Stream_blogfeed);
       update_option('RSS_Stream_blogshowautor', $option_RSS_Stream_blogshowautor);
     
       $option_RSS_Stream_facebookfeed = $_POST['RSS_Stream_facebookfeed'];         
       update_option('RSS_Stream_facebookfeed', $option_RSS_Stream_facebookfeed);

       $option_RSS_Stream_pownceuser = $_POST['RSS_Stream_pownceuser'];         
       $option_RSS_Stream_powncelink = $_POST['RSS_Stream_powncelink'];
       $option_RSS_Stream_pownceshowreplies = $_POST['RSS_Stream_pownceshowreplies'];         
       $option_RSS_Stream_powncediff = $_POST['RSS_Stream_powncediff'];         
       update_option('RSS_Stream_powncediff', $option_RSS_Stream_powncediff);
       update_option('RSS_Stream_pownceuser', $option_RSS_Stream_pownceuser);	      
       update_option('RSS_Stream_powncelink', $option_RSS_Stream_powncelink);
       update_option('RSS_Stream_pownceshowreplies', $option_RSS_Stream_pownceshowreplies);

       $option_RSS_Stream_genericfeednumber = $_POST['RSS_Stream_genericfeednumber'];         
       update_option('RSS_Stream_genericfeednumber', $option_RSS_Stream_genericfeednumber);
       
       for($i=1;$i<=get_option('RSS_Stream_genericfeednumber');$i++) {
          $option_RSS_Stream_genericfeed[$i] = $_POST['RSS_Stream_genericfeed'.$i];         
          update_option('RSS_Stream_genericfeed'.$i, $option_RSS_Stream_genericfeed[$i]);     
       }
       
       ?> <div class="updated"><p>RSS Stream options changes saved.</p></div> <?php
     }

	?>
	
	<div class="wrap">
		<h2><?PHP _e('RSS Stream Options', 'RSS-Stream'); ?></h2>
		<form method="post">
				<p><div class="submit"><input type="submit" name="update_RSS_Stream" id="update_RSS_Stream" value="<?php _e('Update RSS Stream Options', 'RSS-Stream') ?>"  style="font-weight:bold;" /></div></p>
		
		<fieldset class="options">
		<legend><?PHP _e("General Options", 'RSS-Stream'); ?></legend>
		<table>
		 <tr>
		  <td><p><strong><label for="RSS_Stream_date"><?PHP _e("Date format", 'RSS-Stream'); ?></label>:</strong></p></td>
	    <td><input name="RSS_Stream_date" type="text" id="RSS_Stream_date" value="<?php echo get_option('RSS_Stream_date'); ?>" size="20" />
        		PHP <a href="http://es.php.net/strftime">strftime()</a> sintax. Ex. <?php  echo htmlentities(strftime(get_option('RSS_Stream_date'))); ?></p>
      </td>
     </tr>
		 <tr>
		  <td><p><strong><label for="RSS_Stream_hour"><?PHP _e("Hour format", 'RSS-Stream'); ?></label>:</strong></p></td>
	    <td><input name="RSS_Stream_hour" type="text" id="RSS_Stream_hour" value="<?php echo get_option('RSS_Stream_hour'); ?>" size="20" />
        		PHP <a href="http://es.php.net/date">date()</a> sintax. Ex. <?php  echo date(get_option('RSS_Stream_hour')); ?></p>
      </td>
     </tr>
         <tr>
          <td><p><strong><?PHP _e("Timezone", 'RSS-Stream'); ?>:</strong></p></td>
          <td>
        	<select name="RSS_Stream_timezone" id="RSS_Stream_timezone">
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT-9') { echo 'selected'; } ?> value="Etc/GMT-9">GMT +9</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT-8') { echo 'selected'; } ?> value="Etc/GMT-8">GMT +8</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT-7') { echo 'selected'; } ?> value="Etc/GMT-7">GMT +7</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT-6') { echo 'selected'; } ?> value="Etc/GMT-6">GMT +6</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT-5') { echo 'selected'; } ?> value="Etc/GMT-5">GMT +5</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT-4') { echo 'selected'; } ?> value="Etc/GMT-4">GMT +4</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT-3') { echo 'selected'; } ?> value="Etc/GMT-3">GMT +3</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT-2') { echo 'selected'; } ?> value="Etc/GMT-2">GMT +2</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT-1') { echo 'selected'; } ?> value="Etc/GMT-1">GMT +1</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT0') { echo 'selected'; } ?> value="Etc/GMT0">GMT</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT+1') { echo 'selected'; } ?> value="Etc/GMT+1">GMT -1</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT+2') { echo 'selected'; } ?> value="Etc/GMT+2">GMT -2</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT+3') { echo 'selected'; } ?> value="Etc/GMT+3">GMT -3</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT+4') { echo 'selected'; } ?> value="Etc/GMT+4">GMT -4</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT+5') { echo 'selected'; } ?> value="Etc/GMT+5">GMT -5</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT+6') { echo 'selected'; } ?> value="Etc/GMT+6">GMT -6</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT+7') { echo 'selected'; } ?> value="Etc/GMT+7">GMT -7</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT+8') { echo 'selected'; } ?> value="Etc/GMT+8">GMT -8</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT+9') { echo 'selected'; } ?> value="Etc/GMT+9">GMT -9</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT+10') { echo 'selected'; } ?> value="Etc/GMT+10">GMT -10</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT+11') { echo 'selected'; } ?> value="Etc/GMT+11">GMT -11</option>
            <option <?php if(get_option('RSS_Stream_timezone') == 'Etc/GMT+12') { echo 'selected'; } ?> value="Etc/GMT+12">GMT -12</option>

                            
		      </select>
           </td> 
         </tr>

        		<tr>
          <td><p><strong><?PHP _e("Time lapse:", 'RSS-Stream'); ?></strong></p></td>
          <td>
        	<select name="RSS_Stream_timelapse" id="RSS_Stream_timelapse">
	            <option <?php if(get_option('RSS_Stream_timelapse') == '0') { echo 'selected'; } ?> value="0"><?PHP _e("Today", 'RSS-Stream'); ?></option>	            
              <option <?php if(get_option('RSS_Stream_timelapse') == '1') { echo 'selected'; } ?> value="1">1</option>
	            <option <?php if(get_option('RSS_Stream_timelapse') == '2') { echo 'selected'; } ?> value="2">2</option>
	            <option <?php if(get_option('RSS_Stream_timelapse') == '3') { echo 'selected'; } ?> value="3">3</option>
	            <option <?php if(get_option('RSS_Stream_timelapse') == '4') { echo 'selected'; } ?> value="4">4</option>
	            <option <?php if(get_option('RSS_Stream_timelapse') == '5') { echo 'selected'; } ?> value="5">5</option>
	            <option <?php if(get_option('RSS_Stream_timelapse') == '6') { echo 'selected'; } ?> value="6">6</option>
	            <option <?php if(get_option('RSS_Stream_timelapse') == '7') { echo 'selected'; } ?> value="7">7</option>
	            <option <?php if(get_option('RSS_Stream_timelapse') == '8') { echo 'selected'; } ?> value="8">8</option>
	            <option <?php if(get_option('RSS_Stream_timelapse') == '9') { echo 'selected'; } ?> value="9">9</option>
	            <option <?php if(get_option('RSS_Stream_timelapse') == '10') { echo 'selected'; } ?> value="10">10</option>                
		    </select>
           </td> 
         </tr>
         
           <tr>
            <td><p><input name="RSS_Stream_UTF8" type="checkbox" id="RSS_Stream_UTF8" value="true" <?php if(get_option('RSS_Stream_UTF8') == 'true') { echo 'checked="checked"'; } ?> />  <label for="RSS_Stream_UTF8"><strong><?PHP _e("Force UTF8 encoding", 'RSS-Stream'); ?></strong></label></p></td>
           </tr>		 

         </table>

        </fieldset>


		<fieldset class="options"> 
        <legend>Plurk</legend>
          <table>
               <tr>
                <td><p><strong><label for="RSS_Stream_plurkuser"><?PHP _e("User", 'RSS-Stream'); ?></label></strong></p></td>
                <td><p><input name="RSS_Stream_plurkuser" type="text" id="RSS_Stream_plurkuser" value="<?php echo get_option('RSS_Stream_plurkuser'); ?>" size="25" /></p></td>
               </tr>
          </table>
    </fieldset>
    
    		    
		<fieldset class="options"> 
        <legend>Twitter</legend>
          <table>
               <tr>
                <td><p><strong><label for="RSS_Stream_twitteruser"><?PHP _e("User", 'RSS-Stream'); ?></label></strong></p></td>
                <td><p><input name="RSS_Stream_twitteruser" type="text" id="RSS_Stream_twitteruser" value="<?php echo get_option('RSS_Stream_twitteruser'); ?>" size="25" /></p></td>
               </tr>
           <tr>
            <td><p><input name="RSS_Stream_twitterhyperlinks" type="checkbox" id="RSS_Stream_twitterhyperlinks" value="true" <?php if(get_option('RSS_Stream_twitterhyperlinks') == 'true') { echo 'checked="checked"'; } ?> />  <label for="RSS_Stream_twitterhyperlinks"><strong><?PHP _e("Discover hyperlinks", 'RSS-Stream'); ?></strong></label></p></td>
           </tr>
           <tr>
            <td><p><input name="RSS_Stream_twitterreplies" type="checkbox" id="RSS_Stream_twitterreplies" value="true" <?php if(get_option('RSS_Stream_twitterreplies') == 'true') { echo 'checked="checked"'; } ?> />  <label for="RSS_Stream_twitterreplies"><strong><?PHP _e("Discover @replies", 'RSS-Stream'); ?></strong></label></p></td>
           </tr>		 
              </table>
    </fieldset>
    
    <fieldset class="options"> 
        <legend>Jaiku</legend>
          <table>
               <tr>
                <td><p><strong><label for="RSS_Stream_jaikuuser"><?PHP _e("User", 'RSS-Stream'); ?></label></strong></p></td>
                <td><p><input name="RSS_Stream_jaikuuser" type="text" id="RSS_Stream_jaikuuser" value="<?php echo get_option('RSS_Stream_jaikuuser'); ?>" size="25" /></p></td>
               </tr>
           <tr>
            <td><p><input name="RSS_Stream_jaikuhyperlinks" type="checkbox" id="RSS_Stream_jaikuhyperlinks" value="true" <?php if(get_option('RSS_Stream_jaikuhyperlinks') == 'true') { echo 'checked="checked"'; } ?> />  <label for="RSS_Stream_jaikuhyperlinks"><strong><?PHP _e("Discover hyperlinks", 'RSS-Stream'); ?></strong></label></p></td>
           </tr>
           <tr>
            <td><p><input name="RSS_Stream_jaikureplies" type="checkbox" id="RSS_Stream_jaikureplies" value="true" <?php if(get_option('RSS_Stream_jaikureplies') == 'true') { echo 'checked="checked"'; } ?> />  <label for="RSS_Stream_jaikureplies"><strong><?PHP _e("Discover @replies", 'RSS-Stream'); ?></strong></label></p></td>
           </tr>		 
              </table>
    </fieldset>
    
		<fieldset class="options"> 
        <legend>del.icio.us</legend>
          <table>
               <tr>
                <td><p><strong><label for="RSS_Stream_delicioususer"><?PHP _e("User", 'RSS-Stream'); ?>:</label></strong></p></td>
                <td><p><input name="RSS_Stream_delicioususer" type="text" id="RSS_Stream_delicioususer" value="<?php echo get_option('RSS_Stream_delicioususer'); ?>" size="25" /></p></td>
               </tr>
           <tr>
            <td><p><input name="RSS_Stream_deliciousshowtags" type="checkbox" id="RSS_Stream_deliciousshowtags" value="true" <?php if(get_option('RSS_Stream_deliciousshowtags') == 'true') { echo 'checked="checked"'; } ?> />  <label for="RSS_Stream_deliciousshowtags"><strong><?PHP _e("Show tags", 'RSS-Stream'); ?></strong></label></p></td>
           </tr>	         
               <tr>
                <td><p><strong><label for="RSS_Stream_deliciousfiltertag"><?PHP _e("Filter tag(s)", 'RSS-Stream'); ?>:</label></strong></td>
                <td><input name="RSS_Stream_deliciousfiltertag" type="text" id="RSS_Stream_deliciousfiltertag" value="<?php echo get_option('RSS_Stream_deliciousfiltertag'); ?>" size="10" /> <em>i.e. cat+dog+fish</em></p></td>
               </tr>
           <tr>
            <td><p><input name="RSS_Stream_deliciousshowdesc" type="checkbox" id="RSS_Stream_deliciousshowdesc" value="true" <?php if(get_option('RSS_Stream_deliciousshowdesc') == 'true') { echo 'checked="checked"'; } ?> />  <label for="RSS_Stream_deliciousshowdesc"><strong><?PHP _e("Show bookmark's descriptions", 'RSS-Stream'); ?></strong></label></p></td>
           </tr>	 
              </table>
    </fieldset>    

		<fieldset class="options"> 
        <legend>Last.fm</legend>
		<table>
         <tr>
          <td><p><strong><label for="RSS_Stream_lastfmuser"><?PHP _e("User", 'RSS-Stream'); ?>:</label></strong></p></td>
          <td><input name="RSS_Stream_lastfmuser" type="text" id="RSS_Stream_lastfmuser" value="<?php echo get_option('RSS_Stream_lastfmuser'); ?>" size="25" /></p>
         </tr>
        </table>
    </fieldset>   

		<fieldset class="options"> 
        <legend>flickr</legend>
		<table>
         <tr>
          <td><p><strong><label for="RSS_Stream_flickruser"><?PHP _e("ID", 'RSS-Stream'); ?>:</label></strong></p></td>
          <td><p><input name="RSS_Stream_flickruser" type="text" id="RSS_Stream_flickruser" value="<?php echo get_option('RSS_Stream_flickruser'); ?>" size="25" /> <?PHP _e('Use <a href="http://idgettr.com">idGettr</a> to find your id.', 'RSS-Stream'); ?></p></td>
         </tr>
        </table>
    </fieldset>   	
    
		<fieldset class="options"> 
        <legend>Photobucket</legend>
		<table>
         <tr>
          <td><p><strong><label for="RSS_Stream_photobucketfeed"><?PHP _e("Feed", 'RSS-Stream'); ?>:</label></strong></p></td>
          <td><p><input name="RSS_Stream_photobucketfeed" type="text" id="RSS_Stream_photobucketfeed" value="<?php echo get_option('RSS_Stream_photobucketfeed'); ?>" size="75" /> </p></td>
         </tr>
        </table>
    </fieldset>
    
		<fieldset class="options"> 
        <legend>Facebook</legend>
		<table>
         <tr>
          <td><p><strong><label for="RSS_Stream_facebookfeed"><?PHP _e("Feed", 'RSS-Stream'); ?>:</label></strong></p></td>
          <td><p><input name="RSS_Stream_facebookfeed" type="text" id="RSS_Stream_facebookfeed" value="<?php echo get_option('RSS_Stream_facebookfeed'); ?>" size="75" /></p></td>
         </tr>   
        </table>
    </fieldset> 

		<fieldset class="options"> 
        <legend>Pownce</legend>
		<table>
         <tr>
          <td><p><strong><label for="RSS_Stream_pownceuser"><?PHP _e("User", 'RSS-Stream'); ?>:</label></strong></p></td>
          <td><p><input name="RSS_Stream_pownceuser" type="text" id="RSS_Stream_pownceuser" value="<?php echo get_option('RSS_Stream_pownceuser'); ?>" size="25" /></p></td>
         </tr>
		 <tr>
		  <td><p><input name="RSS_Stream_powncelink" type="checkbox" id="RSS_Stream_powncelink" value="true" <?php if(get_option('RSS_Stream_powncelink') == 'true') { echo 'checked="checked"'; } ?> />  <label for="RSS_Stream_powncelink"><strong><?PHP _e("Discover hyperlinks", 'RSS-Stream'); ?></strong></label></p></td>
		 </tr>         
		 <tr>
		  <td><p><input name="RSS_Stream_pownceshowreplies" type="checkbox" id="RSS_Stream_pownceshowreplies" value="true" <?php if(get_option('RSS_Stream_pownceshowreplies') == 'true') { echo 'checked="checked"'; } ?> />  <label for="RSS_Stream_pownceshowreplies"><strong><?PHP _e("Show replies", 'RSS-Stream'); ?></strong></label></p></td>
		 </tr>
		 
		<tr>
          <td><p><strong><?PHP _e("Time diff", 'RSS-Stream'); ?></strong></p></td>
          <td>
        	<select name="RSS_Stream_powncediff" id="RSS_Stream_powncediff">
	            <option <?php if(get_option('RSS_Stream_powncediff') == '-9') { echo 'selected'; } ?> value="-9">+9</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '-8') { echo 'selected'; } ?> value="-8">+8</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '-7') { echo 'selected'; } ?> value="-7">+7</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '-6') { echo 'selected'; } ?> value="-6">+6</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '-5') { echo 'selected'; } ?> value="-5">+5</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '-4') { echo 'selected'; } ?> value="-4">+4</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '-3') { echo 'selected'; } ?> value="-3">+3</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '-2') { echo 'selected'; } ?> value="-2">+2</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '-1') { echo 'selected'; } ?> value="-1">+1</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '0') { echo 'selected'; } ?> value="0">0</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '+1') { echo 'selected'; } ?> value="+1">-1</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '+2') { echo 'selected'; } ?> value="+2">-2</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '+3') { echo 'selected'; } ?> value="+3">-3</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '+4') { echo 'selected'; } ?> value="+4">-4</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '+5') { echo 'selected'; } ?> value="+5">-5</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '+6') { echo 'selected'; } ?> value="+6">-6</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '+7') { echo 'selected'; } ?> value="+7">-7</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '+8') { echo 'selected'; } ?> value="+8">-8</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '+9') { echo 'selected'; } ?> value="+9">-9</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '+10') { echo 'selected'; } ?> value="+10">-10</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '+11') { echo 'selected'; } ?> value="+11">-11</option>
	            <option <?php if(get_option('RSS_Stream_powncediff') == '+12') { echo 'selected'; } ?> value="+12">-12</option>                        
		    </select> <?PHP _e("Hours", 'RSS-Stream'); ?>
           </td> 
         </tr>
		 
		  
        </table>
    </fieldset> 

		<fieldset class="options"> 
        <legend>Blog</legend>
		<table>
         <tr>
          <td><p><strong><label for="RSS_Stream_blogfeed"><?PHP _e("Feed", 'RSS-Stream'); ?>:</label></strong></p></td>
          <td><p><input name="RSS_Stream_blogfeed" type="text" id="RSS_Stream_blogfeed" value="<?php echo get_option('RSS_Stream_blogfeed'); ?>" size="75" /></p></td>
         </tr>
		 <tr>
		  <td><p><input name="RSS_Stream_blogshowautor" type="checkbox" id="RSS_Stream_blogshowautor" value="true" <?php if(get_option('RSS_Stream_blogshowautor') == 'true') { echo 'checked="checked"'; } ?> />  <label for="RSS_Stream_blogshowautor"><strong><?PHP _e("Show post autor", 'RSS-Stream'); ?></strong></label></p></td>
		 </tr>         
        </table>
    </fieldset> 

			<fieldset class="options"> 
        <legend>Generic RSS Feed</legend>
        <table>
        		<tr>
          <td><p><strong><?PHP _e("How many generic feeds?", 'RSS-Stream'); ?></strong></p></td>
          <td>
        	<select name="RSS_Stream_genericfeednumber" id="RSS_Stream_genericfeednumber">
	            <option <?php if(get_option('RSS_Stream_genericfeednumber') == '0') { echo 'selected'; } ?> value="0"><?PHP _e("None", 'RSS-Stream'); ?></option>
              <option <?php if(get_option('RSS_Stream_genericfeednumber') == '1') { echo 'selected'; } ?> value="1">1</option>
	            <option <?php if(get_option('RSS_Stream_genericfeednumber') == '2') { echo 'selected'; } ?> value="2">2</option>
	            <option <?php if(get_option('RSS_Stream_genericfeednumber') == '3') { echo 'selected'; } ?> value="3">3</option>
	            <option <?php if(get_option('RSS_Stream_genericfeednumber') == '4') { echo 'selected'; } ?> value="4">4</option>
	            <option <?php if(get_option('RSS_Stream_genericfeednumber') == '5') { echo 'selected'; } ?> value="5">5</option>
	            <option <?php if(get_option('RSS_Stream_genericfeednumber') == '6') { echo 'selected'; } ?> value="6">6</option>
	            <option <?php if(get_option('RSS_Stream_genericfeednumber') == '7') { echo 'selected'; } ?> value="7">7</option>
	            <option <?php if(get_option('RSS_Stream_genericfeednumber') == '8') { echo 'selected'; } ?> value="8">8</option>
	            <option <?php if(get_option('RSS_Stream_genericfeednumber') == '9') { echo 'selected'; } ?> value="9">9</option>
	            <option <?php if(get_option('RSS_Stream_genericfeednumber') == '10') { echo 'selected'; } ?> value="10">10</option>                
		    </select>
           </td> 
         </tr>
        <?PHP for($i=1;$i<=get_option('RSS_Stream_genericfeednumber');$i++) { ?>
         <tr>
          <td><p><strong><label for="RSS_Stream_genericfeed<?PHP echo $i; ?>"><?PHP _e("Feed", 'RSS-Stream'); ?> #<?PHP echo $i; ?> :</label></strong></p></td>
          <td><p><input name="RSS_Stream_genericfeed<?PHP echo $i; ?>" type="text" id="RSS_Stream_genericfeed<?PHP echo $i; ?>" value="<?php echo get_option('RSS_Stream_genericfeed'.$i); ?>" size="75" /></p></td>
        </tr>
        <?PHP } ?>
        </table>
    </fieldset> 
    
		<p><div class="submit"><input type="submit" name="update_RSS_Stream" id="update_RSS_Stream" value="<?php _e('Update RSS Stream Options', 'RSS-Stream') ?>"  style="font-weight:bold;" /></div></p>
    </form>  
    </div>

<?php } // end flickrRSS_subpanel()

function RSSS_admin_menu() {
   if (function_exists('add_options_page')) {
        add_options_page('RSS Stream Options Page', 'RSS Stream', 8, basename(__FILE__), 'RSS_Stream_subpanel');
        }
}

function RSS_Stream_header() {
  
  	echo '<link rel="stylesheet" type="text/css" media="screen" href="'.RSSS_URL().'/rss-stream.css" />';
}

add_action('admin_menu', 'RSSS_admin_menu');

add_action('wp_head', 'RSS_Stream_header');
  
register_activation_hook( __FILE__, 'RSS_Stream_Init');

function RSS_Stream_Init(){
	add_option("RSS_Stream_date", "%B %e");
	add_option("RSS_Stream_hour", "g:ia");
	add_option("RSS_Stream_blogfeed", bloginfo('rss2_url'));
	add_option("RSS_Stream_genericfeednumber", '0');
	add_option("RSS_Stream_timelapse", '10');
}