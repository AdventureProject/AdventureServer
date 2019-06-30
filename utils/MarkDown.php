<?php

require_once('utils/b2_util.php');

class AdventureMarkdown extends \cebe\markdown\MarkdownExtra
{
	private $db;

	public function __construct()
	{
		$this->db = getDb();
	}

	/**
	 * @marker {{
	 */
	protected function parsePhoto( $markdown )
	{
		// check whether the marker really represents a photo (i.e. there is a closing {{)
		if( preg_match( '/^{{(.+?){{/', $markdown, $matches ) )
		{
			return [
				// return the parsed tag as an element of the abstract syntax tree and call `parseInline()` to allow
				// other inline markdown elements inside this tag
				[ 'photo', $this->parseInline( $matches[1] ) ],
				// return the offset of the parsed text
				strlen( $matches[0] )
			];
		}
		// in case we did not find a closing {{ we just return the marker and skip 2 characters
		return [ [ 'text', '{{' ], 2 ];
	}

	// rendering is the same as for block elements, we turn the abstract syntax array into a string.
	protected function renderPhoto( $element )
	{
		$photoInfo = $this->renderAbsy( $element[1] );

		if( strpos( $photoInfo, ',' ) !== false )
		{
			$parts = explode( ',', $photoInfo );
			$photoId = trim($parts[0]);
			$albumId = trim($parts[1]);

			$url = b2GetPublicThumbnailUrl( $photoId );
			$photo = $this->db->photos[$photoId];
			$title = $photo['title'];

			return '<a href="/photo/' . $photoId . '/album/' . $albumId . '"><img alt="'.$title.'" title="'.$title.'" src="' . $url . '" class="blog-image" /></a>';
		}
		else
		{
			$photoId = trim($photoInfo);

			$url = b2GetPublicThumbnailUrl( $photoId );
			$photo = $this->db->photos[$photoId];
			$title = $photo['title'];

			return '<a href="/photo/' . $photoId . '"><img alt="'.$title.'" title="'.$title.'" src="' . $url . '" class="blog-image" /></a>';
		}
	}
}

global $markDownParser;

$markDownParser = new AdventureMarkdown();

function getMarkdown()
{
	global $markDownParser;

	return $markDownParser;
}

?>