<!doctype html>
<html lang="en">
<?php include('head.php'); ?>


<!-- Uncomment below to activate Accessibility by UserWay plugin -->

<script>(function(d){var s = d.createElement("script");s.setAttribute("data-account", "AsYxVDdeyq");s.setAttribute("src", "https://cdn.userway.org/widget.js");(d.body || d.head).appendChild(s);})(document)</script><noscript>Please ensure Javascript is enabled for purposes of <a href="https://userway.org">website accessibility</a></noscript>

<body>
	
<a href="#main-content" class="non-focus screen-reader-text">Skip to main content</a>

<!-- Google Tag Manager (noscript) -->
<noscript><iframe title="Google Tag Manager" src="https://www.googletagmanager.com/ns.html?id=GTM-KKPDWCQ"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<div class="wrapper <?php if(!is_front_page()) echo ' pages ';?> <?php if(is_front_page()) echo ' home ';?> <?php echo defined('BODY_CLASS')?BODY_CLASS:''; ?> <?php echo get_post_meta(get_the_ID(), 'additional_body_class', true)?>">

		<div class="container-fluid header-wrapper" role="banner">
			<div class="row">
				<div class="col-12 header fixed-top d-flex align-items-center version-a">

					<!-- Logo -->
						<a href="/" class="col-xs logo ms-lg-3 mx-xl-0"><img src="<?=get_template_directory_uri(); ?>/images/logo-shape.svg" height="50"	class="img-fluid" alt="Dyne logo"></a>
					<!--/. Logo -->
					
<nav class="navbar navbar-expand-lg">
    <ul class="navbar-nav me-auto">
			
<?php



		$menu_name = 'top_menu';
		$locations = get_nav_menu_locations();
		if (isset($locations[$menu_name])) {
			$menu = wp_get_nav_menu_object($locations[$menu_name]);
			$menu_items = wp_get_nav_menu_items($menu->term_id);

      /*echo '<pre>';
      print_r($menu);
      echo '</pre>';

      echo '<pre>';
      print_r($menu_items);
      echo '</pre>';*/


            $level_one = array();
			foreach ($menu_items as $i) {
                //echo '<pre>';
                //print_r($i);
                //echo '</pre>';
				if ($i->menu_item_parent == 0) {
                    $is_active = DyneIsMenuItemActive($i);
                    foreach ($menu_items as $subitem) {
                        if ($subitem->menu_item_parent == $i->ID) {
                            $is_active = $is_active || DyneIsMenuItemActive($subitem);
                        }
                    }

					echo '<li class="nav-item dropdown">';

                    if (!in_array('external-link', $i->classes)) {

                        echo '          <a id="navbarDropdownMenuLink_' . $i->ID . '" class="nav-link dropdown-toggle ' . ($is_active ? 'active' : '') . '" aria-haspopup="true" aria-expanded="false" href=' . $i->url . '>' . $i->title . '</a>';
                        echo '			<div class="dropdown-menu dropdown-primary" aria-labelledby="navbarDropdownMenuLink_' . $i->ID . '">';

                        foreach ($menu_items as $subitem) {
                            if ($subitem->menu_item_parent == $i->ID) {
                                echo '<a class="dropdown-item" href="' . $subitem->url . '">' . $subitem->title . '</a>';
                            }
                        }

                        echo '</div>';
                    } else {
                        echo '<a class="nav-link dropdown-toggle" href="' . $i->url . '"
										data-mdb-modal-init 
										data-mdb-target="#modalExternal" 
										data-mdb-url="' . $i->url . '" 
									   >' . $i->title . '</a>
                        ';
                    }

                    echo '</li>';
				}
			}
		}
?>
				
    </ul>
