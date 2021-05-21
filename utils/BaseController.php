<?php

require_once('Controller.php');
require_once('Request.php');
require_once('utils/photos.php');
require_once('utils/DateUtils.php');
require_once('libs/xtemplate.class.php');

require_once('libs/GeoTimeZone/Calculator.php');
require_once('libs/GeoTimeZone/Quadrant/Element.php');
require_once('libs/GeoTimeZone/Quadrant/Tree.php');
require_once('libs/GeoTimeZone/Geometry/Utils.php');
require_once('libs/GeoTimeZone/Quadrant/Indexer.php');

use GeoTimeZone\Calculator;

abstract class BaseController extends Controller
{
	private $calculator;

	public function __construct( $requiresAuth, $config )
	{
		parent::__construct( $requiresAuth, $config );

		//$this->calculator = new Calculator("libs/GeoTimeZone/data/");
	}

	protected function addLazyLoadLibrary( $xtpl )
	{
		$this->addJsFile( 'https://cdnjs.cloudflare.com/ajax/libs/jquery.lazy/1.7.9/jquery.lazy.min.js', $xtpl );
		$this->addJsFile( 'https://cdnjs.cloudflare.com/ajax/libs/jquery.lazy/1.7.9/jquery.lazy.plugins.min.js', $xtpl );

		$this->addJsFile( '/js/lazy-load.js', $xtpl );
	}

	public function get( $request )
	{
		$todaysPhoto = getTodaysPhoto();

		$xtpl = new XTemplate( 'templates/base.html' );
		$xtpl->assign( 'IMAGE', $todaysPhoto->image );
		$xtpl->assign( 'TODAYS_IMAGE_ID', $todaysPhoto->id );

		if( $this->blurBackground() )
		{
			$xtpl->assign( 'BLURRED_BACKGROUND', $this->getBlurredBackgroundPhotoUrl( $todaysPhoto ) );
			$xtpl->parse( 'main.blur_background' );
		}
		else
		{
			$xtpl->parse( 'main.normal_background' );
		}

		$xtpl->assign( 'TITLE', $this->getTitle() );

		if( $this->provideBack() === true )
		{
			/* I think we may want to just keep this simple
			$url = (array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER']  : "");
			if( $this->checkRootDomain( $url ) == false )
			{
				$url = $this->getBackUrl();
			}
			// Back arrow should take you back to the parent is the last page
			// was also this page. This prevents long back chains preventing
			// you from accessing the nav
			elseif( $this->lastPageWasSame() )
			{
				$url = $this->getBackUrl();
			}
			*/
			$url = $this->getBackUrl();

			$xtpl->assign( 'BACK_URL', $url );
			$xtpl->parse( 'main.nav_back' );
		}
		else
		{
			$xtpl->parse( 'main.nav_drawer' );
		}

		if( $this->isAuthenticated() )
		{
			$xtpl->parse( 'main.authenticated' );
		}

		$this->getBody( $request, $todaysPhoto, $xtpl );

		$xtpl->assign( 'RICH_PREVIEW_TITLE', $this->getRichTitle() );
		$xtpl->assign( 'RICH_PREVIEW_DESCRIPTION', $this->getRichDescription() );
		$xtpl->assign( 'RICH_PREVIEW_IMAGE', $this->getRichImage() );
		$xtpl->assign( 'RICH_URL', $this->getRichUrl() );

		$xtpl->assign( 'SEO_KEYWORDS', $this->getSeoKeywords() );
		$xtpl->assign( 'SEO_ROBOTS', $this->getSeoRobots() );

		$xtpl->parse( 'main' );
		$xtpl->out( 'main' );
	}

	public function getBlurredBackgroundPhotoUrl( $todaysPhoto )
	{
		return b2GetPublicBlurUrl( $todaysPhoto->id );
	}

	abstract public function getTitle();

	abstract public function getBody( $request, $todaysPhoto, $xtpl );

	public function getRichTitle()
	{
		return 'Adventure.Rocks - ' . $this->getTitle();
	}

	public function getRichDescription()
	{
		return "A window into Adam &amp; Stacy's Adventures";
	}

	public function getRichImage()
	{
		return 'http://wethinkadventure.rocks/images/default_rich_image.jpg';
	}

