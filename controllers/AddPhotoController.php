<?php

require_once('Controller.php');
require_once('Request.php');

require_once('photos.php');

include_once('libs/xtemplate.class.php');

class AddPhotoController extends Controller
{
    public function __construct( $config )
    {
        parent::__construct( true, $config );
    }
    
    public function get( $request )
    {
        $photo = getTodaysPhoto();
        
        $xtpl = new XTemplate('templates/base.html');
        $xtpl->assign('IMAGE', $photo->image);
        $xtpl->assign_file('BODY_FILE', 'templates/add_photo.html');
        $xtpl->parse('main.body');
        $xtpl->parse('main');
	    $xtpl->out('main');
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