</nav>

					<!-- SideNav slide-out button -->
					<a data-activates="slide-out" class="mobile-nav-activatoion button-collapse ms-auto me-lg-3" href="javascript:;"><i></i><span class="sr-only">Open navigation</span></a>

					<!-- Sidebar navigation -->
					<div id="slide-out" class="side-nav fixed pb-5 right-aligned" style="transform: translateX(100%);">

						<!-- Logo -->
							<a href="/" class="col-xs logo ms-lg-3 mx-xl-0 d-md-none"><img src="<?=get_template_directory_uri(); ?>/images/logo-shape.svg" height="50"	class="img-fluid" alt="Dyne logo"></a>
						<!--/. Logo -->

						<!-- Side navigation links -->
						<div class="row" role="navigation">
							<div class="col-7 col-md-7">
								<div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
									<?php
											$menu_name = 'top_menu';
											$locations = get_nav_menu_locations();
											if (isset($locations[$menu_name])) {
												$menu = wp_get_nav_menu_object($locations[$menu_name]);
												$menu_items = wp_get_nav_menu_items($menu->term_id);

												$level_one = array();
												foreach ($menu_items as $i) {
													if ($i->menu_item_parent == 0) {
														$level_one[] = $i->ID;
                                                        if (in_array('external-link', $i->classes)) {
                                                            echo '<a class="nav-link" href="'.$i->url.'"
                                                            data-mdb-modal-init 
                                                            data-mdb-target="#modalExternal" 
                                                            data-mdb-url="'.$i->url.'" 
                                                           >'.$i->title.'</a>';
                                                        } else {
                                                            echo '<a class="nav-link" id="v-pills-' . $i->ID . '-tab" data-mdb-pill-init href="#v-pills-' . $i->ID . '" role="tab"	aria-controls="v-pills-' . $i->ID . '" aria-selected="false"><span>' . $i->title . '</span></a>';
                                                        }
													}
												}
											}
									?>
								</div>
							</div>
							<div class="col-5 col-md-5 ps-0">
								<div class="tab-content pe-0" id="v-pills-tabContent">
									<?php 
										//var_dump($level_one); die;
										foreach ($level_one as $k => $v) {
											echo '<div class="tab-pane fade /show /active" id="v-pills-'.$v.'" role="tabpanel" aria-labelledby="v-pills-'.$v.'-tab">
												<ul class="nav smooth-scroll">';
												foreach ($menu_items as $i) {
													if ($i->menu_item_parent == $v) {
                                                        echo '<li class="nav-item">';
                                                        if (strpos($i->url, '/pipeline/#') !== false && strpos($_SERVER['REQUEST_URI'], '/pipeline') === 0) {
                                                            $section_code = strtoupper(substr($i->url, strpos($i->url, '#') + 1));
                                                            echo '<a class="nav-link scrolltosource" data-anchor="'.$section_code.'" data-mdb-collapse-init data-mdb-target="#collapse'.$section_code.'" aria-expanded="false" aria-controls="collapse'.$section_code.'" href="'.$i->url.'">'.$i->title.'</a>';
                                                        } else {
                                                            echo '<a class="nav-link" href="'.$i->url.'">'.$i->title.'</a>';
                                                        }

															// third-level
															$aTmp3rdlevel = array();
															foreach ($menu_items as $t) {
																	if ($t->menu_item_parent == $i->ID) {
																		$aTmp3rdlevel[] = $t;
																	}
															}

															if (sizeof($aTmp3rdlevel) > 0) {
																	echo '<ul class="submenu-level3">';
																	foreach ($aTmp3rdlevel as $t) {
																		echo '<li class="nav-item"><a class="nav-link" href="'.$t->url.'">'.$t->title.'</a>';
																	}
																	echo '</ul>';
															}

														echo '</li>';
													}
												}
											echo '</ul></div>';
										}
									?>
								</div>
							</div>
						</div>					
						<!--/. Side navigation links -->

						<!-- Side navigation bottom links -->
						<div class="row mt-5 bottom-links">
							<div class="col-7 col-md-7">
								<div class="nav flex-column nav-pills" aria-orientation="vertical">
									<a class="nav-link disabled"><span>Stay in Touch</span></a>
								</div>
							</div>
							<div class="col-5 col-md-5 justify-content-start">
								<div class="tab-content px-0">
									<div class="tab-pane fade show active">
										<ul class="nav">
											<li class="nav-item">
												<a class="nav-link" href="mailto:info@dyne-tx.com">info@dyne-tx.com</a>
											</li>
											<li class="nav-item">
												<a class="nav-link" href="tel:781-786-8230">781.786.8230</a>
											</li>
											<li class="nav-item socials">
												<a class="nav-link  me-2 me-md-3" target="_blank" href="https://www.linkedin.com/company/dynetx"><i class="ico-in ico-xs"></i><span class="sr-only">www.linkedin.com</span></a>
												<a class="nav-link  me-2 me-md-3" target="_blank" href="https://twitter.com/dyne_tx"><i class="ico-tw ico-xs"></i><span class="sr-only">twitter.com</span></a>
												<a class="nav-link  me-2 me-md-3" target="_blank" href="https://www.facebook.com/DyneTherapeutic/"><i class="ico-fb ico-xs"></i><span class="sr-only">www.facebook.com</span></a>
												<a class="nav-link" target="_blank" href="https://www.youtube.com/@dynetherapeutics"><i class="ico-youtube ico-xs"></i><span class="sr-only">www.youtube.com</span></a>
											</li>
										</ul>
									</div>
								</div>
							</div>
						</div>					
						<!--/. Side navigation bottom links -->

					</div>
					<!--/. Sidebar navigation -->
					<div id="qqq" class="sidenavBackdrop d-none"></div>
				</div>
			</div>
		</div>
    <?php if (!is_front_page()): ?>
    <?php
    $post_id = apply_filters('get_parent_post_id', get_the_ID());
    $is_perspectives = preg_match('/\/perspectives\/[a-zA-Z0-9].*/', $_SERVER['REQUEST_URI']) || preg_match('/\/perspectives/', $_SERVER['REQUEST_URI']);
    // Third-level pages (personal stories, privacy policy, etc.): editor
    // ticks "Hero: Third Level" (ACF true/false field, hero_third_level)
    // in the page editor — no URL/slug pattern to maintain in code.
    $is_third_level = get_field('hero_third_level', $post_id);
    ?>
    <?php if ($is_perspectives): ?>
		<style>
			.hero {
				background-image: url(/wp-content/uploads/hero-perspective.webp);
			}
			@media (orientation : portrait) {
				.hero {
					background-image: url(/wp-content/uploads/hero-perspective-mob.webp);
				}
			}
		</style>
    <?php endif; // $is_perspectives ?>

		<div class="container-fluid">
			<div class="row">
        <?php if ($is_perspectives): ?>
              <div class="col-12 hero perspectives">
              <div class="hero-title-2">perspectives</div>
              <div class="hero-subtitle">We are passionate about using our voices to advance our mission of developing life-transforming therapies for people living with serious muscle diseases.</div>
            </div>
        <?php elseif ($is_third_level): ?>

          <div class="hero hero-third-level">
          </div>

        <?php else: ?>

          <div class="hero hero-secondary">

            <div class="hero-bg-base bg-img">
              <picture>
                <source srcset="<?php echo MultiPostThumbnails::get_post_thumbnail_url(get_post_type($post_id), 'secondary-image', $post_id); ?>" media="(max-width: 767.98px)">
                <img src="<?php echo get_the_post_thumbnail_url($post_id); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?>">
              </picture>
            </div>

            <?php if (get_field('first_line', $post_id)) echo '<p class="h1-secondary-1">'.get_field('first_line', $post_id).'</p>';?>

            <?php if (MultiPostThumbnails::has_post_thumbnail(get_post_type($post_id), 'hero-overlay-image', $post_id)): ?>
            <div class="hero-bg-overlay bg-img">
              <img src="<?php echo MultiPostThumbnails::get_post_thumbnail_url(get_post_type($post_id), 'hero-overlay-image', $post_id); ?>" alt="">
            </div>
            <?php endif; ?>

            <?php if (get_field('second_line', $post_id)) echo '<p class="h1-secondary-2">'.get_field('second_line', $post_id).'</p>';?>

          </div>

        <?php endif; ?>

			</div>
		</div>
    <?php endif; ?>

    <?php if (is_front_page()): ?>
    <div class="container-fluid">
      <div class="row">
        <div class="col-12 hero hero-media">
          <video class="hero-video" muted playsinline></video>
          <video class="hero-video" muted playsinline></video>
          <img class="hero-video hero-video-still" data-variant="mobile" src="<?=get_template_directory_uri(); ?>/images/hero/hero-home-mobile-lastframe.webp" alt="">
          <img class="hero-video hero-video-still" data-variant="desktop" src="<?=get_template_directory_uri(); ?>/images/hero/hero-home-desktop-lastframe.webp" alt="">
          <script>
            (function () {
              var wrap = document.currentScript.parentNode;
              var videos = Array.prototype.slice.call(wrap.querySelectorAll('video.hero-video'));
              var stills = Array.prototype.slice.call(wrap.querySelectorAll('.hero-video-still'));
              var mql = window.matchMedia('(orientation: landscape)');
              var activeIndex = 0;
              var ended = false;
              var currentVariant = null;
              var base = '<?=get_template_directory_uri(); ?>/images/hero/';

              // Each poster is the corresponding video's own first frame, so
              // its pixel dimensions are that video's real aspect ratio —
              // preload both (a few KB each) to size the container for
              // whichever variant is incoming without waiting on the video's
              // own (much larger) metadata.
              var posterDims = {};
              ['mobile', 'desktop'].forEach(function (variant) {
                var img = new Image();
                img.onload = function () {
                  posterDims[variant] = { w: img.naturalWidth, h: img.naturalHeight };
                };
                img.src = base + 'hero-home-' + variant + '.webp';
              });

              function stillFor(variant) {
                return stills.filter(function (s) { return s.dataset.variant === variant; })[0];
              }

              function setHeightFor(variant, fallbackRatio) {
                var w = wrap.getBoundingClientRect().width;
                var d = posterDims[variant];
                var ratio = d ? d.h / d.w : fallbackRatio;
                if (!w || !ratio) return;
                wrap.style.setProperty('height', (w * ratio) + 'px', 'important');
              }

              function seekTo(video, time) {
                // Set currentTime as soon as metadata is known — before the
                // browser has decoded/painted anything yet — so the very
                // first frame it ever shows is already the resumed one,
                // instead of briefly painting frame 0 and jumping once a
                // later seek lands (which read as a flashed poster).
                var applied = false;
                function apply() {
                  if (applied) return;
                  applied = true;
                  clearTimeout(fallbackTimer);
                  var d = video.duration;
                  video.currentTime = isFinite(d) ? Math.min(time, d) : time;
                  video.play();
                }
                video.addEventListener('loadedmetadata', apply, { once: true });
                // Safety net in case metadata never loads.
                var fallbackTimer = setTimeout(function () {
                  video.removeEventListener('loadedmetadata', apply);
                  apply();
                }, 4000);
              }

              // Once a video plays through to its natural end (no loop), both
              // variants become permanently static — swap in their
              // pre-rendered last-frame images (same content, so the swap is
              // invisible) and stop touching <video> for good, so every later
              // orientation change is a plain, instant image crossfade with no
              // seeking or readiness concerns.
              function finalize(variant) {
                if (ended) return;
                ended = true;
                stillFor(variant).style.opacity = '1';
                videos.forEach(function (v) {
                  v.style.opacity = '0';
                  v.pause();
                });
              }

              videos.forEach(function (video) {
                video.addEventListener('ended', function () {
                  finalize(video.dataset.variant);
                });
              });

              function applySource() {
                var variant = mql.matches ? 'desktop' : 'mobile';
                if (variant === currentVariant) return;
                currentVariant = variant;
                setHeightFor(variant, null);

                if (ended) {
                  stills.forEach(function (s) {
                    s.style.opacity = s.dataset.variant === variant ? '1' : '0';
                  });
                  return;
                }

                var active = videos[activeIndex];
                var incoming = videos[1 - activeIndex];
                var resumeAt = active.currentTime || 0;

                incoming.dataset.variant = variant;
                incoming.src = base + 'hero-home-' + variant + '.mp4';

                // Both layers cross-dissolve on the same clock, starting in
                // the same tick, so the fade genuinely overlaps instead of the
                // incoming layer waiting for the seek to land before it even
                // starts appearing. The seek itself runs in parallel — the
                // resumed frame lands mid-fade rather than gating its start.
                active.style.opacity = '0';
                active.pause();
                incoming.style.opacity = '1';

                if (resumeAt > 0) {
                  seekTo(incoming, resumeAt);
                } else {
                  incoming.play();
                }

                activeIndex = 1 - activeIndex;
              }

              var first = videos[0];
              currentVariant = mql.matches ? 'desktop' : 'mobile';
              first.dataset.variant = currentVariant;
              first.addEventListener('playing', function onFirstPlaying() {
                first.removeEventListener('playing', onFirstPlaying);
                first.removeAttribute('poster');
              });
              first.addEventListener('loadedmetadata', function setInitialHeight() {
                first.removeEventListener('loadedmetadata', setInitialHeight);
                setHeightFor(currentVariant, first.videoHeight / first.videoWidth);
              });
              first.poster = base + 'hero-home-' + currentVariant + '.webp';
              first.src = base + 'hero-home-' + currentVariant + '.mp4';
              first.play();
              first.style.opacity = '1';

              mql.addEventListener('change', applySource);
            })();
          </script>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <a name="main-content"></a>