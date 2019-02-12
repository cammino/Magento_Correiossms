<?php
class Cammino_Correiossms_IndexController extends Mage_Core_Controller_Front_Action {

	public function indexAction() {
        try {
            // Instacia helper e model do módulo
            $helper = Mage::helper("correiossms");
            $model = Mage::getModel("correiossms/correios");
            
            // Verifica se o módulo está ativo
            if($helper->isModuleActive()) {

                // Pega todos os pedidos a partir da data informada na config
                foreach($model->getOrders() as $order) {

                    // Pega a flag no banco para ver se o pedido já foi cadastrado
                    $correiosms = $order->getCorreiossms();

                    // Pega o status do pedido para saber se já foi criada uma entrega
                    $status = $order->getStatus();

                    /* Se o pedido estiver com o status completo ('entregue') 
                    E não tiver uma flag de que o pedido já foi sincronizado no banco */
                    if(empty($correiosms) && $status == "complete") {

                        // Pega o código de rastreio do pedido
                        $tracking = $model->getTrackingCode($order);

                        // Pega o celular do cliente
                        $cellphone = $model->getCustomerCellphone($order->getCustomerId());
                        
                        // Se existir um código de rastreio para o pedido e o cliente tiver um celular valido
                        if(!empty($tracking) && $cellphone != false) { 

                            // Registra o celular do cliente no correio
                            $status = $model->registerInCorreios($tracking, $cellphone);

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

}