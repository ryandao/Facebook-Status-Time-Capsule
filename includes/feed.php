<?php

function post_feed() {
		global $app_id;
		global $canvas_page;
		global $url;
		$desc = 'Share this cool app with friends.';
		$name = 'Status Time Capsule';
		$url = '"http://www.facebook.com/dialog/feed?app_id='.$app_id.'&link='.$canvas_page.'&picture='.'http://ec2-75-101-236-189.compute-1.amazonaws.com/images/palms-clock.jpg'.'&name='.$name.'&caption='.$name.'&description='.$desc.'&message='.'so cool man!'.'&display=popup&redirect_uri=http://ec2-75-101-236-189.compute-1.amazonaws.com/includes/close.html"';

		echo '<script language="javascript" type="text/javascript">';
		echo "function popitup() {";
		echo "  var winl = (screen.width-w)/2;
				var wint = (screen.height-h)/2;";
		echo "newwindow=window.open(".$url.",'name','height=300,width=500,left='+winl+'top='+wint);
			if (window.focus) {newwindow.focus()}
			return false;
		}
		</script>";
	echo '<a href="popupex.html" onclick="return popitup()"
	>Share with friends</a>';


	//global $facebook;
	//feed_params=new array('link'=>$canvas_page, 'picture'=>'http://ec2-75-101-236-189.compute-1.amazonaws.com/images/palm-clock/jpg',
	//						'name'=>$name,'caption'=$name,'description'=>$desc,'message'='CooL@@');
	//facebook->api('/feed',feed_params);
}


?>
