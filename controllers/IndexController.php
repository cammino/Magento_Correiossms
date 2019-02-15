<?php
class Cammino_Correiossms_IndexController extends Mage_Core_Controller_Front_Action {
    
    public function indexAction() {
        Mage::getModel("correiossms/correios")->sync();
    }
}