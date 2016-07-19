<?php

require_once('Controller.php');
require_once('Request.php');

require_once('photos.php');

include_once('libs/xtemplate.class.php');

class PhotoController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
    
    public function get( $request )
    {
        $photo = getTodaysPhoto();
        
        $xtpl = new XTemplate('templates/base.html');
        $xtpl->assign('IMAGE', $photo->image);
        
        if( count($request->args) == 1 && is_numeric( $request->args[0] ) )
        {
            $this->renderPhoto( $xtpl, $request->args[0] );
        }
        
        $xtpl->parse('main.body');
        $xtpl->parse('main');
	    $xtpl->out('main');
    }
    
    private function renderPhoto( $xtpl, $photoId )
    {
        $xtpl->assign_file('BODY_FILE', 'templates/photo.html');
        
        if( $this->isAuthenticated() )
        {
            $xtpl->parse('main.body.admin_links');
        }
        
        $db = getDb();
        $photoData = $db->photos("id = ?", $photoId)->fetch();
        
        $photoFlickr = getPhoto( $photoData['flickr_id'], true, 512, 512 );
            
		if( $photoId-1 > 0 )
		{
			$xtpl->assign( 'PREV_PHOTO_URL', '/photo/'.($photoId-1) );
		}
		else
		{
			$xtpl->assign( 'PREV_PHOTO_URL', '/admin' );
		}
		
		if( $photoId+1 <= $db->photos()->max('id') )
		{
			$xtpl->assign( 'NEXT_PHOTO_URL', '/photo/'.($photoId+1) );
		}
		else
		{
			$xtpl->assign( 'NEXT_PHOTO_URL', '/admin' );
		}
		
        $xtpl->assign( 'PHOTO_ID', $photoId );
        $xtpl->assign( 'FLICKR_ID', $photoData['flickr_id'] );
        $xtpl->assign( 'PHOTO_TITLE', $photoFlickr->title );
        $xtpl->assign( 'PHOTO_DATE', $photoFlickr->date );
        
        if( empty($photoFlickr->location) )
        {
            if( $this->isAuthenticated() )
            {
                $xtpl->assign( 'PHOTO_LOCATION', "<a target=\"_blank\" href=\"https://www.flickr.com/photos/organize/?batch_geotag=1&ids={$photoData['flickr_id']}&from_geo_ids={$photoData['flickr_id']}\">add geo data</a>" );
            }
            else
            {
                $xtpl->assign( 'PHOTO_LOCATION', '<em>No location data</em>' );
            }
        }
        else
        {
            $xtpl->assign( 'PHOTO_LOCATION', $photoFlickr->location );
        }
        
        $xtpl->assign( 'PHOTO_DESCRIPTION', $photoFlickr->description );
        $xtpl->assign( 'FLICKR_IMG', $photoFlickr->image );
        
        $xtpl->assign( 'IS_WALLPAPER', $photoData['wallpaper'] == 1 ? 'checked' : '' );
        $xtpl->assign( 'IS_PHOTOFRAME', $photoData['photoframe'] == 1 ? 'checked' : '' );
		
        if( $this->isAuthenticated() )
        {
            $xtpl->parse('main.body.photo_actions');
        }
        
		if( !empty($photoData['photowall_id']) )
		{
			$photoWallId = $photoData['photowall_id'];
			$xtpl->assign( 'PHOTOWALL_ID', $photoData['photowall_id'] );
		}
		else
		{
			$xtpl->assign( 'NEXT_PHOTOWALL_ID', $db->photos()->max('photowall_id')+1 );
			$xtpl->assign( 'PHOTOWALL_ID', '<em>not on the wall</em>' );
            
            if( $this->isAuthenticated() )
            {
                $xtpl->parse('main.body.add_photowall');
            }
		}
    }
    
    public function post( $request )
    {
        if( $this->enforceAuth() )
        {
            if( count($request->args) == 1 && is_numeric( $request->args[0] ) )
            {
				$photoId = $request->args[0];
				
				$db = getDb();
				$photoRow = $db->photos()[$photoId];
				if( $photoRow )
				{
					$success = false;
					
					if( isset($request->post['add_to_photowall']) )
					{
						if( empty($photoRow['photowall_id']) )
						{
							$photoRow['photowall_id'] = $db->photos()->max('photowall_id')+1;
							$success = $photoRow->update();
						}
						else
						{
							echo 'Photo is already on Photowall with ID: ' . $photoRow['photowall_id'];
							$success = false;
						}
					}
					else
					{
						$photoRow['wallpaper'] = isset($request->post['is_wallpaper']) ? 1 : 0;
						$photoRow['photoframe'] = isset($request->post['is_photoframe']) ? 1 : 0;

						$success = $photoRow->update();
					}
					
					if( $success )
					{
						header("Location:/photo/$photoId");
					}
					else
					{
						echo 'Error updating Database';
					}
				}
				else
				{
					echo 'Could not find photo by ID';
				}
			}
        }
    }
}