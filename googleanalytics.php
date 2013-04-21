<?php
/*
Plugin Name: Google Analytics
Plugin URI: http://boakes.org/analytics
Description: Wtyczka ułatwiająca dodanie funkcji Google Analytics do Twojego bloga WordPress lub forum BBPress.
Author: Rich Boakes
Contributor: Tomasz Kuter
Version: 0.70
Author URI: http://boakes.org
Contributor URI: https://github.com/evolic/wp-google-analytics
Translator: Bartosz "brt12" Sobczyk
Translator URI: http://www.brt12.eu
License: GPL

0.30 - * added external link tracking
0.40 - * added code comments and re-jigged so
         others can more easily contribute
	    * added version comment
		 * outbound comment-author links are
		   also now tracked
0.50 - * Switched to a format which should
         behave nicely when run under PHP5 -
			testing by People with PHP5 is needed!
0.51 - * Changed javaScript to use onclick (not
         onClick) for validation purposes.
0.60 - * Now works with bbPress - just set the UAString
         to the one that appears in your analytics
			script.
0.61 - * If the plugin is installed in wordpress AND you
         have BBPress configured to load wordpress, then
         the plugin will (when also installed in bbpress)
         use the UAString from the WPDB.  If you want to use
         a different key, set $wp_uastring_takes_precedence
         to false;
0.65 - * Inclusion of UDN based on $_SERVER['HTTP_HOST']
         for WPMU goodness - set $includeUDN to true to enable
			this capability.
0.66 - * Moved the script to wp_footer so page loads are not
         dependent on the speed of the google server.
0.67 - * Security fix for multiple author blogs.
0.68 - * Allow longer UA strings.
0.70 - * Added support for new tracking code: www.google-analytics.com/analytics.js
*/

$uastring = "UA-00000-0";
$wp_uastring_takes_precedence = true;
$includeUDN = false;

/*
 * Admin User Interface
 */
if ( ! class_exists( 'GA_Admin' ) ) {

	class GA_Admin {

		function add_config_page() {
			global $wpdb;
			if ( function_exists('add_submenu_page') ) {
				add_submenu_page('plugins.php', 'Google Analytics Configuration', 'Google Analytics', 1, basename(__FILE__), array('GA_Admin','config_page'));
			}
		} // end add_GA_config_page()

		function config_page() {
			global $uastring;
			if ( isset($_POST['submit']) ) {
				if (!current_user_can('manage_options')) die(__('Nie możesz zmienić kodu UA.'));
				check_admin_referer();
				$uastring = $_POST['uastring'];
				update_option('analytics_uastring', $uastring);
			}
			$mulch = ($uastring=""?"##-#####-#":$uastring);

			?>
			<div class="wrap">
				<h2>KonfiguracjaGoogle Analytics</h2>
				<p>Google Analytics to usługa statystyk dostarczana za darmo przez Google. Ta wtyczka ułatwia proces wstawiania <em>podstawowego</em> kodu Google Analytics na Twoim blogu, tak że nie musisz edytować żadnych plików PHP. Jeżeli nie masz jeszcze konta na Google Analytics, możesz je założyć na stronie <a href="https://www.google.com/analytics/home/">analytics.google.com</a>.</p>

				<p>W interfejsie Google gdy wybierasz opcję "Dodaj profil strony", ukaże Ci sie kod JavaScript, który należy umieścić na stronie. W tym kodzie znajduje się fragment, który identyfikuje stronę, która własnie została przez Ciebie dodana (jest on <strong>pogrubiony</strong> w przykładzie poniżej).</p>
				<tt>&lt;script&gt;<br />(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){<br />(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),<br />m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)<br />})(window,document,'script','//www.google-analytics.com/analytics.js','ga');<br /><br />ga('create', '<strong><?php echo($mulch);?></strong>', 'eu5.org');<br />ga('send', 'pageview');<br />&lt;/script&gt;<br /></tt>

				<p>Gdy już wpiszesz swój kod UA (użytkownika), wówczas twoje strony będą śledzone przez Google Analytics.</p>

				<form action="" method="post" id="analytics-conf" style="margin: auto; width: 25em; ">
					<h3><label for="uastring">Analytics User Account</label></h3>
					<p><input id="uastring" name="uastring" type="text" size="20" maxlength="40" value="<?php echo get_option('analytics_uastring'); ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;" /></p>
					<p class="submit"><input type="submit" name="submit" value="Update UA String &raquo;" /></p>
				</form>

			</div>
			<?php
			$opt = get_option('analytics_uastring');
			if (isset($opt)) {
				if ($opt == "") {
					add_action('admin_footer', array('GA_Admin','warning'));
				} else {
					if (isset($_POST['submit'])) {
						add_action('admin_footer', array('GA_Admin','success'));
					}
				}
			} else {
				add_action('admin_footer', array('GA_Admin','warning'));
			}

		} // end config_page()

		function success() {
			echo "
			<div id='analytics-warning' class='updated fade-ff0000'><p><strong>Gratulacje! Właśnie aktywowano Twoje konto na Google Analytics. Jeśli chcesz, przyjrzyj się źródłu Twojej strony i poszukaj w nim słowa 'google-analytics.com' aby zaobserwować zmiany.</p></div>
			<style type='text/css'>
			#adminmenu { margin-bottom: 7em; }
			#analytics-warning { position: absolute; top: 7em; }
			</style>";
		} // end analytics_warning()

		function warning() {
			echo "
			<div id='analytics-warning' class='updated fade-ff0000'><p><strong>Usługa Google Analytics nie została aktywowana.</strong> Musisz <a href='plugins.php?page=googleanalytics.php'>wpisać swój kod UA</a> aby magia zaczęła działać :)</p></div>
			<style type='text/css'>
			#adminmenu { margin-bottom: 6em; }
			#analytics-warning { position: absolute; top: 7em; }
			</style>";
		} // end analytics_warning()

	} // end class GA_Admin

} //endif


