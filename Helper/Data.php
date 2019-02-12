<?php 
class Cammino_Correiossms_Helper_Data extends Mage_Core_Helper_Abstract {

    public function isModuleActive() {
        return (bool) Mage::getStoreConfig('shipping/correiossms/enable');
    }

    public function getSyncFromDate() {
        return Mage::getStoreConfig('shipping/correiossms/sync_from');
    }
    
    public function formatDateForEnglish($date) {
        $e = explode("/", $date);
        return $e[2]."/".$e[1]."/".$e[0];
    }

    public function cleanPhone($number) {
        $number = str_replace("(","", $number);
        $number = str_replace(")","", $number);
        $number = str_replace("-","", $number);
        $number = str_replace("_","", $number);
        $number = str_replace(" ","", $number);
        return $number;
    }

    public function isValidCellphone($number) {
        if(strlen($number) > 9) {
            $firstNumber = substr($number, 2, 1);
            if($firstNumber == "9" || $firstNumber == "8" || $firstNumber == "7") {
                return true;
            }
        }
        return false;
    }

    public function formatCellphoneToCorreios($cellphone) {
        $cellphone = substr_replace($cellphone, "(", 0, 0);
        $cellphone = substr_replace($cellphone, ")", 3, 0);
        $cellphone = substr_replace($cellphone, " ", 4, 0);
        $cellphone = substr_replace($cellphone, "-", -4, 0);
        return $cellphone;
    }

    public function hasStringInResponse($response, $string) {
        if (strpos($response, $string) !== false) {
            return true;
        } else {
            return false;
        }
    }
}