<?php

require_once('utils/BaseController.php');

class VideosController extends BaseController
{
	private $videoTitle = null;
	private $videoDescription = null;
	private $videoThumbnail = null;
	
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'videos';
	}
	
	public function getTitle()
	{
		return 'Videos';
	}
	
	public function getRichTitle()
	{
		if( $this->videoTitle !== null )
		{
			return $this->videoTitle;
		}
		else
		{
    		return parent::getRichTitle();
		}
    }
	
	public function getRichDescription()
	{
		if( $this->videoDescription !== null )
		{
			return $this->videoDescription;
		}
		else
		{
			return 'Videos from our various Adventures';
		}
	}
	
	public function getRichImage()
	{
		if( $this->videoThumbnail !== null )
		{
			return $this->videoThumbnail;
		}
		else
		{
			return 'http://wethinkadventure.rocks/images/default_rich_image.jpg';
		}
	}
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		$this->addCssFile( '/external/magnific-popup/magnific-popup.css', $xtpl );
		$this->addJsFile( '/external/magnific-popup/jquery.magnific-popup.min.js', $xtpl );
		
		$this->addJsFile( '/external/clipboard.min.js', $xtpl );
		
		$this->addCssFile( '/external/mdl-jquery-modal-dialog/mdl-jquery-modal-dialog.css', $xtpl );
		$this->addJsFile( '/external/mdl-jquery-modal-dialog/mdl-jquery-modal-dialog.js', $xtpl );
		
		//$this->addCssFile( '/external/sweetalert/sweetalert.css', $xtpl );
		//$this->addJsFile( '/external/sweetalert/sweetalert.min.js', $xtpl );
		
		$this->addCssFile( '/css/videos.css', $xtpl );
		$this->addCssFile( '/css/zoom.css', $xtpl );
		
		$xtpl->assign_file('BODY_FILE', 'templates/videos.html');
        
		$playVideoId = $this->getLinkedVideoId( $request );
		
        $videos = $this->getVideos();
        
		foreach( $videos as $video )
		{
			$xtpl->assign( 'VIDEO_ID', $video->id );
			$xtpl->assign( 'VIDEO_THUMBNAIL', $video->thumbnail );
			$xtpl->parse('main.body.video_style');
			
			$xtpl->assign( 'VIDEO_TITLE', $video->title );
			//$xtpl->assign( 'VIDEO_DATE', $video->date->format('d M Y') );
			
			$description = null;
			if( strlen( $video->description ) > 330 )
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
			
			if( $playVideoId != null && $playVideoId == $video->id )
			{
				$this->videoTitle = $video->title;
				$this->videoDescription = substr( $video->description, 0, 299 );
				$this->videoThumbnail = $video->thumbnail;
			}
			
			$xtpl->parse('main.body.video');
		}
        
        $xtpl->parse('main.body');
    }
	
	private function getLinkedVideoId( $request )
	{
		$videoId = null;
		
		if( array_key_exists( 'play', $request->params ) )
		{
			$videoId = $request->params['play'];
		}
		
		return $videoId;
	}
    
    public function getVideos()
    {
        $youtubeApiKey = 'AIzaSyA66Bgo8cpoYAU_ATgEhr5ccnrpLt7C_Js';
        $playListId = 'PLW6j4j9UcGjqIw80l4pNnCja8mziF9xmw';
        $url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId=$playListId&key=$youtubeApiKey&maxResults=50";

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