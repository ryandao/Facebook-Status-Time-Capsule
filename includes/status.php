<?php
/**
 * @file 
 *   Implements Facebook user status manipulations
 */

// Testing value
define("LIMIT", 1000); 

require_once dirname(__FILE__) . '/karma.php';

/**
 * Retrieves all status updates from the current user
 * 
 * @return an array of all of the user's statuses (reformatted)
 */
function statuses_retrieve() {
  static $cache;
  if (isset($cache)) {
    return $cache;
  }
  
  global $facebook;
  global $user;

  $iteration = 0;
  $statuses = array();
  do {  	
  	$offset = $iteration * LIMIT;
  	$decoded_response = $facebook->api('me/statuses', array('limit' => LIMIT, 'offset' => $offset));  	  
  	$statuses = array_merge($statuses, $decoded_response['data']);
  	$iteration = $iteration + 1;
  } while(!empty($decoded_response['data'])); // Notes: Smaller limit requires more iterations 
  
  // Reformat the statuses array
  $statuses_reformated = array();
  foreach ($statuses as $status) {
  	$status['link'] = $link = "http://www.facebook.com/permalink.php?story_fbid=" . $status['id'] . "&id=" . $user['id'];
	// Process to retrieve the number of likes/comments
  	$status['like_count'] = isset($status['likes']) ? count($status['likes']['data']) : 0;
  	$status['comment_count'] = isset($status['comments']) ? count($status['comments']['data']) : 0;
  	
  	// Due to Facebook *weird* limit of 25, we need to do a little hack
  	if($status['comment_count'] == 25) {
	  $status['comment_count'] = tot_count($status['id'], 'comments');
  	}
  	if($status['like_count'] == 25) {
	  $status['like_count'] = tot_count($status['id'], 'likes');
  	}  	
  	
  	// Create a hash table
  	$statuses_reformated[$status['id']] = $status;  	
  }
  
  $cache = $statuses_reformated;
  return $statuses_reformated;
}

/**
 * Display actual comment/likes count for a status
 * use 'comments' or 'likes as $type
 */
function tot_count($status_id,$type) {
	global $facebook;
	$iteration = 0;
	$LIMIT = 500;
	$count = 0;
	do {  	
  	$offset = $iteration * $LIMIT;
  	$decoded_response = $facebook->api('/' . $status_id.'/'.$type,array('limit' => $LIMIT, 'offset' => $offset));  	  
  	$count += count($decoded_response['data']);
  	$iteration = $iteration + 1;
	} while(!empty($decoded_response['data'])); // Notes: Smaller limit requires more iterations 
	return $count;
}

/**
 * Convert date to a more friendly format
 */
function convert_date($post_date){
	return date("d M, Y", strtotime($post_date));
}


/**
 * Display a single status 
 */
function print_status($status) {
  print status_format($status);
}

function status_format($status) {
  global $user;
  
  $message = $status['message'];
  $likes = $status['like_count'];
  $comments = $status['comment_count'];
  
  $post_date = convert_date($status['updated_time']);
  //$link = $user['link'] . '/posts/' . $status['id'];
  $link = $status['link'];

  return <<<HTML
	<div class="status">
			<p class="status-bubble">{$message}</p>
			<div class="status details">
					<span class="status details left">{$post_date}</span>
					<span class="status details right">
							<input type="button" value="Bump Up!" class="bump-button" status_id="{$status['id']}" width="110" title="Bump this post up in your feed for your friends to see!" />
							&nbsp; <a href='{$link}' target='blank'>View</a>
    						<a class="like-thumb-icon" ></a>{$likes}
							<a class="comment-icon" ></a>{$comments}
					</span>
			</div>
	</div>
	
HTML;
}


/**
 * Display all status updates 
 */
function print_statuses($statuses) {       
  echo "<h2>All Statuses</h2>";
  foreach($statuses as $status) {   	 
  	print_status($status);
  }
}

/**
 * Get the oldest status
 */
function get_oldest($statuses) {
  // Basically it's the last array element
  return end($statuses);
}

/**
 * Get the newest status
 */
function get_newest($statuses) {
  // Basically it's the first array element
  return reset($statuses);
}

/**
 * Display the oldest status
 */
function print_oldest($statuses) {
  global $user;
  $status = get_oldest($statuses);
  
  echo "<h2>Oldest Retrievable Status</h2>";  
  print_status($status);
} 

/**
 * Get the most liked/commented status
 * 
 * @param $statuses
 *   The array of all statuses
 * @param $type
 *   Most popular status based on likes or comments. Default to both
 * @param $from, $to 
 *   Allow searching for the most popular status within a time period  
 * 
 * @return
 *   The status array
 */
function get_most_popular($statuses, $type = '', $from = '', $to = '') {    
  $max = 0;
  $most_popular = array();
  
  if ($from && $to) {
  	// Convert to timestamps for easier comparison
  	$from = strtotime($from);  	
  	$to = strtotime($to);
  }
  
  foreach ($statuses as $status) {  
  	$post_date = strtotime($status['updated_time']);

  	switch($type) {
  	  case 'like':		
        $popularity = $status['like_count'];        
        break;      
  	  case 'comment':
  	  	$popularity = $status['comment_count'];
	    break;
  	  default:
 		$popularity = $status['like_count'] + $status['comment_count']; 
  	  	break;  	  	
  	} 
    if ($popularity >= $max) {
      if ($from && $to) {
      	if ($from < $post_date && $post_date < $to) {      	
          $most_popular = $status;
          $max = $popularity; 	
      	}
      } 
      else {      	
      	$most_popular = $status;
        $max = $popularity;
      }
    }
  }   
    
  return (!empty($most_popular)) ? $most_popular : 0;
} 


function get_most_popular_multiple($statuses, $n=3) {
  // Please don't even waste your time to try to optimize this. This function runs < 0 miliseconds for 100 statuses. 
  
  function _get_most_popular_multiple_cmp_func($status1, $status2) {
    $pop1 = $status1['like_count'] + $status1['comment_count'];
    $pop2 = $status2['like_count'] + $status2['comment_count'];
    if ($pop1 == $pop2) {
      return 0;
    }
    return $pop1 < $pop2 ? 1 : -1;
  }
  
  usort($statuses, '_get_most_popular_multiple_cmp_func');
  return array_slice($statuses, 0, $n);
}


/**
 * Display the most popular status
 */
function print_most_popular($statuses) {
  global $user;
  $pop_statuses = get_most_popular_multiple($statuses);
  echo "<h2>Most Popular Statuses</h2>";
  foreach ($pop_statuses as $status) {
  print_status($status);   
  }
}


