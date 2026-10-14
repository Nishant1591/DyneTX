// Gate the extreme Google Sans Flex weights (Thin/Light — see .h2-new-thin
// and .h1-secondary-1 in _refresh-styles.scss) behind a "fonts really
// loaded" class instead of requesting them immediately. Some browsers
// mis-render a low weight (100/300) against the fallback stack while the
// real font is still loading, so those rules request a safe 400 by default
// and only switch to the real weight once this class is added.
//
// The same class also reveals the Google Sans Flex headings, which are held
// invisible (.fonts-pending, set by an inline script in <head>) until the
// font is ready — so nobody sees the fallback-to-real-font weight jump.
// document.fonts.load() is used instead of document.fonts.ready: ready can
// resolve before the browser has even started fetching a lazily-requested
// font, which would reveal the headings too early. One variable file covers
// every weight, so a single load() is enough. If the font hasn't arrived
// after 2s (slow network) or fails, .fonts-timeout reveals the headings in
// the fallback font rather than leaving them invisible; .fonts-loaded still
// switches the weight in if the font arrives later.
(function () {
	var root = document.documentElement;
	if (!(document.fonts && document.fonts.load)) return;

	setTimeout(function () {
		if (!root.classList.contains('fonts-loaded')) {
			root.classList.add('fonts-timeout');
		}
	}, 2000);

	document.fonts.load('100 1em "Google Sans Flex"').then(function (faces) {
		if (faces.length) {
			root.classList.add('fonts-loaded');
		} else {
			root.classList.add('fonts-timeout');
		}
	}, function () {
		root.classList.add('fonts-timeout');
	});
})();

const devices = new RegExp('Android|webOS|iPhone|iPad|iPod|BlackBerry|BB|PlayBook|IEMobile|Windows Phone|Kindle|Silk|Opera Mini', "i");
var devType;

if (devices.test(navigator.userAgent)) {
	//console.log("This is a mobile device" + navigator.userAgent)
	devType = true;
}else{
//			console.log("This is a PC")
	devType = false;
}



// MULTICAROUSEL INDICATORS

