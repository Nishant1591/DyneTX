/****************************************
javascripts to ignore errors using jquery
***************************************/
jQuery(document).ready(function ($) {
  // on thickbox links return to opener link
  var wp_ada_clickedURL;
  $("body").on("click", ".thickbox", function (event) {
    wp_ada_clickedURL = this;

    setTimeout(function () {
      $("#TB_window").attr("role", "Alert");
      var tbwindow = document.getElementById("TB_window");
      wp_ada_compliance_basic_trapFocus(tbwindow);
    }, 1000);
  });
  jQuery("body").on("thickbox:removed", function () {
    if (typeof wp_ada_clickedURL !== "undefined") {
      wp_ada_clickedURL.focus();
    }
  });

  // trap focus
  function wp_ada_compliance_basic_trapFocus(element) {
    if (element == null) return;

    var focusableEls = element.querySelectorAll(
        'iframe, input[type="submit"]:not([disabled]), input[type="file"]:not([disabled]), input[type="button"]:not([disabled]), a[href]:not([disabled]), button:not([disabled]), textarea:not([disabled]), input[type="text"]:not([disabled]), input[type="radio"]:not([disabled]), input[type="checkbox"]:not([disabled]), select:not([disabled])'
      ),
      firstFocusableEl = focusableEls[0];
    lastFocusableEl = focusableEls[focusableEls.length - 1];
    KEYCODE_TAB = 9;

    element.addEventListener("keydown", function (e) {
      var isTabPressed = e.key === "Tab" || e.keyCode === KEYCODE_TAB;

      if (!isTabPressed) {
        return;
      }

      if (e.shiftKey) {
        /* shift + tab */ if (document.activeElement === firstFocusableEl) {
          lastFocusableEl.focus();
          e.preventDefault();
        }
      } /* tab */ else {
        if (document.activeElement === lastFocusableEl) {
          firstFocusableEl.focus();
          e.preventDefault();
        }
      }
    });
  }

  $("#ada_compliance_options :input").change(function () {
    $("#adasettsingsave").css("border", "4px solid red");
  });

  // close error window when opening report on editor screen
  $(".adareportlink").click(function () {
    $(".adaError").hide();
  });

  // open / close summary
  $(document).on("click", ".summary-dismiss", function () {
    if ($(".wp_ada_summary").is(":visible")) {
      document.cookie = "hide-wp-ada-summary=1";
      $(".wp_ada_summary").hide();
      $(".summary-dismiss").html(wpadacompliancebasicVariables["showsummary"]);
    } else {
      document.cookie =
        "hide-wp-ada-summary=; expires=Thu, 18 Dec 2013 12:00:00 UTC";
      $(".wp_ada_summary").show();
      $(".summary-dismiss").html(wpadacompliancebasicVariables["hidesummary"]);
    }
  });

  // refresh page content on foucus return
  var blurred = false;
  window.onblur = function () {
    blurred = true;
  };
  window.onfocus = function () {
    if (
      blurred &&
      window.location.search.match("compliancereportbasic.php") != null &&
      window.location.search.match("iframe=1") == null &&
      scaninprogress === 0
    ) {
      wp_ada_compliance_basic_refresh_results("0");
    }
  };

  wp_ada_compliance_basic_setbutton_eventListener();

  // toggle help links
  $("body").on("click", ".adaHelpLinkToggle", function (event) {
    event.preventDefault();
    $(this)
      .parent()
      .find(".adaHelpText")
      .toggle("slow", function () {
        // Animation complete.
      });
  });

  // toggle ignore options
  $("body").on("click", ".wp-ada-ignore-options-click", function () {
    $(this).next("span").show().css("display", "inline-block");
    return false;
  });

  // hide notices
  $(document).on("click", ".wpadahidenotices", function (e) {
    $(".wp_ada_compliance_notice_container").hide();
  });

  // update start scan notice
  $("body").on("click", ".startscan", function () {
    $(".wp_ada_compliance_notice_container").show(0);
    $(".wp_ada_compliance_notices").html(
      wpadacompliancebasicVariables.inprogress
    );
  });

  // allow key commands on click events
  function wp_ada_compliance_basic_setbutton_eventListener() {
    if (document.querySelectorAll('[role="button"]') !== undefined) {
      document.querySelectorAll('[role="button"]').forEach(function (button) {
        button.addEventListener("keydown", function (evt) {
          if (evt.keyCode == 13 || evt.keyCode == 32) {
            button.click();
          }
        });
      });
    }
  }

  // hide ignored errors
  $("body").on(
    "click",
    ".wp_ada_compliance_basic_ignoreerror",
    function (event) {
      $(".wp_ada_compliance_notices").html(
        wpadacompliancebasicVariables.working
      );
      $(".wp_ada_compliance_notice_container").show(0);

      var clickedid = $(this).attr("id");
      var url = window.location.search;
      var iframe = window.location.search.match("iframe=1");
      var seperator = "&";
      var resturl = wpadacompliancebasicVariables["resturl"];

      if (resturl.search("/wp-json/") > 0) seperator = "?";
      event.preventDefault();
      $.ajax({
        url:
          wpadacompliancebasicVariables["resturl"] +
          "wp_ada_compliance_basic/v1/ignore/" +
          seperator +
          "_wpnonce=" +
          wpadacompliancebasicVariables["nonce"] +
          "&wpadaignore=" +
          clickedid,
        async: true,
        error: function (e) {
          return true;
        },
        success: function (data) {
          if (iframe == null) {
            wp_ada_compliance_basic_refresh_results("ignore");
          } else {
            if ($("#" + clickedid).hasClass("removeignore")) {
              $("#" + clickedid).attr({
                title: wpadacompliancebasicVariables.ignoreerrortitle,
              });
              $("#" + clickedid).html(
                '<i class="fas fa-eye-slash"></i>' +
                  wpadacompliancebasicVariables.ignoreerrorthis
              );
              $("#" + clickedid).removeClass("removeignore");
              $("#" + clickedid).addClass("addignore");
              var newID = "wpadaignore_" + data + "_0";
              $("#" + clickedid).attr("id", newID);
              $(".ignore" + data).hide();
              $(".wp_ada_compliance_notices").html(
                wpadacompliancebasicVariables.unignoreerror
              );
            } else if ($("#" + clickedid).hasClass("addignore")) {
              $("#" + clickedid).attr({
                title: wpadacompliancebasicVariables.unignoreerrortitle,
              });
              $("#" + clickedid).html(
                '<i class="fas fa-times-circle"></i>' +
                  wpadacompliancebasicVariables.ignoreerrorthis
              );
              $("#" + clickedid).removeClass("addignore");
              $("#" + clickedid).addClass("removeignore");
              var newID = "wpadaignore_" + data + "_1";
              $("#" + clickedid).attr("id", newID);
              $(".ignore" + data).show();
              $(".wp_ada_compliance_notices").html(
                wpadacompliancebasicVariables.ignoreerror
              );
            }
          }
        },
      });
    }
  );

  // ignore rule
  $("body").on(
    "click",
    ".wp_ada_compliance_basic_ignorerule",
    function (event) {
      if (!confirm(wpadacompliancebasicVariables["ignoreruleconfirm"])) {
        event.preventDefault();
        return;
      }

      $(".wp_ada_compliance_notices").html(
        wpadacompliancebasicVariables.working
      );
      $(".wp_ada_compliance_notice_container").show(0);
      var clickedid = $(this).attr("id");
      var seperator = "&";
      var iframe = window.location.search.match("iframe=1");
      var resturl = wpadacompliancebasicVariables["resturl"];

      if (resturl.search("/wp-json/") > 0) seperator = "?";
      event.preventDefault();
      $.ajax({
        url:
          wpadacompliancebasicVariables["resturl"] +
          "wp_ada_compliance_basic/v1/ignore/" +
          seperator +
          "_wpnonce=" +
          wpadacompliancebasicVariables["nonce"] +
          "&wpadaignorerule=" +
          clickedid,
        async: true,
        error: function (e) {
          return true;
        },
        success: function (data) {
          $(".ruleid_" + data).hide(0);
          $(".wp_ada_compliance_notices").html(
            wpadacompliancebasicVariables["ignorerule"]
          );
        },
      });
      if (iframe == null) {
        wp_ada_compliance_basic_refresh_results("ignore");
      }
    }
  );

  // refresh results
  function wp_ada_compliance_basic_refresh_results(status) {
    $(".wp_ada_compliance_notices").html(wpadacompliancebasicVariables.working);
    var url = window.location.search;
    const urlParams = new URLSearchParams(window.location.search);
    const myParam = urlParams.get("myParam");
    var seperator = "&";
    var resturl = wpadacompliancebasicVariables["resturl"];
    var displaysummary = 0;
    if (document.cookie.match(/^(.*;)?\s*hide-wp-ada-summary\s*=\s*[^;]+(.*)?$/)){
      displaysummary = 1;
    }  

    if (resturl.search("/wp-json/") > 0) seperator = "?";
    var data = $.ajax({
      type: "GET",
      url:
        wpadacompliancebasicVariables["resturl"] +
        "wp_ada_compliance_basic/v1/refreshreport/" +
        seperator +
        "_wpnonce=" +
        wpadacompliancebasicVariables["nonce"] +
        "&view=" +
        wp_ada_compliance_basic_getParameterByName("view") +
        "&sort=" +
        wp_ada_compliance_basic_getParameterByName("sort") +
        "&type=" +
        wp_ada_compliance_basic_getParameterByName("type") +
        "&errorw=" +
        wp_ada_compliance_basic_getParameterByName("errorw") +
        "&error=" +
        wp_ada_compliance_basic_getParameterByName("errorw") +
        "&errorid=" +
        wp_ada_compliance_basic_getParameterByName("postid") +
        "&excludedups=" +
        wp_ada_compliance_basic_getParameterByName("excludedups") +
        "&excludethemes=" +
        wp_ada_compliance_basic_getParameterByName("excludethemes") +
        "&searchtitle=" +
        wp_ada_compliance_basic_getParameterByName("searchtitle") +
        "&cpage=" +
        wp_ada_compliance_basic_getParameterByName("cpage") +
        "&displaysummary=" +
        displaysummary +
        "&status=" +
        status,
      dataType: "html",
      //async: false,
      success: function (data) {
        data = data.replace("null", "");

        $(".wp_ada_compliance_basic_report").html(data);
        if (status === "ignore") {
          $(".wp_ada_compliance_notices").html(
            wpadacompliancebasicVariables.ignorestatus
          );
        } else if (status === "scanstatus1") {
          $(".wp_ada_compliance_notices").html(
            wpadacompliancebasicVariables.scanstatus1
          );
        } else if (status === "scanstatus2") {
          $(".wp_ada_compliance_notices").html(
            wpadacompliancebasicVariables.scanstatus2
          );
        } else if (status === "complete") {
          $(".wp_ada_compliance_notices").html(
            wpadacompliancebasicVariables.scancompleteoverage
          );
        } else if (status === "recheck") {
          $(".wp_ada_compliance_notices").html(
            wpadacompliancebasicVariables.recheck
          );
        } else {
          $(".wp_ada_compliance_notices").html(
            wpadacompliancebasicVariables.refresh
          );
        }
      },
    });
  }

  //recheck an item
  $("body").on("click", ".wp_ada_compliance_basic_recheck", function (event) {
    $(".wp_ada_compliance_notices").html(wpadacompliancebasicVariables.working);
    $(".wp_ada_compliance_notice_container").show(0);
    var clickedid = $(this).attr("id");
    var url = window.location.search;
    var seperator = "&";
    var resturl = wpadacompliancebasicVariables["resturl"];

    if (resturl.search("/wp-json/") > 0) seperator = "?";
    event.preventDefault();
    $.ajax({
      url:
        wpadacompliancebasicVariables["resturl"] +
        "wp_ada_compliance_basic/v1/rescan/" +
        seperator +
        "_wpnonce=" +
        wpadacompliancebasicVariables["nonce"] +
        "&wpadarescan=" +
        clickedid,
      async: true,
      dataType: "html",
      error: function (e) {
        console.log("failed");
        return true;
      },
      success: function (data) {
        wp_ada_compliance_basic_refresh_results("recheck");
      },
    });
  });

  // get url params
  function wp_ada_compliance_basic_getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
      results = regex.exec(url);
    if (name == "cpage" && !results) return 1;
    if (!results) return "";
    if (!results[2]) return "";
    return decodeURIComponent(results[2].replace(/\+/g, " "));
  }

  /* settings tabs */
  $(function () {
    if (document.getElementById("abb-tabs") !== null) {
      $("#abb-tabs").tabs();

      if (
        wp_ada_compliance_basic_getCookie("wpadasettingslastTab") != "undefined"
      ) {
        $(
          '.ui-tabs-nav a[href="' +
            wp_ada_compliance_basic_getCookie("wpadasettingslastTab") +
            '"]'
        ).trigger("click");
      }
      $(".ui-tabs-anchor").click(function () {
        var addressValue = $(this).attr("href");
        document.cookie = "wpadasettingslastTab=" + addressValue;
      });
    }
  });

  // scroll button
  var adascrollbutton = $("#adascrollbutton");

  $(window).scroll(function () {
    if ($(window).scrollTop() > 300) {
      adascrollbutton.addClass("show");
    } else {
      adascrollbutton.removeClass("show");
    }
  });

  $("body").on("click", "#adascrollbutton", function (e) {
    e.preventDefault();
    $("html, body").animate({ scrollTop: 0 }, "300");
  });

  // get cookie
  function wp_ada_compliance_basic_getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(";");
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) == " ") {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return "";
  }

  // start scan
  var adastarted = 1;
  var scanmore = 0;
  var scaninprogress = 0;
  var adacounter = 1;

  $(document).on("click", ".startscan", function (e) {
    e.preventDefault();

    //$(".wp_ada_compliance_notice_container").show();
    //$('.wp_ada_compliance_notices').show();

    scaninprogress = 1;

    if (adastarted === 1) {
      $(".wp_ada_compliance_notices").html(
        wpadacompliancebasicVariables.scanning
      );
    }

    $(".wp-ada-compliance-buttns").hide();
    $(".hideduringscan").hide();

    wp_ada_compliance_basic_scanwebsite();
  });

  // scan website
  function wp_ada_compliance_basic_scanwebsite() {
    var seperator = "&";
    var resturl = wpadacompliancebasicVariables["resturl"];
    if (resturl.search("/wp-json/") > 0) seperator = "?";

    var data = $.ajax({
      type: "GET",
      url:
        wpadacompliancebasicVariables["resturl"] +
        "wp_ada_compliance_basic/v1/startscan/" +
        seperator +
        "_wpnonce=" +
        wpadacompliancebasicVariables["nonce"] +
        "&scanmore=" +
        scanmore +
        "&adacounter=" +
        adacounter,
      async: true,
      dataType: "html",
      error: function (xmlReq, txtStatus, errThrown) {
        adastarted = 1;

        wp_ada_compliance_basic_scanwebsite();
      },
      success: function (data) {
        if (data.search("complete") !== -1) {
          $(".wp-ada-compliance-buttns").show();
          $(".hideduringscan").show();
          scaninprogress = 0;
          wp_ada_compliance_basic_refresh_results("complete");
        }
        // run it again.
        if (data.search("complete") === -1) {
          mycrawl = setTimeout(function () {
            adastarted = 1;
            console.log(adacounter);
            adacounter++;
            if (adacounter < 4) {
              if (adacounter === 2) {
                wp_ada_compliance_basic_refresh_results("scanstatus1");
                $(".wp_ada_compliance_notices").html(
                  wpadacompliancebasicVariables.scanstatus1
                );
              }
              if (adacounter === 3) {
                wp_ada_compliance_basic_refresh_results("scanstatus2");
                $(".wp_ada_compliance_notices").html(
                  wpadacompliancebasicVariables.scanstatus2
                );
              }
              wp_ada_compliance_basic_scanwebsite();
            } else {
              scaninprogress = 0;
              adacounter = 0;
              status = "complete";
              wp_ada_compliance_basic_refresh_results("complete");
            }
          }, 1000);
        }
      },
    });
  }

  // remove query params
  if (window.location.href.indexOf("wp-ada-compliance-basic-admin") >= 0) {
    removeParam("reset_settings");
    removeParam("purge");
  }

  function removeParam(paramName) {
    let searchParams = new URLSearchParams(window.location.search);
    searchParams.delete(paramName);
    if (history.replaceState) {
      let searchString =
        searchParams.toString().length > 0 ? "?" + searchParams.toString() : "";
      let newUrl =
        window.location.protocol +
        "//" +
        window.location.host +
        window.location.pathname +
        searchString +
        window.location.hash;
      history.replaceState(null, "", newUrl);
    }
  }

