<?php

require_once('Controller.php');
require_once('utils/KeysUtil.php');

class TelegramBotController extends Controller
{
	private $botToken = '';
	private $chatId = -1;
	private $twilioPassword = '';

	public function __construct( $config )
	{
		parent::__construct( false, $config );

		$keys = getKeys();
		$this->botToken = $keys->telegram->bot_token;
		$this->chatId = $keys->telegram->chat_id;
		//$this->botToken = $keys->telegram->debug_bot_token;
		//$this->chatId = $keys->telegram->debug_chat_id;
		$this->twilioPassword = $keys->twilio->password;
	}

	public function urlStub()
	{
		return 'telegrambot';
	}

	public function isEnabled()
	{
		return true;
	}

	public function get( $request )
	{
		echo 'na bra';
	}

	public function post( $request )
	{
		error_log('SMS received');

		// Don't let just anyone with this URL end a message as our bot!
		if( $request->params['password'] == $this->twilioPassword)
		{
			$text = $request->post['Body'];

			preg_match( '/\((([-+]?)([\d]{1,2})(((\.)(\d+)(,)))(\s*)(([-+]?)([\d]{1,3})((\.)(\d+))?))\)/', $text, $matches, PREG_OFFSET_CAPTURE );
			if( $matches )
			{
				$latLonStr = $matches[1][0];
				$parts = explode( ',', $latLonStr );
				$latitude = $parts[0];
				$longitude = $parts[1];
				$this->sendLocationMessage( $latitude, $longitude );

				// Remove the location from the message
				$latLonStrRemove = $matches[0][0];
				$text = trim( str_replace( $latLonStrRemove, '', $text ) );
			}

			$message = "🛰 *SatCom Message Received:* $text";
			$this->sendTextMessage( $message );
		}
		else
		{
			error_log('SMS failed password match');
		}
	}

	private function sendTextMessage($text)
	{
		$disable_web_page_preview = false;

		$data = array(
			'chat_id' => urlencode($this->chatId),
			'parse_mode' => 'markdown',
			'text' => $text,
			'disable_web_page_preview' => urlencode($disable_web_page_preview)
		);

		//https://api.telegram.org/bot942645309:AAHsi8cBDd34LFU4CUArreNSq4BVILNcpt4/getUpdates
		$url = "https://api.telegram.org/bot$this->botToken/sendMessage";

		$this->sendBotMessage($url, $data);
	}

	private function sendLocationMessage($latitude, $longitude)
	{
		$data = array(
			'chat_id' => urlencode($this->chatId),
		);

		$locationUrl = "https://api.telegram.org/bot$this->botToken/sendlocation?chat_id=$this->chatId&latitude=$latitude&longitude=$longitude";
		//https://api.telegram.org/bot[botToken]/sendlocation?chat_id=[UserID]&latitude=51.6680&longitude=32.6546
		$this->sendBotMessage($locationUrl, $data);
	}

	private function sendBotMessage($url, $data)
	{
		//echo 'About to send message...';
		//  open connection
		$ch = curl_init();
		//  set the url
		curl_setopt($ch, CURLOPT_URL, $url);
		//  number of POST vars
		curl_setopt($ch, CURLOPT_POST, count($data));
		//  POST data
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		//  To display result of curl
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//  execute post
		$result = curl_exec($ch);
		//  close connection
		curl_close($ch);

		return $result;
	}
}

?>