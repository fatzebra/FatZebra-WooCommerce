// Visa Checkout Handling
var visaCheckoutReady = false;
function onVmeReady() {
    visaCheckoutReady = true;
}

function initVisaCheckout() {
    if (!visaCheckoutReady) { // Wait until Visa Checkout is ready to init...
        setTimeout(initVisaCheckout, 500);
        return false;
    }

    V.init({
        apikey: fzvisa.api_key,
        settings: {
            dataLevel: "SUMMARY"
        },
        paymentRequest: {
            currencyCode: fzvisa.currency,
            total: fzvisa.order_total
        }
    });

    V.on("payment.success", function(payment) {
        jQuery('#encKey').val(payment.encKey);
        jQuery('#encPaymentData').val(payment.encPaymentData);
        jQuery('#callid').val(payment.callid);

        jQuery('#place_order').show();
        jQuery('.v-button').remove();

        $form = jQuery("form.checkout");
        $form.submit();
    });

    V.on("payment.cancel", function(payment) {
        // Cancelled, but we don't need to do anything, the user just needs to choose an alternative
    });
    V.on("payment.error", function(payment, error) {
        // Error - report to user, try again?
    });
}

jQuery( '#order_review' ).on( 'click', '.payment_methods input.input-radio', function() {
    if ( jQuery( '.payment_methods input.input-radio' ).length > 1 ) {
        var id = jQuery(this).val();
        if (id == 'fatzebra_visacheckout') {
            if (jQuery('.v-button').length !== 0) return;
            jQuery('#place_order').hide();
            jQuery('#place_order').after(fzvisa.visa_checkout_button);

            setTimeout(initVisaCheckout, 500);
        } else {
            // Remove the visa button and show the place order button again
            jQuery('.v-button').remove();
            jQuery('#place_order').show();
        }
    }
});