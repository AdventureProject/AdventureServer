<?php

require_once('utils/BaseController.php');

class StatsController extends BaseController
{
    public function __construct( $config )
    {
        parent::__construct( false, $config );
    }
	
	public function urlStub()
	{
		return 'stats';
	}
	
    public function getTitle()
    {
    	return 'Stats';
    }
	
	public function getRichDescription()
	{
		return 'Photo stats from our data set';
	}
	
    public function getBody( $request, $todaysPhoto, $xtpl )
    {
		//if( $request->params['regenerate'] == 1 && $this->isAuthenticated())
		{
			$this->regenerateStats( $xtpl );
		}
		
		$this->addJsFile( 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.bundle.js', $xtpl );
		
		// raw stats
		// total photos
		
		// http://www.chartjs.org/docs/latest/charts/
		// http://www.chartjs.org/samples/latest/
		// chart ideas
		// bubble chart for time of day vs time of year
		// bar chart for num photos by month over entire timeline
		// bar chat for num photos by month
		// bar chat num photos by time of day
		// radar chart?
		
		//$this->addCssFile( '/css/stats.css', $xtpl );
		$xtpl->assign_file('BODY_FILE', 'templates/stats.html');
        $xtpl->parse('main.body');
    }
	
	private function regenerateStats( $xtpl )
	{
		$db = getDb();
		$pdo = getDbPdo();
		
		$allPhotos = array();
		
		$allPhotosQuery = $db->photos();
		while( $row = $allPhotosQuery->fetch() )
		{
			$allPhotos[] = $row;
		}
		
		$photoTimestamps = array();
		foreach ($allPhotos as $photo)
		{
			$mysqlDateTime = $photo['date_taken'];
			
			$timestamp = strtotime($mysqlDateTime);
			
			$photoTimestamps[] = $timestamp;
		}
		
		$this->monthsChart( $photoTimestamps, $xtpl );
		$this->hourBymonthsChart( $photoTimestamps, $xtpl );
		
		/*
		$totalPhotoFrame = $pdo->query("SELECT COUNT(id) AS total FROM `photos` WHERE `photoframe` = 1")->fetch()['total'];
		$totalWallpaper = $pdo->query("SELECT COUNT(id) AS total FROM `photos` WHERE `wallpaper` = 1")->fetch()['total'];
		$totalPhotoWall = $pdo->query("SELECT COUNT(id) AS total FROM `photos` WHERE `photowall_id` IS NOT NULL")->fetch()['total'];
		$totalMissingLocation = $pdo->query("SELECT COUNT(id) AS total FROM `photos` WHERE `location` = ',' OR `location` IS NULL")->fetch()['total'];
		$totalPhotos = $pdo->query("SELECT COUNT(id) AS total FROM `photos`")->fetch()['total'];
		$totalAlbums = $pdo->query("SELECT COUNT(id) AS total FROM `albums`")->fetch()['total'];
		*/
		
		$pdo = null;
	}
	
	private function monthsChart( $photoTimestamps, $xtpl )
	{
		$numByMonth = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
		
		foreach ($photoTimestamps as $timestamp)
		{
			$month = date('n', $timestamp)-1;
			
			$numByMonth[$month] += 1;
		}
		
		$monthsCSL = "";
		foreach($numByMonth as $key => $value)
		{
			$monthsCSL .= ' ' . $value . ',';
		}
		$xtpl->assign('MONTH_VALUES', $monthsCSL);
	}
	
	private function hourBymonthsChart( $photoTimestamps, $xtpl )
	{
		$hourByMonth = array(12);
		for( $y = 0; $y<12; $y++ )
		{
			$hour = array(24);
			for( $x = 0; $x<24; $x++ )
			{
				$hour[$x] = 0;
			}
			$hourByMonth[$y] = $hour;
		}
		
		foreach ($photoTimestamps as $timestamp)
		{
			$month = date('n', $timestamp)-1;
			$hour = date('G', $timestamp);
			
			$hourByMonth[$month][$hour] += 1;
		}
		
		$data = "";
		foreach( $hourByMonth as $x => $month )
		{
			foreach( $month as $y => $numPics )
			{
				if( $numPics > 0 )
				{
					$data .= "{ x: $x, y: $y, r: $numPics },\n";
				}
			}
		}
		$xtpl->assign('HOUR_MONTH_VALUES', $data);
	}
}

?>