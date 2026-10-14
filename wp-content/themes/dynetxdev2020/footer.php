	</div>
	<div class="site-footer">
		<div class="footer-blend"></div>
		<div class="container">
			<div class="row">

				<div class="col-12 col-lg-10 footer-main">

					<div class="row footer-info">
						<div class="col-12 col-lg-3 footer-info-item">
							<p class="footer-moto">Stay in touch</p>
						</div>
						<div class="col-12 col-lg-3 footer-info-item">
							<a class="footer-contact" href="mailto:info@dyne-tx.com">info@dyne-tx.com</a>
						</div>
						<div class="col-12 col-lg-auto footer-info-item">
							<a class="footer-contact" href="tel:781-786-8230">781.786.8230</a>
						</div>
						<div class="col-12 col-lg-auto ms-lg-auto footer-info-item footer-socials-col">
							<div class="footer-socials">
								<a href="https://www.linkedin.com/company/dynetx" target="_blank" title="opens in a new window"><img src="<?=get_template_directory_uri(); ?>/images/footer/social-linkedin.svg" alt="LinkedIn"></a>
								<a href="https://twitter.com/dyne_tx" target="_blank" title="opens in a new window"><img src="<?=get_template_directory_uri(); ?>/images/footer/social-x.svg" alt="X"></a>
								<a href="https://www.facebook.com/DyneTherapeutic/" target="_blank" title="opens in a new window"><img src="<?=get_template_directory_uri(); ?>/images/footer/social-facebook.svg" alt="Facebook"></a>
								<a href="https://www.youtube.com/@dynetherapeutics" target="_blank" title="opens in a new window"><img src="<?=get_template_directory_uri(); ?>/images/footer/social-youtube.svg" alt="YouTube"></a>
							</div>
						</div>
					</div>

					<div class="row footer-legal">
						<div class="col-12 offset-lg-3 col-lg-auto footer-legal-item footer-legal-links">
							<a href="/privacy-policy">Privacy Policy</a>
							<a href="/terms-of-service">Terms of Service</a>
							<a href="/privacy-policy/#dyne-therapeutics-inc-consumer-health-data-privacy-policy">Consumer Health Data Policy</a>
						</div>
						<div class="col-12 col-lg-auto ms-lg-auto footer-legal-item footer-copyright-col">
							<span>&copy; <?php echo date("Y"); ?> Dyne Therapeutics, Inc.</span>
						</div>
					</div>

				</div>

				<div class="col-lg-2 footer-mark-col">
					<img src="<?=get_template_directory_uri(); ?>/images/footer/dyne-mark.svg" alt="" class="footer-mark">
				</div>

			</div>
		</div>
	</div>

<div class="popup-main rarediseaseday">
  <div class="p-wrapper">
    <a class="p-close"></a>
    <div class="p-inner">
      <div class="line-1"><img class="rdd-1" src="<?=get_template_directory_uri();?>/images/rdd-1.png" alt="Rare Disease Day"></div>
      <div class="line-2">AT DYNE WE SUPPORT ALL PEOPLE, FAMILIES AND CAREGIVERS LIVING WITH RARE DISEASES <span class="p-red">THIS RARE DISEASE DAY AND EVERY DAY</span></div>
      <div class="line-3"><img class="rdd-logo" src="<?=get_template_directory_uri();?>/images/rdd-logo.png" alt=""><img class="rdd-2" src="<?=get_template_directory_uri();?>/images/rdd-2.png" alt="Rare Disease Day"></div>
    </div>
  </div>
</div>

<!-- RDD Modal -->
<div class="modal fade rdd-banner" id="rareDiseaseDay" tabindex="-1" role="dialog" aria-labelledby="rareDiseaseDay"
	aria-hidden="true" style="display: none;">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				</button>
			</div>
				<div class="modal-body">
					<div class="row h-100">
						<div class="col-12 ms-auto rdd-text">
							WE SUPPORT ALL PEOPLE, FAMILIES <br>AND CAREGIVERS LIVING WITH RARE DISEASES <br>this rare disease day and every day
						</div>
						<div class="col-12 rdd-subline-container ms-auto">
							<div class="row align-items-center rdd-subline">
								<div class="col-3 col-lg-2 rdd-logo"></div>
								<div class="col-9 ms-lg-auto rdd-logo-text">
									<span>29&nbsp;february</span>&nbsp;<span>2024</span> <span>#rarediseaseday</span>
									<a target="_blank" href="https://www.youtube.com/_bvhMWEDz2E" class="btn btn-primary d-none">watch video</a>
								</div>
							</div>
						</div>
					</div>
				</div>
		</div>
	</div>
</div>

