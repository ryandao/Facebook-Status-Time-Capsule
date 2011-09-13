// Requires jquery and spin.js

// Make .spin() accessible via jQuery
$.fn.spin = function(opts) {
  this.each(function() {
	  var $this = $(this),
	      spinner = $this.data('spinner');

	  if (spinner) spinner.stop();
	  if (opts !== false) {
	    opts = $.extend({color: $this.css('color')}, opts);
	    spinner = new Spinner(opts).spin(this);
	    $this.data('spinner', spinner);
	  }
  });
  return this;
};


/**
target is a jQuery object (eg. returned from $('#id')).
*/
function spinner_show(target) {
	var opts = {
		lines: 12, // The number of lines to draw
		length: 8, // The length of each line
		width: 4, // The line thickness
		radius: 11, // The radius of the inner circle
		color: '#000', // #rbg or #rrggbb
		speed: 2.7, // Rounds per second
		trail: 40, // Afterglow percentage
		shadow: true // Whether to render a shadow
	};
	target.spin(opts);
	
}


function spinner_stop(target) {
  target.data('spinner').stop();
}


/**
Shows a spinner until check returns TRUE. check will be called every interval miliseconds. callback will be called when the check returns TRUE.
*/
function spinner_show_until(target, interval, check, callback) {
  var spinner = spinner_show(target);
  
  var check_wrapper = function() {
    var retval = check();
    if (!retval) {
      setTimeout(check_wrapper, interval);
    }
    else {
      if (jQuery.isFunction(callback)) {
        spinner_stop(target);
        callback();
      }
    }
  }
  check_wrapper();
}

$(function() {
  if (!_really_load) {
    return;
  }
  
  FB.init({
    appId  : '233868763323548',
    status : true, // check login status
    cookie : true, // enable cookies to allow the server to access the session
    xfbml  : true, // parse XFBML
    channelUrl : 'http://ec2-75-101-236-189.compute-1.amazonaws.com/channel.html', // channel.html file
    oauth  : false // enable OAuth 2.0
  });
  
 // Invite button
  $('#button-invite').click(function() {
    FB.ui({app_id: '233868763323548', display: 'popup', method: 'apprequests', message: 'See you past status and check out your popularity!', data: '1'});
  });
  
  // Main tab (run after main content is loaded)
  $(document).bind('loaded', function() {
    $('.tab-main').click(function() {
      $('.tab-main').removeClass('confirm');
      $(this).addClass('confirm');
      var id = '#'+$(this).attr('id');
      $('.main-tab-member').not($(id)).hide(300);
      $(id+'-content').show(300);
    });
  });
  
  // Loading screen
  $('#spinner').before('<div id="loading-message" style="margin-top: 5em;text-align:center;">Please wait while we gather your past statuses...</div>');
  spinner_show($('#spinner'));
  
  var continue_funky_messages = true;
  var i = 0;
  $('#main').html('<div id="loading" style="text-align:center; font-size:150%; padding-top: 50px;"><div id="funky0"></div></div>');
  var funky_messages = function() {
    if (continue_funky_messages) {
      var messages = [
        'Spinning the hamsters',
        'Providing brushes to the monkeys',
        'Feeding the statisticians',
        'Establishing equilibrium around the z axis',
        'Knocking on Mark Zuckerberg\'s door',
        'Gathering celebrities to define popularity',
        'Pinging the server for any sign of life',
        //'Punching out the calculators',
        'Proving P == NP',
        'Finding the Higgs boson',
        'Searching for a cure for cancer',
        'Practicing Legilimency',
        'Resuscitating dead monkeys',
        'Trying very hard to remember the past',
        'Inventing the time machine',
        'Figuring out your narcissism level',
        'The girl next door likes you',
        'Undoing the original sin',
        'Sleeping with eyes open',
        'Dropping an apple over Newton\'s head',
        'Giving out money to the poor',
        'Flying kites',
        'Injecting darbepoetin alfa',
        'Singing Pink Panther theme song',
        'Ben Leong is evil'
      ];
      var selected_message = messages[Math.floor(Math.random()*messages.length)];
      $('#funky'+i).slideUp(300);
      $('#loading').append('<div id="funky'+(i+1)+'" style="display:none; background-color:transparent; margin: 0 auto;">'+selected_message+'</div>');
      $('#funky'+(i+1)).slideDown(300);
      i++;
      setTimeout(funky_messages, 2000);
    }
  }
  funky_messages();
  
  $.post('/?q=ajax_content', _POST, function(data) {
    continue_funky_messages = false;
    spinner_stop($('#spinner'));
    $('#main')
      .hide()
      .html(data)
      .show(1500);
    
    $('#loading-message').hide();
    // Hide the spinner place holder
    $('#spinner').slideUp(2000);  
    // Trigger events when the page is fully loaded  
    $(document).trigger('loaded');	  	
  });

});




