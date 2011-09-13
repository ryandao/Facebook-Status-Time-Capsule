<?php
/**
 * @file
 *   Create a status graph based on likes/comments
 */

function print_graph() {
  $statuses = statuses_retrieve();
  $graph_data = new stdClass();
  $graph_data = array(
    'period_1' => generate_data($statuses, 1),
    'period_3' => generate_data($statuses, 3),
    'period_6' => generate_data($statuses, 6),    
  );   
?>
  <script type="text/javascript">
    var graph_data = <?php print json_encode($graph_data); ?>;
    // var statuses = <?php // print $statuses_json; ?>
  </script>
  <div id="placeholder" style="width:720px;height:250px;"></div>

  <br />

  <div class="timeframe"><input class="fetchSeries uibutton" id="button-1" graph_period="6" type="button"
	value="6 months"> 
  <span></span></div>

  <div class="timeframe"><input class="fetchSeries uibutton" id="button-2" graph_period="3" type="button"
	value="3 Months">
  <span></span></div>
  
  <div class="timeframe"><input class="fetchSeries uibutton" id="button-3" graph_period="1" type="button"
	value="1 Month">  
  <span></span></div>
  
  <div class="clearfloat"></div>
  
<?php   
}

/**
 * Generate the json data to be plotted in the graph
 * TODO: Ugly code, need to revise  
 * 
 * The data is an array of graph coordinates, which in our case is in this form
 * ([time], [number of likes/comments])  
 * 
 * @param $period
 *   The period to calculate the most popular status (by month, e.g. 6 or 12 months)
 */
function generate_data($statuses, $period = null) {
  $oldest_status = get_oldest($statuses);  
  $oldest_year = get_year($oldest_status['updated_time']); // year of the oldest status    
  $newest_status = get_newest($statuses);  
  $newest_year = get_year($newest_status['updated_time']); // year of the most recent status     
      
  // Currently the graph can only plot time period of 2, 3, 4, 6
  // So make it 12 months if an invalid number is passed
  if (($period == null) || ($period > 12) || ((12 % $period) != 0)) {
  	$period = 12;
  }
  
  $from = "01-01-$oldest_year";
  
  $data = array();  
  for ($year = $oldest_year; $year <= $newest_year + 1; $year++) {  	
  	$to = "01-01-$year";  	
  	for ($month = 1 + $period; $month <= 12 + 1; $month += $period) {
      if ($to != $from) {  	      	    	    	    	  
  	    $most_popular = get_most_popular($statuses, '', $from, $to);
  	    if ($most_popular) {
  	      $popularity = $most_popular['like_count'] + $most_popular['comment_count']; 
   	    }
  	    else {
  	      $popularity = 0;
  	    }    	    	    	 
  	    // Notes: The Flot API can only accept time data as Javascript timestamp
  	    // We need to pass a valid date string and generate timestamp at front end  	      	  
  	    $data['plot_data'][] = array(date('M j, Y', strtotime("$to")), $popularity);
  	    $data['status_data'][] = array(
  	      'status' => status_format_js($most_popular),
  	      'from' => $from,
  	      'to' => $to,
  	    );  	    
  	    
  	    if ($year == $newest_year + 1) {  	      
  	      break;	
  	    }
  	  } 
  	  $next = $month + 1; 	    	    	 
  	  $from = $to;
  	  $to = "01-$month-$year";
  	}  	
  }	  
  return $data;
}

/**
 * Extract the year from a given date string
 */
function get_year($date) {
  return date('Y', strtotime($date));
}

/**
 * Extract the month from a given date string
 */
function get_month($date) {
  return date('n', strtotime($date));
}

/**
 * Reformat the status array again to pass to JS. JS only need the following data: 
 *   - Status id
 *   - HTML representation of the status
 *   - Link to the status
 *   - Post date
 *   - Like and comment count
 */
function status_format_js($status) {
  if (! $status) {
  	return null;
  }
  
  $data = array(
  	'id' => $status['id'],
  	'message' => $status['message'],
  	'post_date' => date('M j, Y', strtotime($status['updated_time'])),
  	'like_count' => $status['like_count'],
  	'comment_count' => $status['comment_count'],
    'link' => $status['link'],
  );
  	  
  return $data;
}

?>
