<?php

require_once 'includes/db.php';

function karma($status) {
	return $status['like_count']+$status['comment_count']*0.75;
}

function print_karma($statuses) {
	$karma = karma_calculate($statuses);  
	
	$karma_ranks = karma_rank_get($karma->pop_index);


print <<<HTML

	<div class="data-box">
    	<div class="data-left">
			<h4>{$karma->status_count}<span class="data-text">Total statuses</span></h4>
			<div class="clearfloat"></div>
			<h4>{$karma->comment_count}<span class="data-text">Comments on statuses</span></h4>
			<div class="clearfloat"></div>
			<h4>{$karma->like_count}<span class="data-text">Likes on statuses</span></h4>
		</div>
		<div class="data-right">
			<p class="data-text">You're more popular than</p>
			<h4 class="percent">{$karma_ranks->p_more_popular}%</h4>
			<p class="data-text">of Facebook users!</p>
			<input type="button" class="uibutton confirm" id="share-karma" value="Share your popularity">
		</div>
	</div>
	
	<div class="clearfloat"></div>
		
	<script type="text/javascript">
	  var karma_rank = {$karma_ranks->p_more_popular};
	</script>
HTML;
	
	global $user;
	karma_set($user['id'], $karma->pop_index);
  $user['_karma'] = $karma;
}



/**
Adds $value to $array[$key]. (PHP is stupid)
*/
function array_accum(&$array, $key, $value) {
  if (isset($array[$key])) {
    $array[$key] += $value;
  }
  else {
    $array[$key] = $value;
  }
}

/**
Fills missing keys in the array in descending order.
*/
function array_fill_missing_desc($ar) {
  $ret = array();
  $last_key = NULL;
  $keys = array_keys($ar);
  for ($i = max($keys); $i >= min($keys); $i--) {
    $ret[$i] = isset($ar[$i]) ? $ar[$i] : 0;
  }
  return $ret;
}


function karma_calculate($statuses) {  
  $return = new stdClass();
  
  $daily_karma = array();
  //$daily_status_count = array();
  $comment_count = 0;
  $like_count = 0;
  $denominator = 0.;
  foreach ($statuses as $status) {
    // get the timestamp of the date
    $daystamp = unixtojd(strtotime(substr($status['updated_time'], 0, 11)));
    $karma = karma($status);
    array_accum($daily_karma, $daystamp, $karma);
    //array_accum($daily_status_count, $daystamp, 1);
    $comment_count += $status['comment_count'];
    $like_count += $status['like_count'];
    
    
    // For the Wilson score, we need to find a proportion of "positive values". Therefore we set an absolute value for max karma per status message.
    $denominator += max($karma, 10);
  }
    
  //print_r($daily_status_count);
  //print_r(array_fill_missing_desc($daily_status_count));
  //print 'sharpe: ' . sharpe_ratio($a);
  //print "\nsum:" . array_sum($daily_karma) . "\n";
  $wilson_score = Rating::ratingAverage(array_sum($daily_karma), $denominator);
	$return->pop_index = $wilson_score * 100;
  
  $daily_karma_full = array_fill_missing_desc($daily_karma);
  $return->comment_count = $comment_count;
  $return->like_count = $like_count;
  $return->day_count = count($daily_karma);
  $return->status_count = count($statuses);
  
	return $return;
}

/**
Exponential moving average.
*/
function ema($days, $n = 5) {
	$count = count($days);
	$sma = array_sum($days) / $count;
	$sma_n = array_sum(array_slice($days, 0, $n)) / $n;
	//print '<pre>';
	//print_r($days);
	//$days = array_reverse($days);
	
	$multiplier = 2 / ($count + 1);

	$ema = 0.;
	$ema_previous = $sma_n;
	foreach (array_slice($days, $n) as $i) {
	  $ema_today = (($i - $ema_previous) * $multiplier) + $ema_previous;
	  $ema += $ema_today;
	  $ema_previous = $ema_today;
	}
	//print "sma: $sma\n1 ema: $ema_previous";
	//print '</pre>';
	return $ema_previous;
}


function sharpe_ratio($days) {
	$karma = array_sum($days);
	return $karma/(count($days)*sd($days));
}

// Function to calculate square of value - mean
function sd_square($x, $mean) { return pow($x - $mean,2); }

// Function to calculate standard deviation (uses sd_square)   
function sd($array) {   
// square root of sum of squares devided by N-1
return sqrt(array_sum(array_map("sd_square", $array, array_fill(0,count($array), (array_sum($array) / count($array)) ) ) ) / (count($array)-1) );
}


/**
Courtesy of http://www.evanmiller.org/how-not-to-sort-by-average-rating.html and http://www.derivante.com/2009/09/01/php-content-rating-confidence/
pos is the number of positive ratings, n is the total number of ratings, and power refers to the statistical power: pick 0.10 to have a 95% chance that your lower bound is correct, 0.05 to have a 97.5% chance, etc.
*/
class Rating
{
  public static function ratingAverage($positive, $total, $power = '0.05')
  {
    if ($total == 0)
      return 0;
 
    $z = Rating::pnormaldist(1-$power/2,0,1);
    //print $z;
    $p = 1.0 * $positive / $total;
    $to_sqrt = ($p*(1-$p)+$z*$z/(4*$total))/$total;
    if ($to_sqrt < 0) {
      $to_sqrt = 0.;
    }
    $sqrt = sqrt($to_sqrt);
    $s = ($p + $z*$z/(2*$total) - $z * $sqrt)/(1+$z*$z/$total);
    //print "z = $z total = $total p = $p to_sqrt = $to_sqrt s = $s";
    //print '($p*(1-$p)+$z*$z/(4*$total))/$total): ' . ($p*(1-$p)+$z*$z/(4*$total))/$total . "\n";
    return $s;
  } 
 
