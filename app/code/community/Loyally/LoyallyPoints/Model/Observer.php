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
    public function joinLoyally($observer)
    {

        Mage::log("*** Starting observer code for joinLoyally() **** ");
        $join = Mage::app()->getRequest()->getPost('loyallypoints');

       	if(!empty($join)) {
         	Mage::Log("User entered email address so let's enrol them on the scheme!");
          	// We're assuming the user is logged in as otherwise they wouldn't be prompted for the email box (called comment here though!!)
           	$session = Mage::getSingleton('customer/session',array('name'=>'frontend'));
          	$customer = $session->getCustomer();
          	$email = $customer->getEmail();
           	$firstname = $customer->getFirstname();
         	$lastname = $customer->getLastname();
          	Mage::Log("About to register Customer : ".$email." ".$firstname." ".$lastname);

          	// Get unique key for this online store
         	$key = Mage::getStoreConfig('loyallypoints/settings/key');

         	// Create the URL and log it
        	$host = "http://loyally.local:9000";
         	$url_path = "/api/v01/memberships";
         	$url = $host.$url_path;
          	Mage::Log("URL ".$url);

   			// Create the body for the json message
      		// $json_message = '{"key" : '.$key.', "email" : "'.$email.'"}';
   			$json_key = '"key" : "'.$key.'"';
			$json_email = '"email" : "'.$email.'"';
			$json_plugin_type = '"plugin_type" : "magento_1.6.0"';
			$json_plugin_version = '"plugin_version" : "0.0.2"';
			$json_message = '{'.$json_key.', '.$json_email.', '.$json_plugin_type.', '.$json_plugin_version.'}';
       		Mage::Log("Json message: ".$json_message);

      		// Set headers for json message
     		$headers = array(
         		'Accept: application/json',
         		'Content-Type: application/json',
      		);

			//$headers = array(
         	//	'application/json'
      		//);
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
    	    	$customer->setMembership_id($membership_id);
				// $product->getResource()->saveAttribute($product, "name_of_your_attribute"); // From http://stackoverflow.com/questions/2418698/magento-user-created-attribute-for-products-is-not-saved
				// $customer->saveAttribute($customer, "membership_id");
				$customer->save();
				Mage::Log("Membership ID saved is : ".$customer->getMembership_id());
     		} else {
           	       Mage:Log("No membership ID returned!!");
      		}

			// Log call info from curl
			Mage::Log(curl_getinfo($ch));

         	// Stop curl
        	curl_close($ch);

			Mage::log("*** End of observer code for saveJoinLoyally() **** ");

         } else {
            Mage::Log("User did not request to join scheme, so exiting...");
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
			// From http://stackoverflow.com/questions/2418698/magento-user-created-attribute-for-products-is-not-saved
			//$collection->addAttributeToSelect("my_update");
			$customer = $session->getCustomer();
			// $customer->getAttributes("membership_id");
    	  	Mage::Log("Customer Info: ".$customer->getEmail()." ".$customer->getFirstname()." ".$customer->getLastname());
		  	// 
		  	Mage::Log("Membership ID saved is : ".$customer->getMembership_id());
		  	
		  	// Check for the user's membership ID
		  	$membership_id = $customer->getMembership_id();
			Mage::Log("Membership ID stored : ".$membership_id);
			if (!is_null($membership_id)) {
				Mage::Log("User logged in and their membership ID is ".$membership_id);
			
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
	
	    		// Create the URL and log it (hardcoded for now, not using any config settings from Magento DB)
		    	$host = "http://loyally.local:9000";
	   	 		$url_path = "/api/v01/memberships/accrue/";

				Mage::Log("Membership ID saved is : ".$customer->getMembership_id());
				
    			$membership_id = $customer->getMembership_id();	    
    			$url = $host.$url_path.$membership_id;
	    		Mage::Log("URL ".$url);
		
				// Create the body for the json message
				$json_key = '"key" : '.Mage::getStoreConfig('loyallypoints/settings/key');
	    		$json_points = '"points" : '.$points;
				$json_plugin_type = '"plugin_type" : magento_1.6.0';
				$json_plugin_version = '"plugin_version" : "0.0.2"';
				$json_message = '{'.$json_key.', '.$json_points.', '.$json_plugin_type.', '.$json_plugin_version.'}';
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
				
				// **** Need to handle failed CURL command here...
				
			} else {
				Mage::Log("**** User logged in but not enrolled, so doesn't accrue points!!'");	
			}
		 } else {
		 	Mage::Log("User not logged in so doesn't accrue points!!");
			
		 }
		Mage::Log("***** End of posting points to loyally.me function *****");
  	}
	 
}

