<?php
class Cammino_Correiossms_Model_Correios {

    public function sync() {
        Mage::log("==== Iniciou processo para sincronizar pedidos com os correios ====", null, "correiossms.log");

        try {
            // Instacia helper
            $helper = Mage::helper("correiossms");
            
            // Verifica se o módulo está ativo
            if($helper->isModuleActive()) {

                // Pega todos os pedidos a partir da data informada na config
                foreach($this->getOrders() as $order) {

                    // Pega a flag no banco para ver se o pedido já foi cadastrado
                    $orderCorreioFlag = $order->getCorreiossms();

                    // Pega o status do pedido para saber se já foi criada uma entrega
                    $status = $order->getStatus();

                    /* Se o pedido estiver com o status completo ('entregue') 
                    E não tiver uma flag de que o pedido já foi sincronizado no banco */
                    if(empty($orderCorreioFlag) && $status == "complete") {

                        // Pega o código de rastreio do pedido
                        $tracking = $this->getTrackingCode($order);

                        // Pega o celular do cliente
                        $cellphone = $this->getCustomerCellphone($order->getCustomerId());
                        
                        // Se existir um código de rastreio para o pedido e o cliente tiver um celular valido
                        if(!empty($tracking) && $cellphone != false) { 

                            // Registra o celular do cliente no correio
                            $status = $this->registerInCorreios($tracking, $cellphone, $order->getId());

                            /* Se conseguiu cadastrar o celular do cliente no correios,
                            atualiza a flag para o pedido no banco, indicando que esse pedido ja foi
                            cadastrado e não precisa ser sincronizado novamente */
                            if($status) {
                                $order->setCorreiossms("1")->save();
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Mage::log("Erro ao sincronizar os pedidos no módulo Correiossms");
            Mage::log($e->getMessage());
        }
    }

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

        if($customer && $customer->getPrimaryBillingAddress() && $customer->getPrimaryBillingAddress()->getTelephone()) {
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
        } else {
            return false;
        }
    }

    public function registerInCorreios($tracking, $cellphone, $orderId) {
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
                Mage::log("Cadastrou com sucesso o número " . $cellphone . " nos correios sms para o tracking " . $tracking . " , para o pedido #" . $orderId, null, "correiossms.log");
                return true;
            } else if($helper->hasStringInResponse($response, "existe telefone para este objeto');")) {
                Mage::log("Já existe cadastro para o número " . $cellphone . " nos correios sms para o tracking " . $tracking . " , para o pedido #" . $orderId, null, "correiossms.log");
                return true;
            } else {
                Mage::log("Tentou cadastrar para o número " . $cellphone . " nos correios sms para o tracking " . $tracking . " , para o pedido #" . $orderId, null, "correiossms.log");
                return false;
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage());
            return false;
        }
    }
}