$(document).ready(function () {
    $(".scrolltosource").on("click", function (e) {
        // get data from data-anchor attribute
        var anchor = $(this).attr("data-anchor");
        var elementID = $("#collapse" + anchor);
        if (elementID.length > 0) {
            // get previous div
            var previousDiv = elementID.prev();
            // scroll to element
            $("html, body").animate(
                {
                    scrollTop: previousDiv.offset().top - 100,
                },
                500
            );
        } else {
            return true;
        }
    })

    if (window.location.pathname.includes('/pipeline') && window.location.hash && $(".mobile-nav-activatoion").is(":visible")) {
        const targetUrl = window.location.pathname + window.location.hash;
        const targetLink = document.querySelector(`a.scrolltosource[href="${targetUrl}"]`);

        if (targetLink) {
            console.log('pipeline click imitation')
            targetLink.click();
        }
    }

    var timeoutLength;
    var $carousel = $("#quotesCarousel .multi-carousel-inner");
    var $carouselItems = $("#quotesCarousel .multi-carousel-item");
    var $indicators = $(".carousel-indicators button");
    var $controlPrev = $("#quotesCarousel .carousel-control-prev");
    var $controlNext = $("#quotesCarousel .carousel-control-next");
    var itemWidth = $carousel[0].scrollWidth / $indicators.length; // width of one step
// 			console.log('itemWidth: '+ itemWidth );
		var currentIndex = 0;

    // Active indicator refreshing
    function updateIndicators() {
        var scrollLeft = $carousel.scrollLeft();
// 				console.log('scrollLeft: ' + scrollLeft);
//        var activeIndex = Math.round(scrollLeft / itemWidth); // Calculating the current slide's index
        var activeIndex = currentIndex; // Calculating the current slide's index
//console.log('activeIndex:' + activeIndex);
        $indicators.removeClass("active").eq(activeIndex).addClass("active");
    }

    // Click upon indicators - custom scrolling
    $indicators.on("click", function () {
        var index = $(this).index();
				var $carouselFirstItem = $("#quotesCarousel .multi-carousel-item:first-child");
				var direction;
				if (index > currentIndex) direction = -1;
				if (index < currentIndex) direction = 1;
				var diff = index - currentIndex;
				if (diff < 0) diff = diff * -1;
//					console.log('Indicator Index: ' + index);
//					console.log('Current Index: ' + currentIndex);
//					console.log('difference: ' + diff);
			
					if (direction < 0 ) {  // scrolling left
						if (devType) {
							timeoutLength = 500;
						}else if (diff == $indicators.length - 1 ){
							timeoutLength = 50;
						}else{
							timeoutLength = 200;
						}

						$carouselFirstItem.animate({ marginLeft: itemWidth * -diff }, 500, function() {
							var $elements = $carousel.children().slice(0, diff);
							setTimeout(function() {
								var $detachedElements = $elements.detach();
								$carouselFirstItem.css('margin-left',0);
								$carousel.append($detachedElements);
							}, timeoutLength);
//							console.log('direction: ' + direction);
						});
					}
					if (direction > 0 ) { // scrolling right
								var $elements = $carousel.children().slice(-diff);
								var $detachedElements = $elements.detach();
//								console.log('direction: ' + direction);
								$carousel.prepend($detachedElements);
								var $carouselNewFirstItem = $("#quotesCarousel .multi-carousel-item:first-child");
								$carouselNewFirstItem.css('margin-left', itemWidth * -diff )
								$carouselNewFirstItem.animate({ marginLeft: 0 }, 500)

						}
			
				currentIndex = index;
				updateIndicators();
    });

    $controlPrev.on("click", function () {
			if (currentIndex > 0)	currentIndex--;
			else currentIndex = $carouselItems.length - 1;
//				console.log('currentIndex:' + currentIndex);
        updateIndicators();
    });

    $controlNext.on("click", function () {
			if (currentIndex < $carouselItems.length - 1)	currentIndex++;
			else currentIndex = 0;
//				console.log('currentIndex:' + currentIndex);
        updateIndicators();
    });

    // Updating indicators onload
    updateIndicators();
	


	var startX = 0;
  var startY = 0;

  // Touching scfreen
  $carousel.on('touchstart', function(e) {
    var touch = e.originalEvent.touches[0];
    startX = touch.pageX;
    startY = touch.pageY;
  });

  // Moving finger 
  $carousel.on('touchmove', function(e) {
    e.preventDefault();  // prevent default behavior (e.g. scrolling)
  });

  // Finger off screen
  $carousel.on('touchend', function(e) {
    var touch = e.originalEvent.changedTouches[0];
    var endX = touch.pageX;
    var endY = touch.pageY;

    var diffX = endX - startX;
    var diffY = endY - startY;

    // Defining swipe direction
    if (Math.abs(diffX) > Math.abs(diffY)) {  // hor swipe
      if (diffX > 0) {
// console.log('Swipe right');
				if (currentIndex > 0)	currentIndex--;
				else currentIndex = $carouselItems.length - 1;
      } else {
// console.log('Swipe left');
				if (currentIndex < $carouselItems.length - 1)	currentIndex++;
				else currentIndex = 0;
//				console.log('currentIndex:' + currentIndex);
      }
			updateIndicators();

    } 
  });



	
	
});									




// external popup

const extModal = document.getElementById('modalExternal');
modalExternal.addEventListener('show.mdb.modal', (e) => {
    
    	const button = e.relatedTarget;

		const url = button.getAttribute('data-mdb-url');

		const extBtn = extModal.querySelector('.btn-primary');

		extBtn.href = `${url}`;
})