<!-- VIDEO Modal -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModal" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered dialog-xl">
    <div class="modal-content">
      <div class="modal-header">
					<button type="button" class="close" data-mdb-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
				<div class="embed-responsive-16x9"></div>
			</div>
    </div>
  </div>
</div>
	

<!-- Thank You Modal -->
<div class="modal fade form-confirmation" id="thanksModal" tabindex="-1" aria-labelledby="thanksModal" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
					<button type="button" class="close" data-mdb-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
				<h3>Thank you, your form has successfully been submitted</h3>
			</div>
    </div>
  </div>
</div>

<!-- Modal External Link -->
<div class="modal fade" id="modalExternal" tabindex="-1" role="dialog" aria-labelledby="modalExternalTitle"  aria-hidden="true">

  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">

    <div class="modal-content">
      <div class="modal-body py-5">
        <button type="button" class="btn close" data-mdb-dismiss="modal" aria-label="Close"><i></i></button>
        
        <div class="row justify-content-center mt-5 mb-4">
		  <div class="col-lg-8">
			<h4 class="text-center">You are now leaving <br>the Dyne Therapeutics corporate website</h4>

			<div class="btn-line d-flex justify-content-center mt-5">
				<a class="btn btn-primary btn-lg" target="_blank" title="Opens in a new tab">Continue</a>
				<button type="button" class="btn btn-secondary btn-lg" data-mdb-dismiss="modal" aria-label="Close">Stay on page</button>
			</div>
		</div>
		</div>
				
      </div>
    </div>
  </div>
</div>	


<?php wp_footer(); ?>


<style>
    /*--------------------------------------------------------------
POPUP
--------------------------------------------------------------*/
    .popup-wrapper { display: none; z-index: 10000; position: fixed; left: 0; right: 0; top: 0; bottom: 0;}
    .popup-wrapper.popup-open { display: block;}
    .popup-bg { position: fixed; left: 0; right: 0; top: 0; bottom: 0; background: rgba(0,0,0,.75);}
    .popup { position: absolute; overflow: auto; background: #fff;}
    .popup .close { position: absolute; right: 8px; top: 8px; width: 20px; height: 20px; cursor: pointer; z-index: 1}
    .popup .close:before, .popup .close:after { content: ""; position: absolute; left: 50%; top: 0; width: 2px; height: 20px; background: #7d1761;}
    .popup .close:before { transform: rotate(45deg);}
    .popup .close:after  { transform: rotate(-45deg);}
    .popup .close:hover:before, .popup .close:hover:after { background: #000;}
    .popup .inner { padding: 34px; position: relative; }
	
    @media (min-width: 769px) {
        .popup { left: 50%; top: 50%; transform: translate(-50%,-50%);  max-height: 90%;}
        .popup .leadership-list .pic { margin-bottom: 24px;}
        .popup .md-flex-container > div:not(:first-child) { padding-left: 40px;}
				.popup .videoPopup-player { width: 560px; height: 315px;}
    }
    @media (max-width: 768px) {
        .popup { left: 20px; right: 20px; top: 20px; bottom: 20px; height: fit-content;}
        .popup .leadership-list .pic { margin-bottom: 24px;}
				.popup .inner { padding-left: 0; padding-right: 0; }
				.popup .inner:before { content: ''; display: block;width: 0;height: 0; padding-top: 60%; }
			 	.popup .inner iframe { position: absolute; top: 34px; /*left: 0;*/ width: 100%; /*height: 72%; */ height: calc(193vw/3.75)}
   }

</style>

<div id="videoPopup" class="popup-wrapper">
    <div class="popup-bg" onclick="CloseVideoPopup();"></div>
    <div class="popup">
        <a class="close popupclosebutton" onclick="CloseVideoPopup();"></a>
        <div class="inner" id="videoPlaceholderHp">

        </div>
    </div>
</div>
<script>
    var videoContent = '<iframe class="videoPopup-player" src="https://www.youtube.com/embed/p_hRmpj5XRo?rel=0&autoplay=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';

    function OpenVideoPopup() {
        jQuery('#videoPlaceholderHp').html(videoContent);
        jQuery('#videoPopup').toggleClass('popup-open');
    }

    function CloseVideoPopup() {
        jQuery('#videoPopup').toggleClass('popup-open');
        jQuery('#videoPlaceholderHp').html("");
    }

    jQuery(document).ready(function($) {
        $( document ).on( 'keydown', function ( e ) {
            if ( e.keyCode === 27 && jQuery('#videoPopup').hasClass("popup-open")) { // ESC
                CloseVideoPopup();
            }
        });
    });


	<?php

	if (isset($_GET['show_video'])) {
	?>
    jQuery(document).ready(function($) {
        OpenVideoPopup();
    });
	<?php
	}
	?>
</script>

</body>
</html>
