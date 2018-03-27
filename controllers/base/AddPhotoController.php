<?php

require_once('utils/BaseController.php');
require_once('utils/photos.php');
require_once('utils/b2_util.php');
require_once('utils/KeysUtil.php');
require_once('libs/flickr.simple.php');

class AddPhotoController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( true, $config );
    }
	
	public function urlStub()
	{
		return 'addphoto';
	}
    
    public function getTitle()
    {
    	return 'Add Photo';
    }
    
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
        $xtpl->assign_file('BODY_FILE', 'templates/add_photo.html');
		$this->addJsFile( '/js/add_photo.js', $xtpl );
        $xtpl->parse('main.body');
    }
    
    public function post( $request )
    {
        if( !empty($request->post['flickr_id']) && is_numeric($request->post['flickr_id']) )
        {
			$flickrId = $request->post['flickr_id'];
			
            $db = getDb();
            
            $row = $db->photos( 'flickr_id = ?', $flickrId )->fetch();

            if( !$row )
            {
                $item['flickr_id'] = $request->post['flickr_id'];
                $item['wallpaper'] = isset($request->post['is_wallpaper']) ? 1 : 0;
                $item['highlight'] = isset($request->post['is_highlight']) ? 1 : 0;
                $item['photoframe'] = isset($request->post['is_photoframe']) ? 1 : 0;
				
                if( isset($request->post['is_photowall']) )
                {
                    $item['photowall_id'] = $db->photos()->max('photowall_id')+1;
                }
                else
                {
                    $item['photowall_id'] = null;
                }
				
				$keys = getKeys();

				$key = $keys->flickr_api->key;
				$secret = $keys->flickr_api->secret;
				$flickr = new Flickr($key, $secret);
				
				////////////////////////////////////////////////////////
				// Flickr Info
				
				$method = 'flickr.photos.getInfo';
				$args = array('photo_id' => $flickrId );
				$responseInfo = $flickr->call_method($method, $args);
				
				$item['imagetype'] = $responseInfo['photo']['originalformat'];
				$item['title'] = $responseInfo['photo']['title']['_content'];
				$item['description'] = $responseInfo['photo']['description']['_content'];
				$item['location'] = $responseInfo['photo']['location']['latitude'] . ',' . $responseInfo['photo']['location']['longitude'];
				
				$item['date_taken'] = $responseInfo['photo']['dates']['taken'];
				
				////////////////////////////////////////////////////////
				// Flickr Sizes

				$method = 'flickr.photos.getSizes';
				$args = array('photo_id' => $flickrId);
				$responseSizes = $flickr->call_method($method, $args);

				$width = -1;
				$height = -1;
				$flickrUrl = null;
				$thumbnailUrl = null;

				foreach( $responseSizes['sizes']['size'] as $size )
				{
					if( $size['label'] == 'Original' )
					{
						$width = $size['width'];
						$height = $size['height'];
						$flickrUrl = $size['source'];
					}
					else if( $size['label'] == 'Medium' )
					{
						$thumbnailUrl = $size['source'];
					}
				}
				
				$item['orientation'] = determineOrientation( $width, $height );
				
				$item['width'] = $width;
				$item['height'] = $height;
				
				////////////////////////////////////////////////////////
				// Insert to DB
                
                $newRow = $db->photos()->insert( $item );
                
                if( $newRow )
                {
					transferPhotoFromFlickrToB2( $newRow['id'], $newRow['flickr_id'] );
					
					if( $thumbnailUrl != null )
					{
						$tmpFileName = "data/temp/" . $newRow['id'];
						$downloadResult = file_put_contents($tmpFileName, fopen($thumbnailUrl, 'r'));
						if( $downloadResult )
						{
							b2PhotoMetaExists( $newRow['id'], $tmpFileName );

							$targetPath = getB2PhotoMetaPath( $newRow['id'] ) . '/' . 'thumbnail.jpg';
							uploadB2File( $tmpFileName, $targetPath );
						}
						unlink( $tmpFileName );
					}
					
					if( $item['wallpaper'] == 1 )
					{
						addBlurMeta( $newRow['id'] );
					}
					
                    header('Location:/photo/'.$newRow['id']);
                }
                else
                {
                    echo 'Failed to add Photo!';
					
					var_dump($newRow);
                }
            }
            else
            {
                echo 'Flickr ID already exists!';
            }
        }
    }
}

?>