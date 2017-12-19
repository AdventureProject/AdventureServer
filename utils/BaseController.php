<?php

require_once('Controller.php');
require_once('Request.php');
require_once('utils/photos.php');

require_once('libs/xtemplate.class.php');

abstract class BaseController extends Controller
{
    public function get( $request )
    {
        $todaysPhoto = getTodaysPhoto();
        
        $xtpl = new XTemplate('templates/base.html');
        $xtpl->assign('IMAGE', $todaysPhoto->image);
		$xtpl->assign('TODAYS_IMAGE_ID', $todaysPhoto->id);
		
		if( $this->blurBackground() )
		{
			$xtpl->parse('main.blur_background');
		}
		else
		{
			$xtpl->parse('main.normal_background');
		}
		
        $xtpl->assign('TITLE', $this->getTitle());
		
		if( $this->provideBack() === true )
		{
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
			
			$xtpl->assign('BACK_URL', $url);
			$xtpl->parse('main.nav_back');
		}
		else
		{
			$xtpl->parse('main.nav_drawer');
		}
		
        if( $this->isAuthenticated() )
        {
            $xtpl->parse('main.authenticated');
        }
        
        $this->getBody( $request, $todaysPhoto, $xtpl );
        
		$xtpl->assign('RICH_PREVIEW_TITLE', $this->getRichTitle());
		$xtpl->assign('RICH_PREVIEW_DESCRIPTION', $this->getRichDescription());
		$xtpl->assign('RICH_PREVIEW_IMAGE', $this->getRichImage());
		$xtpl->assign('RICH_URL', $this->getRichUrl());
		
		$xtpl->assign('SEO_KEYWORDS', $this->getSeoKeywords());
		$xtpl->assign('SEO_ROBOTS', $this->getSeoRobots());
		
        $xtpl->parse('main');
		$xtpl->out('main');
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
		$xtpl->parse('main.css_file');
	}
	
	public function addJsFile( $path, $xtpl )
	{
		$xtpl->assign( 'JS_FILE', $path );
		$xtpl->parse('main.js_file');
	}
	
	public function addSeoLocation( $latitude, $longitude, $xtpl )
	{
		$xtpl->assign( 'SEO_LATITUDE', $latitude );
		$xtpl->assign( 'SEO_LONGITUDE', $longitude );
		$xtpl->parse('main.seo_location');
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
		$xtpl->parse('main.nav_action');
	}
	
	public function formatDateForDisplay( $inDate )
	{
		return date("j M Y - H:i", strtotime( $inDate ));
	}
	
	protected function checkRootDomain( $url )
	{
		if (!preg_match("~^(?:f|ht)tps?://~i", $url))
		{
			$url = "http://" . $url;
		}

		$domain = implode('.', array_slice(explode('.', parse_url($url, PHP_URL_HOST)), -2));
		if ($domain == 'wethinkadventure.rocks')
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
		if( array_key_exists('HTTP_REFERER', $_SERVER) )
		{
			$url = $_SERVER['HTTP_REFERER'];
			$domain = implode('.', array_slice(explode('.', parse_url($url, PHP_URL_HOST)), -2));
			$path = substr( $_SERVER['HTTP_REFERER'], strpos( $_SERVER['HTTP_REFERER'], $domain ) + strlen($domain) );
			
			return $this->startsWith( $path, '/'.$this->urlStub() );
		}
		else
		{
			return false;
		}
	}
	
	private function startsWith($haystack, $needle)
	{
		 $length = strlen($needle);
		 return (substr($haystack, 0, $length) === $needle);
	}
}

?>