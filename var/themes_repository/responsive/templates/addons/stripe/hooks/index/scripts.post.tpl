{script src="js/addons/stripe/checkout.js"}

<script>
    (function (_) {
        _.deferred_scripts.push({
            src: 'js/addons/stripe/views/instant_payment.js',
        });

        _.tr({
                'stripe.online_payment': '{__("stripe.online_payment")|escape:javascript}'
            });
    })(Tygh);
</script>