  public static function pnormaldist($qn)
  {
    $b = array(
      1.570796288, 0.03706987906, -0.8364353589e-3,
      -0.2250947176e-3, 0.6841218299e-5, 0.5824238515e-5,
      -0.104527497e-5, 0.8360937017e-7, -0.3231081277e-8,
      0.3657763036e-10, 0.6936233982e-12);
 
    if ($qn < 0.0 || 1.0 < $qn)
      return 0.0;
 
    if ($qn == 0.5)
      return 0.0;
 
    $w1 = $qn;
 
    if ($qn > 0.5)
      $w1 = 1.0 - $w1;
 
    $w3 = - log(4.0 * $w1 * (1.0 - $w1));
    $w1 = $b[0];
 
    for ($i = 1;$i <= 10; $i++)
      $w1 += $b[$i] * pow($w3,$i);
 
    if ($qn > 0.5)
      return sqrt($w1 * $w3);
 
    return - sqrt($w1 * $w3);
  }
}

/// Persistence ///

function karma_set($uid, $karma) {
  global $db;
  $time = time();
  $q = $db->prepare('INSERT INTO popindex (fb_user_id, value, updated) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = ?, updated = ?');
  $q->bind_param('sdidi', $uid, $karma, $time, $karma, $time);
  $q->execute();
}


function karma_rank_get($pop_index) {
  global $db;
  $return = new stdClass();
  
  $q = $db->prepare('SELECT COUNT(fb_user_id) from popindex WHERE value > ?');
  $q->bind_param('d', $pop_index);
  $q->execute();
  $q->bind_result($count_higher);
  
  while ($q->fetch()) {
    $return->count_higher = $count_higher; // number of FB users that have higher pop index than specified
  } // Ugly hack to make sure everything is fetched before the next statement
  
  $q = $db->prepare('SELECT COUNT(fb_user_id) from popindex WHERE 1');
  $q->execute();
  $q->bind_result($count_total);
  while ($q->fetch()) {
    $return->count_total = $count_total;
  } // Ugly hack to make sure everything is fetched before the next statement
  
  $return->p_more_popular = round((1 - ($count_higher / $count_total)) * 100);
  return $return;
}


function print_leaderboard($friends = TRUE, $limit = 10) {
  require_once dirname(__FILE__) . '/common.php';
  
  $current_user = $GLOBALS['user'];
  global $db;
  // note that $limit is not entered by user so there is SQL injection risk
  $where = '';
  if ($friends) {
    global $facebook;
    $decoded_response = $facebook->api('me/friends');
    $friend_ids = array();
    foreach ($decoded_response['data'] as $friend_ar) {
      $friend_ids[] = '"' . $friend_ar['id'] . '"';
    }
    $friend_ids[] = '"' . $current_user['id'] . '"';
    
    $where = 'WHERE popindex.fb_user_id IN (' . implode(', ', $friend_ids) . ')';
    //print $where;
  }
  
  //$time = microtime(TRUE);
  $result = $db->query('SELECT popindex.fb_user_id, popindex.value AS popindex, cache.value AS user, popindex.updated AS updated FROM `popindex` INNER JOIN cache ON cache.name = concat("user:", popindex.fb_user_id) ' . $where . ' ORDER BY popindex.value DESC LIMIT ' . $limit);
  //print microtime(TRUE) - $time;
  $time = time();
  $ctr = 1;
  print $friends ? '<h2>Most popular among your friends</h2>' : '<h2>Most popular Facebook users</h2>';
  print '<div id="leaderboard">';
  while ($row = $result->fetch_assoc()) {
    $user = json_decode($row['user']);
    $user_url = $user->link;
    //print_r($user->_karma);
    
    $karma_details = '';
    if (!empty($user->_karma)) {
      $karma_details = "{$user->_karma->status_count} total statuses, {$user->_karma->comment_count} comments on statuses, {$user->_karma->like_count} likes on statuses"
      . ', last updated ' . format_interval($time - $row['updated'], 1) . ' ago';
    }
    
    print '<div class="leaderboard-item">
      <div class="leaderboard-rank">' . $ctr . '</div>
      <div class="leaderboard-photo"><a href="' . $user_url . '" target="blank"><img src="http://graph.facebook.com/' . ($row['fb_user_id']) . '/picture" /></a></div>
      <div class="leaderboard-desc">
        <a class="leaderboard-name" href="' . $user_url . '" target="blank">' . $user->name . ($row['fb_user_id'] == $current_user['id'] ? ' (you)' : '') . '</a>
        <div class="leaderboard-details">' . $karma_details . '</div>
      </div>
      <div style="clear:both;"></div>
    </div>';
    $ctr++;
  }

  if ($friends) {
    print '<fb:facepile width="710" max_rows="1"></fb:facepile>';
  }
  print '</div>';
  
}
