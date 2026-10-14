 var poi,map,icon;
 

 MQA.EventUtil.observe(window,'load',function(){
        /*Create an object for options*/
		
		if(typeof gdmapid != 'undefined')
		  {
		if (jQuery('#'+gdmapid).length > 0) {
		
		
        var findusoptions={
          elt:document.getElementById(gdmapid),        /*ID of element on the page where you want the map added*/
          zoom:gdzoom,                                   /*initial zoom level of map*/
          latLng:{lat:gdstartlat,lng:gdstartlong},   /*center of map in latitude/longitude*/
          mtype:'map' ,                               /*map type (map)*/
		  zoomOnDoubleClick:true
        };

        /*Construct an instance of MQA.TileMap with the options object*/
		
		windowwidth=jQuery('#'+gdmapid).closest('div').width();
		jQuery('#'+gdmapid).css('width',windowwidth+'px'); 
        GDmap = new MQA.TileMap(findusoptions); 


         MQA.withModule('shapes','smallzoom','largezoom','viewoptions','mousewheel','directions', function() {
			
			
				icon=new MQA.Icon(gdiconpath,gdiconwidth,gdiconheight);
				if (gdshowroute=='1') {
				  navigator.geolocation.getCurrentPosition(function(position) {
					GDmap.addRoute([
						{latLng: {lat: position.coords.latitude, lng: position.coords.longitude}},
						{latLng:{lat:gdstartlat,lng:gdstartlong}}],

						/*Add options.*/
						{ribbonOptions:{draggable:true,draggablepoi:true}},

						/*Add the callback function to the route call.*/
						displayGDNarrative
						);
				  },showError);
				} else {

					p=new MQA.Poi({lat:gdstartlat,lng:gdstartlong});
					p.setIcon(icon);
					p.setRolloverContent(gdtitle);
					p.setInfoContentHTML(gdcontent); 
					GDmap.addShape(p);
				}
			
			
	
				if (gdcontrols=='largezoom') {
					GDmap.addControl(
						new MQA.LargeZoom(),
						new MQA.MapCornerPlacement(MQA.MapCorner.TOP_LEFT, new MQA.Size(5,5))
					);
				} else if (gdcontrols=='smallzoom') {
					GDmap.addControl(
						new MQA.SmallZoom(),
						new MQA.MapCornerPlacement(MQA.MapCorner.TOP_LEFT, new MQA.Size(5,5))
						);
				}
				if (gdradiusint!=0) {
                   var circle = new MQA.CircleOverlay(); 
				   circle.radiusUnit=gdradiusunits;
				   circle.radius=gdradiusint;
				   circle.shapePoints=[gdstartlat, gdstartlong];
				   GDmap.addShape(circle);
				}
				
				GDmap.addControl(new MQA.ViewOptions());

				GDmap.enableMouseWheelZoom();
				
				GDmap.setLogoPlacement(MQA.MapLogo.MAPQUEST,new MQA.MapCornerPlacement(MQA.MapCorner.BOTTOM_RIGHT,new MQA.Size(10,50)));
				GDmap.setLogoPlacement(MQA.MapLogo.SCALES,new MQA.MapCornerPlacement(MQA.MapCorner.TOP_LEFT,new MQA.Size(10000,10000)));
			
			function showError(error){
				switch(error.code) 
					{
						case error.PERMISSION_DENIED:
							alert("User denied the request for Geolocation.");
							break;
						case error.POSITION_UNAVAILABLE:
							alert("Location information is unavailable.");
							break;
						case error.TIMEOUT:
							alert("The request to get user location timed out.");
							break;
						case error.UNKNOWN_ERROR:
							alert("An unknown error occurred.");
							break;
					}
				}
			
			
		  });
		  
		  } 
		 }
      });


      /*function inspecting the route data and generating a narrative for display.*/
      function displayGDNarrative(data){
        if(data.route){
          var legs = data.route.legs, html = '', i = 0, j = 0, trek, maneuver, totaldistance = 0;
          html += '<table><thead>';
          html += '<tr><th class="direction">Direction</th><th class="route">Route</th><th class="distance">Miles</th></tr></thead><tbody>';

          for (; i < legs.length; i++) {
            for (j = 0; j < legs[i].maneuvers.length; j++) {
              maneuver = legs[i].maneuvers[j];
              

              
              
			  if ( j < (legs[i].maneuvers.length - 1) ) {
			    html += '<tr>';
                html += '<td>';
			    if (maneuver.iconUrl) {
                  html += '<img src="' + maneuver.iconUrl + '">  ';
                }
			    for (k = 0; k < maneuver.signs.length; k++) {
                  var sign = maneuver.signs[k];
                  if (sign && sign.url) {
                    html += '<img src="' + sign.url + '">  ';
                  }
                }
				html += '</td><td>' + maneuver.narrative + '</td>';
				html += '<td>' + maneuver.distance.toFixed(1) + '</td>';
				totaldistance += maneuver.distance;
				html += '</tr>';
				} else {
				  if (maneuver.iconUrl) {
                	finalSignHtml = '<img src="' + maneuver.iconUrl + '">  ';
              	  }
				}
              
            }
          }
          html += '<tfoot><tr><td>';
          html += finalSignHtml;
          html += '</td><td>Total Distance</td>';
		  html += '<td>' + totaldistance.toFixed(1) + '</td>';
		  html += '</tr></tfoot>';
		  
          html += '</tbody></table>';
          document.getElementById(gdmapid+'-route').innerHTML = html;
        }
      }
		 
		 
		   
