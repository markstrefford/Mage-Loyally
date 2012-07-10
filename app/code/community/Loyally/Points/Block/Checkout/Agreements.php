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
 
class Loyally_LoyallyPoints_Block_Checkout_Agreements extends Mage_Checkout_Block_Agreements
{
    /**
     * Override block template
     *
     * @return string
     */
    protected function _toHtml()
    {
        $this->setTemplate('loyallypoints/checkout/agreements.phtml');
        return parent::_toHtml();
    }
}