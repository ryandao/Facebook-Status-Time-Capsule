/**
 * JS support for graph
 *
 * Global variables passed from PHP
 *   graph_data: [period_1[plot_data, status_data], period_3..., period_6..., period_12]
 *     plot_data: Data used to plot the graph
 *	   status_data: Arrays containing the most popular statuses relative to graph_data 
 *      
 */

/**
 * Toggle button active (blue) and inactive states
 */
$(document).ready(function() {
  $(".timeframe #button-2").addClass("confirm");
	
  $(".timeframe .uibutton").click(function(){
	$(".timeframe .confirm").toggleClass("confirm");
	$(this).next().slideToggle();
	$(this).toggleClass("confirm");
  });
  
  $(".timeright .uibutton:last").addClass("confirm");
	
  $(".timeright .uibutton").click(function(){
	$(".timeright .confirm").toggleClass("confirm");
	$(this).next().slideToggle();
	$(this).toggleClass("confirm");
  });
});

$(document).bind('loaded', function() {	
  // Toggle button active (blue) and inactive states	
  $(".timeframe #button-1").addClass("confirm");	
  
  $(".timeframe .uibutton").click(function(){
	$(".timeframe .confirm").toggleClass("confirm");
	$(this).next().slideToggle();
	$(this).toggleClass("confirm");
  });
    
  $(".timeright .uibutton:last").addClass("confirm");
  	
  $(".timeright .uibutton").click(function(){
	$(".timeright .confirm").toggleClass("confirm");
	$(this).next().slideToggle();
	$(this).toggleClass("confirm");
  });
    
  // Specify the Graph options	   
  var options = {
      lines: { show: true },
      points: { show: true },
      xaxis: { mode: "time", timeformat: "%b %y", tickSize: [3, "month"], 
       	       monthNames: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]},
      yaxis: { tickDecimals: 0, tickSize: 10 },
      grid: { clickable: true, hoverable: true }      
  };
  console.log(graph_data);
  // Change date to JS timestamp          
  for (var key in graph_data) {
    if(graph_data.hasOwnProperty(key)) {      	
      for (var i = 0; i < graph_data[key].plot_data.length; i++) {         	   
        graph_data[key].plot_data[i][0] = Date.parse(graph_data[key].plot_data[i][0]);	
      }
    }
  }    
     
  var data = [];	
  var status_data = [];
  
  // Plot the graph for 6 months initially
  data.push(graph_data.period_6.plot_data); 
  status_data.push(graph_data.period_6.status_data);       
  var placeholder = $("#placeholder");        
  $.plot(placeholder, data, options);        
  
  // Redraw the graph when clicking buttons   
  $("input.fetchSeries").click(function () {
    var button = $(this);
	var graph_period = button.attr('graph_period');
    console.log(graph_data.period_1);
    // Reset plot and status data  
    data = [];
    status_data = [];
    switch (graph_period) {
      case '1':
      	data.push(graph_data.period_1.plot_data);
      	status_data.push(graph_data.period_1.status_data);
		break;
      case '3':            
        data.push(graph_data.period_3.plot_data);
        status_data.push(graph_data.period_3.status_data);
		break;
	  case '6':
		data.push(graph_data.period_6.plot_data);
		status_data.push(graph_data.period_6.status_data);
		break;           
    }

    $.plot(placeholder, data, options);
  });
  
  /**
   *  Use click event to open the original status when clicking on graph points
   */     
  $("#placeholder").bind("plotclick", function (event, pos, item) {
    if (item) {
      if (status_data[0][item.dataIndex]) {          	         	        	             
        link = $(document.createElement('a')).attr({
          'href': status_data[0][item.dataIndex].link, 
  	      'id': 'fancy-box-link-' + item.dataIndex
        }).css('display', 'hidden');        

      	window.open(status_data[0][item.dataIndex]['status'].link);
      }      
    }
  });
  
  
  /**
   * Show the status when hovering over the points 
   */    
  $("#placeholder").bind("plothover", function (event, pos, item) {  	 
  	 $("#x").text(pos.x.toFixed(2));
	 $("#y").text(pos.y.toFixed(2));	
	    
	 if (item) {	   
	   if (previousPoint != item.dataIndex) {
	     previousPoint = item.dataIndex;	     
	                
	     $("#tooltip").remove();
	     var x = item.datapoint[0].toFixed(2),
	         y = item.datapoint[1].toFixed(2);	                
	     var content = "<span><b>Most popular from " + 
	     			   status_data[0][item.dataIndex]['from'] +  
	     			   " to " + status_data[0][item.dataIndex]['to'] + 
	     			   ":</b></span><p><i>" + 
	                   status_data[0][item.dataIndex]['status'].message + "</i></p>";
	     showTooltip(item.pageX, item.pageY, content);
	   }
	 }
	 else {
	   $("#tooltip").remove();
	   previousPoint = null;            
	 }	   
  });
  
  // Add tooltip to graph
  function showTooltip(x, y, contents) {
  	// alert(y);  	
  	var top = y + 5;    // The top position of the tooltip
  	var left = x + 5;   // The left position of the tooltip
  	var width = 200;    // The width of the tooltip
  	
  	if ($(window).width() < left + width) {
  	  // If the tooltip overlaps the viewport border, reposition it
  	  left = left - 10 - width;	
  	} 
  	console.log(x + 205);
  	console.log($(window).width());
    $('<div id="tooltip">' + contents + '</div>').css( {
	  position: 'absolute',
	  display: 'none',
	  width: width,
	  top: top,
	  left: left,
	  border: '1px solid #fdd',
	  padding: '2px',
	  'background-color': '#F4E9DA',
	  opacity: 0.90
	}).appendTo("body").fadeIn(200); 
  }

  // Initiate a recurring data update
  $("input.dataUpdate").click(function () {
    // reset data
    data = [];
    alreadyFetched = {};
        
    $.plot(placeholder, data, options);

    var iteration = 0;
        
    function fetchData() {
        ++iteration;

        function onDataReceived(series) {
          	alert("asdf");
            // we get all the data in one go, if we only got partial
            // data, we could merge it with what we already got
            data = [ series ];
            console.log(data);
            $.plot($("#placeholder"), data, options);
        }
        
        $.ajax({
            // usually, we'll just call the same URL, a script
            // connected to a database, but in this case we only
            // have static example files so we need to modify the
            // URL
            url: "data-eu-gdp-growth-" + iteration + ".json",
            method: 'GET',
            dataType: 'json',
            success: onDataReceived
        });
           
        if (iteration < 5)
            setTimeout(fetchData, 1000);
        else {
            data = [];
            alreadyFetched = {};
        }
    }  

    setTimeout(fetchData, 1000);
  });
});