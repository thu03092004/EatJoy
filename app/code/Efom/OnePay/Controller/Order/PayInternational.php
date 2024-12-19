<?php

namespace Efom\OnePay\Controller\Order;

use Magento\Framework\App\Action\Context;

class PayInternational extends \Magento\Framework\App\Action\Action
{
	/** @var  \Magento\Sales\Model\Order */
	protected $order;
	/** @var  \Magento\Checkout\Model\Session */
	protected $checkoutSession;
	/** @var  \Magento\Framework\App\Config\ScopeConfigInterface */
	protected $scopeConfig;
	public function __construct( Context $context,
		\Magento\Sales\Model\Order $order,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
		)
	{
		parent::__construct( $context );
		$this->order = $order;
		$this->checkoutSession = $checkoutSession;
		$this->scopeConfig = $scopeConfig;
	}

	/**
     * Order success action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
    	$vpc_TxnResponseCode = $this->getRequest()->getParam('vpc_TxnResponseCode','');
    	//$isSuccess = false;
    	//if($vpc_TxnResponseCode == "0")
	    //{
	    	//check hash
		    $responseHash = $this->getRequest()->getParam('vpc_SecureHash','');
		    $SECURE_SECRET = $this->scopeConfig->getValue('payment/onepayinternational/hash_code');
		    $responseParams = $this->getRequest()->getParams();
		    ksort ($responseParams);
		    $md5HashData = '';
		    foreach($responseParams as $key=>$value)
		    {
			    if ( $key != "vpc_SecureHash" && strlen($value) > 0 && ((substr($key, 0,4)=="vpc_") || (substr($key,0,5) =="user_"))) {
				    $md5HashData .= $key . "=" . $value . "&";
			    }
		    }
		    $md5HashData = rtrim($md5HashData, "&");
		    $hash = strtoupper(hash_hmac('SHA256', $md5HashData, pack('H*',$SECURE_SECRET)));
		    if($hash == strtoupper($responseHash) && $vpc_TxnResponseCode == "0")
		    {
			    $vpc_OrderInfo = $this->getRequest()->getParam('vpc_OrderInfo','000000000');
			    $order = $this->order->loadByIncrementId($vpc_OrderInfo);
			    if($order->getId())
			    {
				    if($this->checkoutSession->getLastOrderId() == $order->getId())
				    {
					    $amount = $this->getRequest()->getParam('vpc_Amount','0');
					    $order->setTotalPaid(floatval($amount)/100);
					    $order->setStatus($order::STATE_PAYMENT_REVIEW); //STATE_PROCESSING STATE_PAYMENT_REVIEW
					    $order->save();
					    //$isSuccess = true;
				    }
			    }
				
				$this->messageManager->addSuccess('Đã thanh toán thành công bằng OnePay quốc tế');
				return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
				
		    } else if ($hash == strtoupper($responseHash) && $vpc_TxnResponseCode != "0"){
				$vpc_OrderInfo = $this->getRequest()->getParam('vpc_OrderInfo','000000000');
			    $order = $this->order->loadByIncrementId($vpc_OrderInfo);
			    if($order->getId())
			    {
				    if($this->checkoutSession->getLastOrderId() == $order->getId())
				    {
					    $amount = $this->getRequest()->getParam('vpc_Amount','0');
					    $order->setTotalPaid(floatval($amount)/100);
					    //$order->setStatus($order::STATE_PAYMENT_REVIEW);
						$order->setStatus("payment_onepay_fail");
					    $order->save();
					    //$isSuccess = false;
				    }
			    }
				$this->messageManager->addError(
					'Thanh toán OnePay thất bại. '. $this->getResponseDescription($vpc_TxnResponseCode)
				);
				return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure');
			
			}
			else {
			
				$this->messageManager->addError(
					'Thanh toán OnePay pending'
				);
				return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure');
			
			}
			
			
		//}

		/*
	    if(!$isSuccess)
	    {
	    	$this->messageManager->addError(
	    		'Thanh toán OnePay thất bại. '. $this->getResponseDescription($vpc_TxnResponseCode)
		    );
			return $this->resultRedirectFactory->create()->setPath('checkout/onepage/failure');
	    }
	    else{
		    $this->messageManager->addSuccess('Đã thanh toán thành công bằng OnePay quốc tế');
			return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
	    }
		*/


	    //return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');https://yr-beta.goretail.vn/vn/checkout/onepage/failure

    }
	
	public function getResponseDescription($responseCode)
	{

		switch ($responseCode) {
			case "0" :
				$result = "Transaction Successful";
				break;
			case "?" :
				$result = "Transaction status is unknown";
				break;
			case "1" :
				$result = "Bank system reject";
				break;
			case "2" :
				$result = "Bank Declined Transaction";
				break;
			case "3" :
				$result = "No Reply from Bank";
				break;
			case "4" :
				$result = "Expired Card";
				break;
			case "5" :
				$result = "Insufficient funds";
				break;
			case "6" :
				$result = "Error Communicating with Bank";
				break;
			case "7" :
				$result = "Payment Server System Error";
				break;
			case "8" :
				$result = "Transaction Type Not Supported";
				break;
			case "9" :
				$result = "Bank declined transaction (Do not contact Bank)";
				break;
			case "B" :
				$result = "Fraud Risk Block";
				break;
			case "Z" :
				$result = "Transaction was block by OFD";
				break;		
			case "F" :
				$result = "3D Secure Authentication failed";
				break;
			case "I" :
				$result = "Card Security Code verification failed";
				break;			
			case "99" :
				$result = "User Cancel";
				break;
			default  :
				$result = "Unable to be determined";
		}
		return $result;
	}
}
