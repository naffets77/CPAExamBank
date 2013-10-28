<?php
require_once('/srv/lib/stripe-php/Stripe.php');

class stripe_configuration{

    public static $secret_key = "sk_live_UIcybit30Z3LxXB7KpbRAmtS"; //sk_test_mkGsLqEW6SLnZa487HYfJVLf
    public static $public_key = "pk_live_PpVwQkuyOIGmuxmp9v4KZqtv"; //pk_test_czwzkTp2tactuLOEOqbMTRzG

    function stripe_configuration(){
        Stripe::setApiKey(stripe_configuration::$secret_key);
    }

}

Stripe::setApiKey(stripe_configuration::$secret_key);


?>