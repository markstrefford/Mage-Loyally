<?php
/**
 * This source file is (c) 2012 Loyally.me
 * 
 * @category    Loyally
 * @package     Loyally_Points
 * @copyright   Copyright (c) 2012 Loyally.me
 * @license     http://loyally.me
 *
 * 
 * *** THIS FILE IS FOR ENROLING A USER IN A SCHEME IF THEY ARE NOT ALREADY ENROLED ****
 * 
 * 
 */
 

class Loyally_LoyallyPoints_Model_Observer extends Varien_Object
{
    /**
	 * 
	 *
     * Handle loyally signup agreement from checkout form
     *
     * @param $observer
     */
    public function saveJoinLoyally($observer)
    {

        Mage::log("*** Starting observer code for saveJoinLoyally() **** ");
        $join = Mage::app()->getRequest()->getPost('joinloyally');

       	if(!empty($join)) {
         	Mage::Log("User entered email address so let's enrol them on the scheme!");
          	// We're assuming the user is logged in as otherwise they wouldn't be prompted for the email box (called comment here though!!)
           	$session = Mage::getSingleton('customer/session',array('name'=>'frontend'));
          	$customer = $session->getCustomer();
          	$email = $customer->getEmail();
           	$firstname = $customer->getFirstname();
         	$lastname = $customer->getLastname();
          	Mage::Log("About to register Customer : ".$email." ".$firstname." ".$lastname);

           	// Will replace $scheme_id = 1 below with a call to get the unique key for this account,
          	// but need to tweak to API to base it on a unique key first!!
         	$key = Mage::getStoreConfig('points/settings/key');
         	// $scheme_id = 111;

         	// Create the URL and log it
        	$host = "http://loyally.local:9000";
         	$url_path = "/api/memberships";
         	$url = $host.$url_path;
          	Mage::Log("URL ".$url);

   			// Create the body for the json message
      		$json_message = '{"key" : '.$key.', "email" : "'.$email.'"}';
       		Mage::Log("Json message: ".$json_message);

      		// Set headers for json message
     		$headers = array(
         		'Accept: application/json',
         		'Content-Type: application/json',
      		);

        	// Now initialise CURL and set parameters
            $ch = curl_init();
         	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
          	curl_setopt($ch, CURLOPT_URL, $url);
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
          	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
           	curl_setopt($ch, CURLOPT_POST, true);
       		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_message);

          	// Now execute the CURL command
         	$output = curl_exec($ch);
   			Mage::Log("Curl output : ".$output);

     		// *** Now need to get the membership ID out of $output
    		$json_response = json_decode($output);
        	$membership_id = $json_response->membership_id;
       		// Now set the customer's Loyally_membership_id attribute!!

           	Mage::Log("Let's check we have a membership ID or is it null");
     		if(!is_null($membership_id)) {
  			Mage::Log("Returned membership id : ".$membership_id);
    	    $customer->setLoyally_membership_id($membership_id);
     		} else {
           	       Mage:Log("No membership ID returned!!");
      		}

			// Log call info from curl
			Mage::Log(curl_getinfo($ch));

         	// Stop curl
        	curl_close($ch);

			Mage::log("*** End of observer code for saveJoinLoyally() **** ");

          }
         
    }

	/*
	 * Handle different checkout flows to ensure that points get logged to loyally.me
	 * 
	 */
	 
	 public function coreBlockAbstractToHtmlAfter($observer)
  	{
    	$transport = $observer->getEvent()->getTransport();
    	$block = $observer->getEvent()->getBlock();	
	    return $this;
	}

  	public function checkoutOnepageControllerSuccessAction($observer)
  	{
  	  	$order_id = Mage::getSingleton('checkout/session')->getLastOrderId();
 	  	Mage::Log("order ".$order_id);
    	$this->postPointsToLoyally($order_id);
    	return $this;
  	}

	// NOTE - This has not been tested properly!!
  	public function checkoutMultishippingControllerSuccessAction($observer)
  	{
    	$orders = $observer->getEvent()->getOrderIds();
    	Mage::Log("Multishipping: ".count($orders)." orders in total");
    	foreach($orders as $oid => $order_id)
    	{
    	  Mage::Log("order ".$order_id);
    	  $this->postPointsToLoyally($order_id);
    	}
    	return $this;
  	}
  	
  	
	
	
	
	
	// So this function does all of the work for posting to Loyally.me
  	public function postPointsToLoyally($order_id)
  	{
  		Mage::Log("***** Start of posting points to loyally.me function *****");
	
  		// Select the default app
    	$app = Mage::app('default');

    	// Get the customers session
    	$session = Mage::getSingleton('customer/session',array('name'=>'frontend'));
    	if($session->isLoggedIn())
    	{
    	  	Mage::Log("User logged in");
    	  	// Get the customer info
    	  	$customer = $session->getCustomer();
    	  	Mage::Log("Customer Info: ".$customer->getEmail()." ".$customer->getFirstname()." ".$customer->getLastname());
		  	// 
		  	// Check for the user's membership ID
		  	// 
		  	// **** NOTE THAT THIS HAS BEEN CREATED ELSEWHERE USING:
		  	// http://www.magentocommerce.com/magento-connect/custom-attributes-4340.html
		  	// 
		  	// In future revisions, let's create this attribute in the setup scripts
		  	//
		  	$membership_id = $customer->getLoyally_membership_id();
		  	Mage::Log("Membership ID ".$membership_id);
	
	 	  	// Now let's get the user session
	 	  	$core_session = Mage::getSingleton('core/session');
	 	  	$visitor_data = $core_session->getVisitorData();
	
	 	  	// Start with a 0 price total
	 	  	$price = 0;
	 	  	// Get the order model
	 	  	$order = Mage::getModel('sales/order');
	 	  	$order->load($order_id);
	 	  	// Log getting the order ID data
			Mage::Log($order->getData());
	    	// Get and log the subtotal
	    	Mage::Log("Subtotal: ".$order->getSubtotal());
	    	$points = $order->getSubtotal();
	    	
	
	    	// Create the URL and log it
		    $host = "http://loyally.local:9000";
	   	 	$url_path = "/api/memberships/accrue/";

    		$membership_id = $customer->getLoyally_membership_id();	    
    		$url = $host.$url_path.$membership_id;
	    	Mage::Log("URL ".$url);
	
			// Create the body for the json message
	    	$json_message = '{"points" : '.$points.'}';
			Mage::Log("Json message: ".$json_message);
	
			// Code derived from http://stackoverflow.com/questions/3958226/using-put-method-with-php-curl-library

			// Write the json message into a temporary file
			$putData = tmpfile();
			$putString = stripslashes($json_message);
			fwrite($putData, $putString);
			fseek($putData, 0);
		
			// Set headers for json message
			$headers = array(
	 		   'Accept: application/json',
	 		   'Content-Type: application/json',
			);
			
			// Now initialise CURL and set parameters
	  		$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_PUT, true);
	
			// Now execute the CURL command
			$output = curl_exec($ch);
			Mage::Log("Curl output : ".$output);
		    Mage::Log(curl_getinfo($ch));
			fclose($putData);
    		curl_close($ch);
			
			Mage::Log("***** End of posting points to loyally.me function *****");
			
		 } else {
		 	Mage::Log("User not logged in so doesn't accrue points!!");
			Mage::Log("***** End of posting points to loyally.me function *****");
			
		 }
  	}
	 
}

