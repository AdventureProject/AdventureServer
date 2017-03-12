<?php

require_once('controllers/base/BaseController.php');
require_once('Request.php');

require_once('controllers/photos.php');

include_once('libs/xtemplate.class.php');

class VideosController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
    public function getTitle()
    {
    	return 'Videos';
    }
	
	public function getRichDescription()
	{
		return 'Videos from our various Adventures';
	}
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		$this->addCssFile( '/external/magnific-popup/magnific-popup.css', $xtpl );
		$this->addJsFile( '/external/magnific-popup/jquery.magnific-popup.min.js', $xtpl );
		
		$this->addCssFile( '/css/zoom.css', $xtpl );
		$this->addJsFile( '/js/zoom.js', $xtpl );
		
		$this->addJsFile( '/external/clipboard.min.js', $xtpl );
		
		$this->addCssFile( '/external/sweetalert/sweetalert.css', $xtpl );
		$this->addJsFile( '/external/sweetalert/sweetalert.min.js', $xtpl );
		
		$this->addCssFile( '/css/videos.css', $xtpl );
		$this->addJsFile( '/js/videos.js', $xtpl );
		
		$xtpl->assign_file('BODY_FILE', 'templates/videos.html');
        
        $videos = $this->getVideos();
        
		foreach( $videos as $video )
		{
			$xtpl->assign( 'VIDEO_ID', $video->id );
			$xtpl->assign( 'VIDEO_THUMBNAIL', $video->thumbnail );
			$xtpl->parse('main.body.video_style');
			
			$xtpl->assign( 'VIDEO_TITLE', $video->title );
			//$xtpl->assign( 'VIDEO_DATE', $video->date->format('d M Y') );
			
			$description;
			if( strlen($video->description) > 330 )
			{
				$description = substr( $video->description, 0, 336 );
				$description .= '...';
			}
			else
			{
				$description = $video->description;
			}
			$description = str_replace( "\n", "<br />", $description );
			
			$xtpl->assign( 'VIDEO_DESCRIPTION', $description );
			$xtpl->assign( 'VIDEO_URL', $video->getVideoUrl() );
			
			$xtpl->parse('main.body.video');
		}
        
        $xtpl->parse('main.body');
    }
    
    public function getVideos()
    {
        $youtubeApiKey = 'AIzaSyA66Bgo8cpoYAU_ATgEhr5ccnrpLt7C_Js';
        $playListId = 'PLW6j4j9UcGjqIw80l4pNnCja8mziF9xmw';
        $url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId=$playListId&key=$youtubeApiKey";

        $json = file_get_contents($url, false);
        
        $videos = array();
        
        $data = json_decode( $json, true );
        foreach( $data['items'] as $item )
        {
            if( $item['snippet'] && $item['snippet']['resourceId']['kind'] == 'youtube#video' )
            {
                $video = new YouTubeVideo();

                $video->id = $item['snippet']['resourceId']['videoId'];
                $video->title = $item['snippet']['title'];
                $video->description = $item['snippet']['description'];
                $video->date = new DateTime( $item['snippet']['publishedAt'] );
				if( isset( $item['snippet']['thumbnails']['maxres'] ) )
				{
					$video->thumbnail = $item['snippet']['thumbnails']['maxres']['url'];
				}
                else
				{
					$video->thumbnail = $item['snippet']['thumbnails']['medium']['url'];
				}

                $videos[] = $video;
            }
        }
        
        $videos = array_reverse( $videos );
		
		return $videos;
    }
}

class YouTubeVideo
{
    public $id;
    public $title;
    public $description;
    public $date;
    public $thumbnail;
    
    public function getVideoUrl()
    {
        return "http://www.youtube.com/watch?v={$this->id}";
    }
}

?>