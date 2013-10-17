<?php
require_once('/srv/lib/stripe-php/Stripe.php');

class stripe_configuration{

    public static $secret_key = "sk_test_nZvhxHClm4cR4fA9rv4Um4aU";
    public static $public_key = "pk_test_JyW7jbQudHJwV36CAQNiM63O";

    function stripe_configuration(){
        Stripe::setApiKey(stripe_configuration::$secret_key);
    }

}

Stripe::setApiKey(stripe_configuration::$secret_key);


?>