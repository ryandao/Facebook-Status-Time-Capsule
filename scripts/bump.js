$(document).bind('loaded', function() {
  $(".bump-button").click(function() {  	  
	  var status_id = $(this).attr('status_id');
	  //alert("Coming Soon");	  
	  //+'/comments'
	  FB.api('/' + status_id, function(response) {
	  	if (!response || response.error) {
	  		
	  	} else {
	  	  var status_message = response.message;
	  	  var comment_body = "Bumped up via http://apps.facebook.com/status-timecapsule";
	  	  
	  	  FB.api('/'+status_id+'/comments', 'post', { message: comment_body }, function(response) {
		    if (!response || response.error) {
		  	  alert("Coming Soon...");
			} else {
		  	  alert('Bumped up! A comment has been added to your status post.');
		  
		  	  var status_link = "http://www.facebook.com/permalink.php?story_fbid=" + status_id + "&id=" + user_id; 
		      var wallpost = {
		  	    message : 'I just bumped up a past status.',
		  	    name: 'View the status',
	            link: status_link,	      
	            caption: user_name + ' just bumped up the status:',
	            picture: 'http://ec2-75-101-236-189.compute-1.amazonaws.com/images/276486_233868763323548_2165787_n.jpg',
	            description: '\"' + status_message + '\"' 	        
		      };
		      FB.api('/me/feed', 'post', wallpost, function(response) {
		  	    if (!response || response.error) {
		  	      // Post not published
		  	    } else {
		  	      // Post published
		  	    }
		      });
		    }
	      });	
	  	}
	  });
	  	  
	  
	  //var result = FB.api(status_id+"/comments",{'message':"Bump"});
	  //alert(result);
	  //window.location.replace("http://apps.facebook.com/status-timecapsule/");
  });
});
	