/**
 * @file
 *   Takes care of posting on user's wall
 *
 * Variables passed from PHP:
 *   karma_rank: The popularity rank of the user   
 */

$(document).bind('loaded', function() {
  $('#share-karma').click(function() { 	 
  	FB.ui({
	  method: 'feed',
	  name: 'Status Time Capsule',
	  link: 'http://apps.facebook.com/status-timecapsule',	      
	  caption: 'View your past statuses and check out your popularity!',
	  picture: 'http://ec2-75-101-236-189.compute-1.amazonaws.com/images/276486_233868763323548_2165787_n.jpg',
	  description: user_name + ' is more popular than ' + karma_rank + '% of Facebook users. Check out your popularity now!',
	  message: ''
	},
	function(response) {
	  if (response && response.post_id) {	  
	    // Post published
	  } else {
	  	// Post unpublished	   
	  }
	});          
  });
});