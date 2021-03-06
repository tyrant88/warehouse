<?php

class wh_giropay {

    public function init_giropay() {

        if (rex::isDebugMode()) {
            define('__GIROCHECKOUT_SDK_DEBUG__', true);
        }


        $cart = warehouse::get_cart();
        $user_data = warehouse::get_user_data();

        $merchantID = rex_config::get('warehouse', 'giropay_merchand_id');
        $projectID = rex_config::get('warehouse', 'giropay_project_id');
        $projectPassword = rex_config::get('warehouse', 'giropay_project_pw');

        $request = new GiroCheckout_SDK_Request('giropayTransaction');
        $request->setSecret($projectPassword);
        $request->addParam('merchantId', $merchantID)
                ->addParam('projectId', $projectID)
                ->addParam('merchantTxId', 1234567890)
                ->addParam('amount', (warehouse::get_cart_total() * 100))
                ->addParam('currency', rex_config::get('warehouse', 'currency'))
                ->addParam('purpose', 'Beispieltransaktion')
                ->addParam('bic', $user_data['giropay_bic'])
                ->addParam('info1Label', 'Ihre Kundennummer')
                ->addParam('info1Text', '0815')
                ->addParam('urlRedirect', trim(rex::getServer(), '/') . rex_getUrl(rex_config::get('warehouse', 'giropay_page_notify'), '', [], '&'))
                ->addParam('urlNotify', trim(rex::getServer(), '/') . rex_getUrl(rex_config::get('warehouse', 'giropay_page_notify'), '', [], '&'))
	    //the hash field is auto generated by the SDK
                ->submit();

        if ($request->requestHasSucceeded()) {
            $db_id = warehouse::save_order_to_db($request->getResponseParam('reference'));
            $request->getResponseParam('rc');
            $request->getResponseParam('msg');
            $request->getResponseParam('reference');
            $request->getResponseParam('redirect');
            $request->redirectCustomerToPaymentProvider();
        } else {
            $request->getResponseParam('rc');
            echo '<p>' . $request->getResponseParam('msg') . '</p>';
            echo '<p><a href="' . rex_getUrl(rex_config::get('warehouse', 'address_page')) . '">Weiter ...</a></p>';
//                dump($request->getResponseParam('msg'));
            $request->getResponseMessage($request->getResponseParam('rc'), 'DE');
        }
    }
    
    
    /**
     * Aufruf über Notify Seite
     * 
     Response ok:
    * gcReference = 4650b695-7c70-4415-987d-860b9347bdf8
    * gcMerchantTxId = 1234567890
    * gcBackendTxId = SJZMNBUGQ4
    * gcAmount = 100
    * gcCurrency = EUR
    * gcResultPayment = 4000  // erfolgreich (4502 = abgebrochen)
    * gcResultAVS = 4020      // erfolgreich (4021 - nicht durchführbar, 4022 nicht erfolgreich)
    * gcHash = 9846f333598f22258c8d90b981601da2
    * 
    * 
   Response Abbruch:
    * gcReference = 7e51f472-f6a1-4f10-ac21-9fd5c51b0e6c
    * gcMerchantTxId = 1234567890
    * gcBackendTxId = SJZMQ8M9C4
    * gcAmount = 450
    * gcCurrency = EUR
    * gcResultPayment = 4502
    * gcResultAVS = 4021
    * gcHash = ef939526adb1622a3049cb1d6e5c74c9
     * 
     */
    public function check_response () {
        // Get Parameter auslesen
        
        // Order Datensatz auslesen und prüfen
        
        // Alterscheck in DB schreiben falls User vorhanden
        
        
        
        // Erfolgsmail versenden
        
        $merchantID = rex_config::get('warehouse', 'giropay_merchand_id');
        $projectID = rex_config::get('warehouse', 'giropay_project_id');
        $projectPassword = rex_config::get('warehouse', 'giropay_project_pw');

        /* get transaction information */
        try {
                $request = new GiroCheckout_SDK_Request('getTransactionTool');
                $request->setSecret($projectPassword);
                $request->addParam('merchantId',$merchantID)
                        ->addParam('projectId',$projectID)
                        ->addParam('reference',rex_get('gcReference','string'))
                    //the hash field is auto generated by the SDK
                        ->submit();


                /* if request succeeded*/
                if($request->requestHasSucceeded()) {
                    /*
                    $request->getResponseParam('rc');
                    $request->getResponseParam('msg');
                    $request->getResponseParam('reference');
                    $request->getResponseParam('merchantTxId');
                    $request->getResponseParam('backendTxId');
                    $request->getResponseParam('amount');
                    $request->getResponseParam('currency');
                    $request->getResponseParam('resultPayment');
                    $request->getResponseParam('resultAVS');
                    $request->getResponseParam('obvName');
                     */
                    
                    warehouse::giropay_approved($request->getResponseParam('reference'), $request->getResponseParam('resultAVS'));
                    
                    if (rex::isDebugMode()) {
                        rex_logger::factory()->log('notice',json_encode([
                            'reference'=>$request->getResponseParam('reference'),
                            'resultAVS'=>$request->getResponseParam('resultAVS'),
                            'currency'=>$request->getResponseParam('currency'),
                            'amount'=>$request->getResponseParam('amount')
                        ]),[],__FILE__,__LINE__);
                    }
                    
                }
                /* if the request did not succeed */
                else {
                    $request->getResponseParam('rc');
                    $request->getResponseParam('msg');
                    $request->getResponseMessage($request->getResponseParam('rc'),'DE');
//                    dump($request->getResponseParam('msg'));
//                    warehouse::giropay_not_approved($request->getResponseParam('reference'));
                }
        } catch (Exception $e) { echo $e->getMessage(); }        
        
        
    }

}
