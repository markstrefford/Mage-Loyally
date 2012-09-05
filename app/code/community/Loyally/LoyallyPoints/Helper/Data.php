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
 
// *** Need to work out what a helper abstract class is!!
class Loyally_LoyallyPoints_Helper_Data extends Mage_Core_Helper_Abstract
{
 	// Check whether the config setting for points is enabled
  	public function isLoyallyEnabled()
  	{
    	if(Mage::getStoreConfig('loyallypoints/settings/enabled') == 0)
    	  return FALSE;
    	else
    	  return TRUE;
  	}

	// Do we need to do anything else here to set loyally up?
}