// team popups
const bioModal = document.getElementById('modalTeam');
if (bioModal !== null ) {
	jQuery(".btn-arrow-left").click(function() {
		setModalContent(jQuery( "#" + jQuery(this).attr('data-id') )[0]);
	});

	jQuery(".btn-arrow-right").click(function() {
		setModalContent(jQuery( "#" + jQuery(this).attr('data-id') )[0]);
	});

		$('.read-more').on('click', function(){
			$(this).addClass('d-none');
			$('.letter-body').removeClass('d-none');
			
		})

		$('.read-less').on('click', function(){
			$('.read-more').removeClass('d-none');
			$('.letter-body').addClass('d-none');
			
		})

	function setModalContent(button) {
		const imgPath = button.getAttribute('data-mdb-imgpath');
		const name = button.getAttribute('data-mdb-name');
		const position = button.getAttribute('data-mdb-position');
		const bio = button.getAttribute('data-mdb-bio');
        const additional_class = button.getAttribute('data-class');
		// Update the modal's content.

		const bioImg 				= bioModal.querySelector('.card-img-top');
		const bioName1			= bioModal.querySelector('.card-title-1');
		const bioName2 			= bioModal.querySelector('.card-title-2');
		const bioPosition1 	= bioModal.querySelector('.card-position-1');
		const bioPosition2 	= bioModal.querySelector('.card-position-2');
		const bioDescr 			= bioModal.querySelector('.card-description');
		const modalDialog		= bioModal.querySelector('.modal-dialog');

		bioImg.src = `${imgPath}`;
		bioImg.alt = `${name}`;
		bioName1.textContent = `${name}`;
		bioName2.textContent = `${name}`;
		bioPosition1.textContent = `${position}`;
		bioPosition2.textContent = `${position}`;
		bioDescr.innerHTML = `${bio}`;

		if (imgPath == "")
			jQuery(".image_card").hide();
		else
			jQuery(".image_card").show();

    if (additional_class) {
      bioModal.classList.add("noimage")
    } else {
      bioModal.classList.remove("noimage")
    }

    jQuery(".btn-arrow-right").attr("data-id", button.getAttribute('data-next'));
		jQuery(".btn-arrow-left").attr("data-id", button.getAttribute('data-prev'));
	}
}


$(document).ready(function(){
	

		$('.custom-cover').on('click', function(){
			var $this = $(this);
			var $embed	 = $this.attr('embd-data-url');
			var $width 	 = '800';
			var $height  = '450';
			var $title   = $this.attr('embd-data-title');
			
			var newHtml = '<iframe width="'+$width+'" height="'+$height+'" src="https://www.youtube.com/embed/'+ $embed +'?autoplay=1&mute=1" title="' + $title + '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>'
			
			$(this).removeClass('custom-cover').html(newHtml);
		})
	
	
		$('.video-link').on('click', function(){
			var $this = $(this);
			var $embed	 = $this.attr('embd-data-url');
			var $width 	 = '800';
			var $height  = '450';
			var $title   = $this.attr('embd-data-title');
			
			var newHtml = '<iframe width="'+$width+'" height="'+$height+'" src="https://www.youtube.com/embed/'+ $embed +'?autoplay=1&mute=1" title="' + $title + '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>'
			
			$('#videoModal .embed-responsive-16x9').html(newHtml);
		})
	
		$('#videoModal').on('hide.bs.modal', function(){
			$(this).find('.embed-responsive-16x9').empty();
		})

	if (!getRDDCookie("showRareDiseaseDayPopup")) {
		//$('#rareDiseaseDay').modal('show'); //  NEW BANNER INIT
	} else {
		//$('#rareDiseaseDay').modal('hide'); //  NEW BANNER INIT
	}

	function setRDDCookie(name,value,days) {
		var expires = "";
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days*24*60*60*1000));
			expires = "; expires=" + date.toUTCString();
		}
		document.cookie = name + "=" + (value || "")  + expires + "; path=/";
	}

	function getRDDCookie(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for(var i=0;i < ca.length;i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1,c.length);
			if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
		}
		return null;
	}

	setRDDCookie('showRareDiseaseDayPopup', 1);



