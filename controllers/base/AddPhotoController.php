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
			$importTaskId =
				createPhotoImport( $request->post['flickr_id'],
									null,
									null,
									false,
									isset($request->post['is_wallpaper']),
									isset($request->post['is_highlight']),
									isset($request->post['is_photoframe']),
									isset($request->post['is_photowall']) );

        	if( $importTaskId != null )
			{
				error_log( 'Add Photo - Import Task OK, processing...' );
				
				$photoId = processImportTask( $importTaskId );
				
				header('Location: /photo/' . $photoId);
			}
			else
			{
				error_log( 'Add Photo - Failed to create import task' );
				
				header('Location: /' . $this->urlStub());
			}
        }
    }
}

?>