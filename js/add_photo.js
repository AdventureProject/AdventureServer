function confirmAddToPhotoWall( checkBox )
{
	if( checkBox.checked )
	{
		if( !confirm("Are you sure you want to add this to the PhotoWall?") )
		{
			checkBox.checked = false;
		}
	}
}