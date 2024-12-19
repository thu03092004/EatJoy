<?php

namespace Efom\OnePay\Controller\Order;

use Magento\Framework\App\Action\Context;

class Pay extends \Magento\Framework\App\Action\Action
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
		    $SECURE_SECRET = $this->scopeConfig->getValue('payment/onepay/hash_code');
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
				
				$this->messageManager->addSuccess('Đã thanh toán thành công bằng Thẻ ATM nội địa');
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
		    $this->messageManager->addSuccess('Đã thanh toán thành công bằng OnePay thẻ ATM nội địa');
			return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
	    }

		*/

	    //return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');

    }
	
	public function getResponseDescription($responseCode) {
	
		switch ($responseCode) {
			case "0" :
				$result = "Giao dịch thành công - Approved";
				break;
			case "1" :
				$result = "Ngân hàng từ chối giao dịch - Bank Declined";
				break;
			case "3" :
				$result = "Mã đơn vị không tồn tại - Merchant not exist";
				break;
			case "4" :
				$result = "Không đúng access code - Invalid access code";
				break;
			case "5" :
				$result = "Số tiền không hợp lệ - Invalid amount";
				break;
			case "6" :
				$result = "Mã tiền tệ không tồn tại - Invalid currency code";
				break;
			case "7" :
				$result = "Lỗi không xác định - Unspecified Failure ";
				break;
			case "8" :
				$result = "Số thẻ không đúng - Invalid card Number";
				break;
			case "9" :
				$result = "Tên chủ thẻ không đúng - Invalid card name";
				break;
			case "10" :
				$result = "Thẻ hết hạn/Thẻ bị khóa - Expired Card";
				break;
			case "11" :
				$result = "Thẻ chưa đăng ký sử dụng dịch vụ - Card Not Registed Service(internet banking)";
				break;
			case "12" :
				$result = "Ngày phát hành/Hết hạn không đúng - Invalid card date";
				break;
			case "13" :
				$result = "Vượt quá hạn mức thanh toán - Exist Amount";
				break;
			case "21" :
				$result = "Số tiền không đủ để thanh toán - Insufficient fund";
				break;
			case "99" :
				$result = "Người sủ dụng hủy giao dịch - User cancel";
				break;
			default :
				$result = "Giao dịch thất bại - Failured";
		}
		return $result;
	}

	
	
}