/* BEG DESIGNER FUNCTIONS */
	
	
			var UserWayInterval = setInterval(function() {
				if ($('#userwayAccessibilityIcon').length) {
					//console.log('element has been added into dom: ' + $('#userwayAccessibilityIcon').length);
					
					if ($('#userwayAccessibilityIcon').css('left') != '') {
						$('#userwayAccessibilityIcon').css('left','');
						//console.log('css has been changed');
					}
					
					//clearInterval(UserWayInterval);
					
				}
			}, 300);	

					$('#userwayAccessibilityIcon').on('change', function() {
						
//						console.log('element changed');
					})
	
				if (getCookie('modal') != 'shown' ) {
					console.log(getCookie('modal'))
					//$('#rareDiseaseDay').modal('show'); //  NEW BANNER INIT
					$('#rareDiseaseDay').on('shown.bs.modal', function (e) {
					    setCookie('modal', 'shown');
					});
				} else 
					console.log('modal shown earlier')

				function getCookie(name) {
				  let matches = document.cookie.match(new RegExp(
				    "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
				  ));
				  return matches ? decodeURIComponent(matches[1]) : undefined;
				}
				function setCookie(name, value) {
				  options = {
				    path:   '/',
				    domain: location.host
				  };
				  if (options.expires instanceof Date) {
				    options.expires = options.expires.toUTCString();
				  }
				  let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);
				  for (let optionKey in options) {
				    updatedCookie += "; " + optionKey;
				    let optionValue = options[optionKey];
				    if (optionValue !== true) {
				      updatedCookie += "=" + optionValue;
				    }
				  }
				  document.cookie = updatedCookie;
				}

				

				// Additional info label
				$('.perspective-articles .btn-plus-collapse').on('click', function(){
					var lbl = $('.perspective-articles .red-label');
					if ($(this).hasClass('collapsed'))	$(lbl).html('Less content');
					else $(lbl).html('Additional content');
				});

	
				// SideNav Collapse Button Initialization
				var btn = '<a data-activates="slide-out" class="button-collapse ml-auto mr-lg-3 brdr" style="display: inline-block; line-height: 24px;"><i></i></a>';
				$(".side-nav").append(btn);

				$('.button-collapse').on('click', function(){
					$('.side-nav').addClass("show");
					$('#qqq').removeClass('d-none');
					//console.log('sidenavBackdrop show');
				});
	
				$('#qqq').on('click', function(){
					//console.log("This is a PC")
					$(this).addClass('d-none');
					$('.side-nav').removeClass("show");
				});

				$('.button-collapse').on('focus', function(){
					$('.side-nav').addClass("show");
					$('#qqq').removeClass('d-none');
					//console.log('focused');
				});

				$('.side-nav .button-collapse').on('click', function(){
					$('.side-nav').removeClass("show");
					$('#qqq').addClass('d-none');
					//console.log('sidenavBackdrop removed by button');
				});
	
				$('.side-nav .tab-pane .nav-link').on('click', function(){
					$('.side-nav').removeClass("show");
					$('#qqq').addClass('d-none');
					//console.log('sidenavBackdrop removed by link');
				});
		
			// Checking for IE for some case
				if (navigator.userAgent.match(/msie/i) || navigator.userAgent.match(/trident/i) ){
						$("html").addClass("ie");
				};

				/*
				$('.button-collapse').sideNav({
					edge: 'right', // Choose the horizontal origin
					closeOnClick: false, // Closes side-nav on &lt;a&gt; clicks, useful for Angular/Meteor
					breakpoint: 3992, // Breakpoint for button collapse
					menuWidth: 320, // Width for sidenav
					timeDurationOpen: 300, // Time duration open menu
					timeDurationClose: 200, // Time duration open menu
					timeDurationOverlayOpen: 50, // Time duration open overlay
					timeDurationOverlayClose: 200, // Time duration close overlay
					easingOpen: 'easeOutQuad', // Open animation
					easingClose: 'easeOutCubic', // Close animation
					showOverlay: false, // Display overflay
					showCloseButton: true // Append close button into siednav
				});
				*/
		
				// STICKY HEADER ------------------------------------------------------------------------------------------------

				checkPosition ();	
		
				$(window).on('scroll',function(){
					checkPosition ();
				});
				
				var srch = false;
				$('#top-bar-search').click(function(){
					srch = true;
				});

	
				function checkPosition () {
					var $header = $('.header');
					var $offsetItem = $('.hero');
					var $body = $('body');

					var hdrOffset = $offsetItem.height();
					var hdrHeight = $header.height();
					var winSrlTop = $(window).scrollTop();
					var trigPoint = hdrOffset - winSrlTop - hdrHeight;
					
					//alert(trigPoint);

				
					if (trigPoint <= 0) {
						$header.addClass('inverse');
					}else{
						$header.removeClass('inverse');
					}
				}
		
				// STICKY HEADER ------------------------------------------------------------------------------------------------
				
				checkMenu ();	
		
				$(".nav-link").on('click', function(){
					$(".nav-pills").addClass('hasSelectedItems');
				});
		
				function checkMenu () {
					if($(".nav-pills .active").length>0) {
						$(".nav-pills").addClass('hasSelectedItems');
					}else{
						$(".nav-pills").removeClass('hasSelectedItems');
					}
				}
	
			
	
				// Collapsible Pipeline  ------------------------------------------------------------------------------------------------
	
				if (isMobile()) {
					$(".pipeline-chart-24 .btn-plus-collapse").removeAttr('href')
					$(".pipeline-chart-25 .btn-plus-collapse").removeAttr('href')
					$(".pipeline-chart-26 .btn-plus-collapse").removeAttr('href')
				}else{
					$(".pipeline-chart-24 .btn-plus-collapse").removeAttr('data-mdb-collapse-init')
					$(".pipeline-chart-25 .btn-plus-collapse").removeAttr('data-mdb-collapse-init')
					$(".pipeline-chart-26 .btn-plus-collapse").removeAttr('data-mdb-collapse-init')
				}
	
	
	
				// Top Nav  ------------------------------------------------------------------------------------------------

				function isMobile() {
					if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
						return true;
					}
					return false;
				}

					
					$('.version-a .nav-item').mouseenter(function(){
						$(this).children('.nav-link').next().slideDown(200);
						$(this).children('.nav-link').addClass('hover');
					});
					
					$('.version-a .nav-item').mouseleave(function(){
						$(this).children('.nav-link').removeClass('hover').next().slideUp(50);
					});
					
					$('.version-a .dropdown-menu').mouseleave(function(){
						//console.log('out');
						$(this).slideUp(50).parent().removeClass('hover');
					});
					

