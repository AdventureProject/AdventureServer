<?php

require_once('controllers/base/BaseController.php');
require_once('Request.php');

require_once('controllers/photos.php');

include_once('libs/xtemplate.class.php');

class AddPhotoController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( true, $config );
    }
    
    public function getTitle()
    {
    	return 'Add Photo';
    }
    
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
        $xtpl->assign_file('BODY_FILE', 'templates/add_photo.html');
        $xtpl->parse('main.body');
    }
    
    public function post( $request )
    {
        if( !empty($request->post['flickr_id']) && is_numeric($request->post['flickr_id']) )
        {
            $db = getDb();
            
            $row = $db->photos( 'flickr_id = ?', $request->post['flickr_id'] )->fetch();;

            if( !$row )
            {
                $item['flickr_id'] = $request->post['flickr_id'];
                $item['wallpaper'] = isset($request->post['is_wallpaper']) ? 1 : 0;
                $item['photoframe'] = isset($request->post['is_photoframe']) ? 1 : 0;
                if( isset($request->post['is_photowall']) )
                {
                    $item['photowall_id'] = $db->photos()->max('photowall_id')+1;
                }
                else
                {
                    $item['photowall_id'] = null;
                }
                
                $newRow = $db->photos()->insert( $item );
                
                if( $newRow )
                {
                    header('Location:/photo/'.$newRow['id']);
                }
                else
                {
                    echo 'Failed to add Photo!';
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