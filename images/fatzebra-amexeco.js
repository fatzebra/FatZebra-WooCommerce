jQuery(function() {
  jQuery(window).on('message onmessage', function(e) {
    var payment = e.originalEvent.data;
    if (amexeco.inline == 1) {
        var checkout_button = jQuery('.checkout-button');
        var link = checkout_button.prop('href');

        jQuery('.wc-proceed-to-checkout').block({
  				message: 'Please Wait',
  				overlayCSS: {
  					background: '#fff',
  					opacity: 0.6
  				}
        });
        jQuery.post("/wc-api/WC_FatZebra_Amex_Eco", 'auth_code=' + payment.auth_code +  '&transaction_id=' + payment.transaction_id + '&card_type=' + payment.card_type + '&wallet_id=' + payment.wallet_id, function(data) {
          location.href = link;
          jQuery('.wc-proceed-to-checkout').unblock();
        });
    }
  });
});