/* END DESIGNER FUNCTIONS */

  var $url = window.location.pathname;
  var $hash = window.location.hash;
  console.log('url: '+$url);
  console.log('hash: '+$hash);
  if ($url == '/about_us/') {
    console.log('current url: ' + window.location.pathname);
    $('.nav.flex-column.nav-pills').addClass('hasSelectedItems');
    $('#v-pills-1219-tab').addClass('active');
    $('#v-pills-1219').addClass('active');
    $('#v-pills-1219').addClass('show');
    }
    
  if ($hash == '#bod') $('.action-bod').trigger('click');
  if ($hash == '#scientific') $('.action-sab').trigger('click');
  
  $('.p-close').click(function(){
    $('body').removeClass('popup-open');
  });
  
// Community popup

  $('.community-popup-close, .community-overlay').click(function(){
    $('.community-popup, .community-overlay').hide();
	$('.embed-responsive-item').attr('src', $('.embed-responsive-item').attr('src'));
  });
  
  $('.community-popup-button').click(function(){
    var go = $(this).data("video-url");
    var left = (screen.width-700)/2;
    $('.community-popup').css('left', left);
    console.log(left);
    go = '<iframe allowfullscreen class="embed-responsive-item" frameborder="0" src="'+go+'?rel=0&amp;autohide=1">&gt;</iframe>';        
    $('.community-video').html(go);
    $('.community-popup, .community-overlay').show();
  });

});