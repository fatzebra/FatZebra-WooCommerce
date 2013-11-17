jQuery(function() {
  var overlay = jQuery("<div id='lean_overlay'></div>");
            
  jQuery("body").append(overlay);
  jQuery("a[rel*=leanModal]").live('click', function(e) {
    var modal_id = jQuery(this).attr("href");
    var close_modal = function(modal_id){
      jQuery("#lean_overlay").fadeOut(200);
      jQuery(modal_id).css({ 'display' : 'none' });
    }

    jQuery("#lean_overlay").click(function(e) { 
      close_modal(modal_id);
      e.preventDefault();
    });
    
    jQuery(".modal_close").click(function(e) { 
      close_modal(modal_id);
      e.preventDefault();
    });

    var modal_height = jQuery(modal_id).outerHeight();
    var modal_width = jQuery(modal_id).outerWidth();

    jQuery('#lean_overlay').css({ 'display' : 'block', opacity : 0 });

    jQuery('#lean_overlay').fadeTo(200, 0.5);

    jQuery(modal_id).css({ 
    
      'display' : 'block',
      'position' : 'fixed',
      'opacity' : 0,
      'z-index': 11000,
      'left' : 50 + '%',
      'margin-left' : -(modal_width/2) + "px",
      'top' : 100 + "px"
    
    });

    jQuery(modal_id).fadeTo(200,1);
    e.preventDefault();
  });
  

  jQuery("#cardnumber").live("keyup", function() {
    var value = jQuery(this).val();
    if(value.length === 0) return;

    var card_id;
    if(value.match(/^4/)) card_id = "card_visa";
    if(value.match(/^5/)) card_id = "card_mastercard";
    if(value.match(/^(34|37)/)) card_id = "card_american_express";
    if(value.match(/^(36)/)) card_id = "card_diners_club";
    if(value.match(/^(35)/)) card_id = "card_jcb";
    if(value.match(/^(65)/)) card_id = "card_discover";

    jQuery("img.card_logo").each(function() {
      if(jQuery(this).attr("id") != card_id) {
        jQuery(this).css({opacity: 0.5});
      } else {
        jQuery(this).css({opacity: 1.0});
      }
    });

    jQuery("#card_expiry_year").live("blur", function() {
      if(parseInt(jQuery(this).val()) < 100) {
        jQuery(this).val(2000 + parseInt(jQuery(this).val()));
      }
    });
  });
});