// correct spacebar not triggering button clicks keypress
$("body").on("keypress", ".wp-ada-compliance-modal-open", function (event) {
  
  var code = event.key || event.code; //Get keycode.
  if (code != 'Tab' && code != ' ' && code != 'Enter' && code != 'ArrowLeft' && code != 'ArrowUp' && code != 'ArrowRight' && code != 'ArrowDown') //Check each key.
      return; //If it isn't any of those, don't worry about it.

  if ((code == ' ' || code == 'Enter' || code == 'ArrowLeft' || code == 'ArrowRight') && $(':focus').is('input')) //If a space/enter or left/right arrow is pressed in the input, then allow it to happen as normal.
      return;

  event.preventDefault()

jQuery(this).trigger('click');

});


/* show modal */
var openerid;
jQuery("body").on("click", ".wp-ada-compliance-modal-open", function (e) {
var id = '#'+jQuery(this).attr('data-id');
openerid = '#'+jQuery(this).attr('id');

e.preventDefault();

//   trap focus
var dialogid = document.getElementById(jQuery(this).attr('data-id'));
wp_ada_compliance_basic_trapFocus(dialogid);

  if (jQuery(id).css('display') == 'none') {
      jQuery(id).show();
  
      jQuery(id).find('.wp-ada-compliance-modal-close').focus();

 } else {
jQuery(id).hide();
jQuery(id).focus();
  }

return false;
});


// close all open modals
jQuery("body").on("click", "a.wp-ada-compliance-modal-close", function (e) {

  e.preventDefault();
    jQuery('.wp-ada-compliance-modal-wrapper').hide();
  var opener = jQuery(this).parent().parent().attr('id');

  if(openerid !== '#undefined'){
    jQuery(openerid).focus();
  }else {
  jQuery('a[data-id='+opener+']').focus();
  }

});
// close all open modals on escape
jQuery(document).on("keydown", function (e) {

  if (e.key === "Escape") {
  
    if(jQuery('.modal_wrapper').is(':visible')){
    e.preventDefault();
    }

  jQuery( ".wp-ada-compliance-modal-wrapper" ).each(function( index ) {
    if(jQuery(this).is(':visible')){

    jQuery(this).hide();
  var opener = jQuery(this).attr('id');

  if(openerid !== '#undefined'){
    jQuery(openerid).focus();
  }else {
  jQuery('a[data-id='+opener+']').focus();
  }
    }
    });
  }

});

});
