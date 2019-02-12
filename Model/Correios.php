<?php
class Cammino_Correiossms_Model_Correios {

    public function getOrders() {
        $helper = Mage::helper("correiossms");
        $date = $helper->getSyncFromDate();
        $date = $helper->formatDateForEnglish($date);
        $fromDate = date('Y-m-d H:i:s', strtotime($date));

        $ids = array();
        $orders = Mage::getModel("sales/order")
            ->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('created_at', array('from' => $fromDate));
        
        return $orders;
    }

    public function getTrackingCode($order) {
        $shipments = Mage::getResourceModel('sales/order_shipment_collection')
            ->setOrderFilter($order)
            ->load();
        
        $trackingCode = false;

        foreach ($shipments as $shipment) {
            foreach($shipment->getAllTracks() as $tracknum) {
                if(strlen($tracknum->getNumber()) > 5) {
                    $trackingCode = $tracknum->getNumber();
                }
            }
        }

        return $trackingCode;
    }

    public function getCustomerCellphone($customerId) {
        $helper = Mage::helper("correiossms");
        $customer = Mage::getModel('customer/customer')->load($customerId);

        $telephone = $customer->getPrimaryBillingAddress()->getTelephone();
        $telephone = $helper->cleanPhone($telephone);

        if($helper->isValidCellphone($telephone)) {
            return $telephone;
        } else {
            $fax = $customer->getPrimaryBillingAddress()->getFax();
            $fax = $helper->cleanPhone($fax);

            if($helper->isValidCellphone($telephone)) {
                return $fax;
            } else {
                return false;
            }
        }
    }

    public function registerInCorreios($tracking, $cellphone) {
        return true;
        try {
            $helper = Mage::helper("correiossms");
            $cellphone = Mage::helper("correiossms")->formatCellphoneToCorreios($cellphone);

            $post = [
                'celularum' => $cellphone,
                'celulardois' => '',
                'termo' => 'on',
                'etiqueta'   => $tracking,
                'objetos' => $tracking,
                'botao' => 'OK'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"https://www2.correios.com.br/sistemas/rastreamento/resultado.cfm");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close ($ch);

            if($helper->hasStringInResponse($response, "alert('Registro gravado com sucesso');")) {
                Mage::log("Cadastrou com sucesso o número " . $cellphone . " nos correios sms para o tracking " . $tracking, null, "correiossms.log");
                return true;
            } else if($helper->hasStringInResponse($response, "existe telefone para este objeto');")) {
                Mage::log("Já existe cadastro para o número " . $cellphone . " nos correios sms para o tracking " . $tracking, null, "correiossms.log");
                return false;
            } else {
                Mage::log("Tentou cadastrar para o número " . $cellphone . " nos correios sms para o tracking " . $tracking, null, "correiossms.log");
                return false;
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage());
            return false;
        }
    }
}