/**
 * Code that actually inserts stuff into pages.
 */
if ( ! class_exists( 'GA_Filter' ) ) {
	class GA_Filter {

		function analytics_cats() {
      	global $dir, $post;
		      foreach (get_the_category($post->ID) as $cat) {
      		 	$profile = get_option('analytics_'.$cat->category_nicename);
		         if ($profile != "") {
						return $profile;
					}
      		}
			return '';
		} //end analytics_cats()

		function spool_analytics() {
			global $uastring, $post, $version;

			echo("\n\n<!--\nGoogle Analytics Plugin for Wordpress and BBPress\nhttp://boakes.org/analytics\n-->\n");

			// check if there's a post level profile
			// and if so, use it.
			if (function_exists("get_post_meta")) {
				$ua = get_post_meta($post->ID, $uakey);
				if ($ua[0] != "") {
					GA_Filter::spool_this($ua);
					return;
				}
			}

			// check if any of the categories this post
			// belongs to have a profile, and if so
			// use the first one that's found
			//
			// TO DO switch on when maintenence UI is done
			//
			// $ua = analytics_cats();
			// if ($ua != "") {
			// 	spool_this($ua);
			// 	return;
			// }

			// use the default channel if there is
			if ($uastring != "") {
				GA_Filter::spool_this($uastring);
				return;
			}

			// if we get here there is a problem
			echo("<!-- Wtyczka jest włączona, ale numer konta kanału nie jest dostępny. -->\n");
		} // end spool_analytics()

		/*
		 * Insert the tracking code into the page
		 */
		function spool_this($ua) {
			global $version, $includeUDN;

			// Don't track pages in preview mode
			if (!array_key_exists('preview', $_GET) || !$_GET['preview']) {
				echo("<script type='text/javascript'>\n");
				echo("(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){\n");
				echo("	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),\n");
				echo("	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)\n");
				echo("})(window,document,'script','//www.google-analytics.com/analytics.js','ga');\n");
				echo("\n");
				echo("ga('create', '$ua', '{$_SERVER['HTTP_HOST']}');\n");
				echo("ga('send', 'pageview');\n");
				echo("</script>\n");
			}
		}

		/* Create an array which contians:
		 * "domain" e.g. boakes.org
		 * "host" e.g. store.boakes.org
		 */
		function ga_get_domain($uri){

			$hostPattern = "/^(http:\/\/)?([^\/]+)/i";
			$domainPattern = "/[^\.\/]+\.[^\.\/]+$/";

			preg_match($hostPattern, $uri, $matches);
			$host = $matches[2];
			preg_match($domainPattern, $host, $matches);
			return array("domain"=>$matches[0],"host"=>$host);

		}

		/* Take the result of parsing an HTML anchor ($matches)
		 * and from that, extract the target domain.  If the
		 * target is not local, then when the anchor is re-written
		 * then an urchinTracker call is added.
		 *
		 * the format of the outbound link is definedin the $leaf
		 * variable which must begin with a / and which may
		 * contain multiple path levels:
		 * e.g. /outbound/x/y/z
		 * or which may be just "/"
		 *
		 */
		function ga_parse_link($leaf, $matches){
			global $origin ;
			$target = GA_Filter::ga_get_domain($matches[3]);
			$coolbit = "";
			if ( $target["domain"] != $origin["domain"]  ){
				$coolBit .= "onclick=\"javascript:urchinTracker ('".$leaf."/".$target["host"]."');\"";
			}
			return '<a href="' . $matches[2] . '//' . $matches[3] . '"' . $matches[1] . $matches[4] . ' '.$coolBit.'>' . $matches[5] . '</a>';
		}

		function ga_parse_article_link($matches){
			return GA_Filter::ga_parse_link("/outbound/article",$matches);
		}

		function ga_parse_comment_link($matches){
			return GA_Filter::ga_parse_link("/outbound/comment",$matches);
		}

		function the_content($text) {
			static $anchorPattern = '/<a (.*?)href="(.*?)\/\/(.*?)"(.*?)>(.*?)<\/a>/i';
			$text = preg_replace_callback($anchorPattern,array('GA_Filter','ga_parse_article_link'),$text);
			return $text;
		}

		function comment_text($text) {
			static $anchorPattern = '/<a (.*?)href="(.*?)\/\/(.*?)"(.*?)>(.*?)<\/a>/i';
			$text = preg_replace_callback($anchorPattern,array('GA_Filter','ga_parse_comment_link'),$text);
			return $text;
		}

		function comment_author_link($text) {

			static $anchorPattern = '(.*href\s*=\s*)[\"\']*(.*)[\"\'] (.*)';
			ereg($anchorPattern, $text, $matches);
			if ($matches[2] == "") return $text;

			$target = GA_Filter::ga_get_domain($matches[2]);
			$coolbit = "";
			$origin = GA_Filter::ga_get_domain($_SERVER["HTTP_HOST"]);
			if ( $target["domain"] != $origin["domain"]  ){
				$coolBit .= " onclick=\"javascript:urchinTracker('/outbound/commentauthor/".$target["host"]."');\" ";
			}
			return $matches[1] . "\"" . $matches[2] . "\"" . $coolBit . $matches[3];
		}
	} // class GA_Filter
} // endif

$version = "0.61";
$uakey = "analytics";

if (function_exists("get_option")) {
	if ($wp_uastring_takes_precedence) {
		$uastring = get_option('analytics_uastring');
	}
}

$mulch = ($uastring=""?"##-#####-#":$uastring);
$gaf = new GA_Filter();
$origin = $gaf->ga_get_domain($_SERVER["HTTP_HOST"]);

if (!function_exists("add_GA_config_page")) {
} //endif

// adds the menu item to the admin interface
add_action('admin_menu', array('GA_Admin','add_config_page'));

// adds the footer so the javascript is loaded
add_action('wp_footer', array('GA_Filter','spool_analytics'));
// adds the footer so the javascript is loaded
add_action('bb_foot', array('GA_Filter','spool_analytics'));

// filters alter the existing content
add_filter('the_content', array('GA_Filter','the_content'), 99);
add_filter('the_excerpt', array('GA_Filter','the_content'), 99);
add_filter('comment_text', array('GA_Filter','comment_text'), 99);
add_filter('get_comment_author_link', array('GA_Filter','comment_author_link'), 99);

?>