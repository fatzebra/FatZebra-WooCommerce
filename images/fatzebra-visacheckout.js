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

    if (fzvisa.checkout_captured == 1) {
        return false; // Visa Checkout has already been completed on the cart page.
    } else {

        V.init({
            apikey: fzvisa.api_key,
            settings: {
                dataLevel: "SUMMARY",
                displayName: fzvisa.website_name,
                logoUrl: fzvisa.logo_url,
                shipping: {
                    collectShipping: fzvisa.shipping == 1
                },
                review: {
                    buttonAction: fzvisa.button_text,
                    message: fzvisa.review_message
                }
            },
            paymentRequest: {
                currencyCode: fzvisa.currency,
                total: fzvisa.order_total
            }
        });

        V.on("payment.success", function(payment) {
            jQuery('#callid').val(payment.callid);

            if (fzvisa.inline == 1) {
                // 'Post' form to wc-api call for processing.. prefill the form and then show the checkout page
                jQuery(".checkout-button").click(); 
                jQuery(".checkout-button").attr("disabled", true).val("Please wait...");
            }

            jQuery('#encKey').val(payment.encKey);
            jQuery('#encPaymentData').val(payment.encPaymentData);
            

            jQuery('#place_order').show();
            jQuery('#custom-visa-checkout-outer-container').remove();

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
}

jQuery( '#order_review' ).on( 'click', '.payment_methods input.input-radio', function() {
    if ( jQuery( '.payment_methods input.input-radio' ).length > 1 ) {
        if (fzvisa.checkout_captured == 1) {
            return;
        }
        var id = jQuery(this).val();
        if (id == 'fatzebra_visacheckout') {
            if (jQuery('#custom-visa-checkout-outer-container').length !== 0) return;
            jQuery('#place_order').hide();
            jQuery('#place_order').after(fzvisa.visa_checkout_button);

            setTimeout(initVisaCheckout, 500);
        } else {
            // Remove the visa button and show the place order button again
            jQuery('#custom-visa-checkout-outer-container').remove();
            jQuery('#place_order').show();
        }
    }
});

if (fzvisa.inline == 1) {
    // Render the 'Checkout' button on the cart page
    jQuery(".checkout-button").after("<br />").after(fzvisa.visa_checkout_button);
    jQuery(".checkout-button").append("<input type='hidden' name='callid' id='callid' />");
    setTimeout(initVisaCheckout, 500);
}