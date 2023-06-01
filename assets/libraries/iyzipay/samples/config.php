<?php
require_once(dirname(__DIR__).'/IyzipayBootstrap.php');

IyzipayBootstrap::init();
$url = 'https://sandbox-api.iyzipay.com';
if ($nrg['config']['iyzipay_mode'] == '0') {
	$url = 'https://api.iyzipay.com';
}

class IyzipayConfig
{
    public static function options()
    {
    	global $nrg,$url;

        $options = new \Iyzipay\Options();
        $options->setApiKey($nrg['config']['iyzipay_key']);
        $options->setSecretKey($nrg['config']['iyzipay_secret_key']);
        $options->setBaseUrl($url);

        return $options;
    }
}
$ConversationId = rand(11111111,99999999);
$request = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
$request->setLocale(\Iyzipay\Model\Locale::TR);
$request->setConversationId($ConversationId);
$request->setCurrency(\Iyzipay\Model\Currency::TL);
$request->setBasketId("B".rand(11111111,99999999));
$request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::PRODUCT);
$request->setEnabledInstallments(array(2, 3, 6, 9));


$buyer = new \Iyzipay\Model\Buyer();
$buyer->setId($nrg['config']['iyzipay_buyer_id']);
$buyer->setName($nrg['config']['iyzipay_buyer_name']);
$buyer->setSurname($nrg['config']['iyzipay_buyer_surname']);
$buyer->setGsmNumber($nrg['config']['iyzipay_buyer_gsm_number']);
$buyer->setEmail($nrg['config']['iyzipay_buyer_email']);
$buyer->setIdentityNumber($nrg['config']['iyzipay_identity_number']);
$buyer->setRegistrationAddress($nrg['config']['iyzipay_address']);
$buyer->setCity($nrg['config']['iyzipay_city']);
$buyer->setCountry($nrg['config']['iyzipay_country']);
$buyer->setZipCode($nrg['config']['iyzipay_zip']);
$request->setBuyer($buyer);


$shippingAddress = new \Iyzipay\Model\Address();
$shippingAddress->setContactName($nrg['config']['iyzipay_buyer_name'].' '.$nrg['config']['iyzipay_buyer_surname']);
$shippingAddress->setCity($nrg['config']['iyzipay_city']);
$shippingAddress->setCountry($nrg['config']['iyzipay_country']);
$shippingAddress->setAddress($nrg['config']['iyzipay_address']);
$shippingAddress->setZipCode($nrg['config']['iyzipay_zip']);
$request->setShippingAddress($shippingAddress);

$billingAddress = new \Iyzipay\Model\Address();
$billingAddress->setContactName($nrg['config']['iyzipay_buyer_name'].' '.$nrg['config']['iyzipay_buyer_surname']);
$billingAddress->setCity($nrg['config']['iyzipay_city']);
$billingAddress->setCountry($nrg['config']['iyzipay_country']);
$billingAddress->setAddress($nrg['config']['iyzipay_address']);
$billingAddress->setZipCode($nrg['config']['iyzipay_zip']);
$request->setBillingAddress($billingAddress);