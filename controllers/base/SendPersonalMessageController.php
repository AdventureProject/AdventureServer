<?php

require_once('utils/KeysUtil.php');
require_once('utils/BaseController.php');
require_once('utils/PushMessage.php');

class SendPersonalMessageController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( true, $config );
		
		$keys = getKeys();
		$this->authPassword = $keys->admin->password;
    }
	
	public function urlStub()
	{
		return 'sendpm';
	}
	
    public function getTitle()
    {
    	return 'Send Personal Message';
    }
	
	public function getSeoRobots()
	{
		return 'noindex, nofollow';
	}
	
	public function getBody( $request, $todaysPhoto, $xtpl )
    {
		$xtpl->assign_file('BODY_FILE', 'templates/send_personal_message.html');
		
		$db = getDb();
		$photoFrames = $db->photo_frame()->select('*')->order('created');
		
		foreach ($photoFrames as $id => $photoFrame)
		{
			$xtpl->assign('PHOTO_FRAME_OWNER', $photoFrame[owner]);
			$xtpl->assign('PHOTO_FRAME_ID', $photoFrame[id]);
			$xtpl->parse('main.body.photo_frame');
		}
		
        $xtpl->parse('main.body');
	}
	
	public function post( $request )
    {
        if( !empty($request->post['photoframe_id'])
		   && !empty($request->post['author'])
		   && !empty($request->post['recipient'])
		   && !empty($request->post['message']))
        {
			$photoFrameId = $request->post['photoframe_id'];
			
			$db = getDb();
			$row = $db->photo_frame_registration_token()->select('*')->where("id", $photoFrameId)->fetch();

			$data = array(
							'type' => 'personal_message',
							'personal_message_author' => $request->post['author'],
							'personal_message_recipient' => $request->post['recipient'],
							'personal_message_content' => $request->post['message']
						);

			#API access key from Google API's Console
			$registrationId = $row['token'];

			sendPushMessage( $registrationId, $data );
		}
	}
}

?>