	public function getRichUrl()
	{
		return "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	}

	public function getSeoKeywords()
	{
		return 'adam brown stacy brown adventure photos pictures videos gopro trips mountains mountain mountaineering skiing climbing rockclimbing hiking backpacking nature outdoors backcountry';
	}

	public function getSeoRobots()
	{
		return 'index,follow';
	}

	public function provideBack()
	{
		return false;
	}

	public function getBackUrl()
	{
		return '/about';
	}

	public function blurBackground()
	{
		return true;
	}

	public function addCssFile( $path, $xtpl )
	{
		$xtpl->assign( 'CSS_FILE', $path );
		$xtpl->parse( 'main.css_file' );
	}

	public function addJsFile( $path, $xtpl )
	{
		$xtpl->assign( 'JS_FILE', $path );
		$xtpl->parse( 'main.js_file' );
	}

	public function addSeoLocation( $latitude, $longitude, $xtpl )
	{
		$xtpl->assign( 'SEO_LATITUDE', $latitude );
		$xtpl->assign( 'SEO_LONGITUDE', $longitude );
		$xtpl->parse( 'main.seo_location' );
	}

	public function addNavAction( $id, $icon, $tooltip, $url, $xtpl, $raw = null )
	{
		$xtpl->assign( 'ACTION_ID', $id );
		$xtpl->assign( 'ACTION_ICON', $icon );
		$xtpl->assign( 'ACTION_TOOLTIP', $tooltip );
		$xtpl->assign( 'ACTION_URL', $url );
		if( $raw !== null )
		{
			$xtpl->assign( 'ACTION_RAW', $raw );
		}
		$xtpl->parse( 'main.nav_action' );
	}

	public function formatDateForDisplay( $inDate, $format = null )
	{
		return $this->formatDateForDisplayWithTimeZone( $inDate, new DateTimeZone( 'America/Los_Angeles' ), $format );
	}

	public function formatDateForDisplayWithLocation( $inDate, $latitude, $longitude, $format = null )
	{
/*
echo 'latitude ' . $latitude . ' longitude ' . $longitude;
		$timeZoneName = $this->calculator->getTimeZoneName($latitude, $longitude);
echo 'TIMEZONE '.$timeZoneName . '<br />';
*/
		//$timeZone = get_nearest_timezone($latitude, $longitude, 'US');
		$timeZone = new DateTimeZone("America/Los_Angeles");
		return $this->formatDateForDisplayWithTimeZone( $inDate, $timeZone, $format );
	}

	private $defaultDateFormat = "j M Y - H:i";

	public function formatDateForDisplayWithTimeZone( $inDate, $timeZone, $format = null )
	{
		$dateTime = new DateTime( $inDate, new DateTimeZone( "GMT" ) );
		$dateTime->setTimezone( $timeZone );

		$fmt = null;
		if($format != null)
		{
			$fmt = $format;
		}
		else
		{
			$fmt = $this->defaultDateFormat;
		}

		return $dateTime->format( $fmt );
	}

	protected function checkRootDomain( $url )
	{
		if( !preg_match( "~^(?:f|ht)tps?://~i", $url ) )
		{
			$url = "http://" . $url;
		}

		$domain = implode( '.', array_slice( explode( '.', parse_url( $url, PHP_URL_HOST ) ), -2 ) );
		if( $domain == 'wethinkadventure.rocks' )
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	private function lastPageWasSame()
	{
		if( array_key_exists( 'HTTP_REFERER', $_SERVER ) )
		{
			$url = $_SERVER['HTTP_REFERER'];
			$domain = implode( '.', array_slice( explode( '.', parse_url( $url, PHP_URL_HOST ) ), -2 ) );
			$path = substr( $_SERVER['HTTP_REFERER'], strpos( $_SERVER['HTTP_REFERER'], $domain ) + strlen( $domain ) );

			return $this->startsWith( $path, '/' . $this->urlStub() );
		}
		else
		{
			return false;
		}
	}

	private function startsWith( $haystack, $needle )
	{
		$length = strlen( $needle );
		return (substr( $haystack, 0, $length ) === $needle);
	}
}

?>