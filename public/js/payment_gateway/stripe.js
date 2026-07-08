(function ($) {
    "use strict";

    $(document).ready(function () {
        console.log('irfan');

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var $form = $(".require-validation");
        $('form').bind('submit', function(e) {
        // $("#stripePaymentForm").on("submit",function(e){
            e.preventDefault();
            const stripePublishableKey = $('#stripeKey').val();
            $('#payNowBtn').text('Processing...');
            if (!$form.data('cc-on-file')) {
                Stripe.setPublishableKey(stripePublishableKey);
                Stripe.createToken({
                    number: $('.card-number').val(),
                    cvc: $('.card-cvc').val(),
                    exp_month: $('.card-expiry-month').val(),
                    exp_year: $('.card-expiry-year').val()
                }, stripeResponseHandler);
            }
        });


        function stripeResponseHandler(status, response) {
            if (response.error) {
                    let responseData = {
                        responseJSON: {
                            errorMsg: response.error.message
                        }
                    };
                    let htmlContent = prepareMessage(responseData);
                    displayErrorMessage(htmlContent);
                    $('#payNowBtn').text('Pay Now');
            }
            else {
                // token contains id, last4, and card type
                var token = response['id'];
                $('input[name="stripeToken"]').val(token);
                $.ajax({
                    url: targetURL,
                    method: "POST",
                    data: new FormData(document.getElementById("stripePaymentForm")),
                    contentType: false,
                    cache: false,
                    processData: false,
                    dataType: "json",
                    error: function(response) {
                        console.log(response);
                        let htmlContent = prepareMessage(response);
                        displayErrorMessage(htmlContent);
                        $('#payNowBtn').text('Pay Now');
                    },
                    success: function (response) {
                        console.log(response);
                        $('#payNowBtn').text('Pay Now');
                        window.location.href = redirectURL;
                    }
                })
            }
        }


        $("#payCancelBtn").click(function(){
            if (confirm('Are you sure to cancel ?')) {
                window.history.back();
            }
        });

    });

})(jQuery);
