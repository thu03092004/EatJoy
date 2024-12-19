<?php


namespace Efom\OnePay\Controller\Order;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\ScopeInterface;

class Info extends \Magento\Framework\App\Action\Action
{
	protected $resultPageFactory;
	protected $jsonFac;
	/** @var  \Magento\Sales\Model\Order */
	protected $order;
	/** @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig */
	protected $scopeConfig;
	/** @var  \Magento\Store\Model\StoreManagerInterface */
	protected $storeManager;
	/** @var  \Magento\Checkout\Model\Session */
	protected $checkoutSession;


	public function __construct(
		Context $context,
		PageFactory $resultPageFactory,
		\Magento\Framework\Controller\Result\Json $json,
		\Magento\Sales\Model\Order $order,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Checkout\Model\Session $checkoutSession
	)
	{
		parent::__construct($context);
		$this->resultPageFactory = $resultPageFactory;
		$this->jsonFac = $json;
		$this->order = $order;
		$this->scopeConfig = $scopeConfig;
		$this->storeManager = $storeManager;
		$this->checkoutSession = $checkoutSession;
	}

	public function execute()
	{



		$id = $this->getRequest()->getParam('order_id',0);
		$order = $this->order->load(intval($id));
		//$url = "https://mtf.onepay.vn/onecomm-pay/vpc.op?";
		$url = $this->scopeConfig->getValue('payment/onepay/payment_url')."?";
		if($order->getId())
		{
			$incrementID = $order->getIncrementId();


			$returnUrl = $this->storeManager->getStore()->getBaseUrl();
			$returnUrl = rtrim($returnUrl,"/");
			$returnUrl .= "/efomonepay/order/pay";

			$md5HashData = '';

			$arrParams = [
				'vpc_Version'=>'2',
				'vpc_Currency'=>'VND',
				'vpc_Command'=>'pay',
				'vpc_AccessCode'=>$this->scopeConfig->getValue('payment/onepay/access_code'),
				'vpc_Merchant'=>$this->scopeConfig->getValue('payment/onepay/merchant_id'),
				'vpc_Locale'=>'vn',
				'vpc_ReturnURL'=>$returnUrl,
				'vpc_MerchTxnRef'=>'precita'.$incrementID,
				'vpc_OrderInfo'=>$incrementID,
				//'vpc_Amount'=>$order->getTotalDue()*100,
				'vpc_Amount'=> round($order->getTotalDue()*100, 0),
				'vpc_TicketNo'=>$order->getRemoteIp(),
				'AgainLink'=> $this->storeManager->getStore()->getBaseUrl().'/checkout',
				'Title'=>'OnePAY Payment Gateway'
			];
			ksort ($arrParams);
			foreach($arrParams as $key=>$value)
			{
				$url .= urlencode($key)."=".urlencode($value)."&";
				if ((strlen($value) > 0) && ((substr($key, 0,4)=="vpc_") || (substr($key,0,5) =="user_"))) {
					$md5HashData .= $key . "=" . $value . "&";
				}
			}
			$md5HashData = rtrim($md5HashData, "&");
			$SECURE_SECRET = $this->scopeConfig->getValue('payment/onepay/hash_code');
			$hash = strtoupper(hash_hmac('SHA256', $md5HashData, pack('H*',$SECURE_SECRET)));
			$vpcURL = "vpc_SecureHash=" . $hash;
			$url .= $vpcURL;

		}

		$this->jsonFac->setData($url);
		return $this->jsonFac;
	}
}