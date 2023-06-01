<?php
require_once('assets/libraries/stripe/vendor/autoload.php');
$stripe = array(
  "secret_key"      =>  $nrg['config']['stripe_secret'],
  "publishable_key" =>  $nrg['config']['stripe_id']
);

\Stripe\Stripe::setApiKey($stripe['secret_key']);
