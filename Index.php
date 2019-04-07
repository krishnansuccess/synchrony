<?php


namespace Bd\CustomOrder\Controller\Add;
use Magento\Checkout\Model\Cart;
class Index extends \Magento\Framework\App\Action\Action
{

    protected $formKey;   
protected $cart;
protected $product;

public function __construct(
\Magento\Framework\App\Action\Context $context,
\Magento\Framework\Data\Form\FormKey $formKey,
\Magento\Checkout\Model\Cart $cart,
\Magento\Catalog\Model\Product $product,
\Magento\Checkout\Model\Session $checkoutSession,
array $data = []) {
    $this->formKey = $formKey;
    $this->cart = $cart;
    $this->product = $product;  
    $this->checkoutSession = $checkoutSession;    
    parent::__construct($context);
}

public function execute()
 { 
  $productId =10;
  $params = array(
                // 'form_key' => $this->formKey->getFormKey(),
                'product' => $productId, //product Id
                'qty'   =>1 //quantity of product                
            );              
    //Load the product based on productID   
    $_product = $this->product->load($productId);       
    $this->cart->addProduct($_product, $params);
    $this->cart->save();
    $quote = $this->checkoutSession->getQuote();
    $quoteItems= $quote->getAllItems();
    foreach ($quoteItems as $item )
    {
            $item = ( $item->getParentItem() ? $item->getParentItem() : $item );
            $custom_text = 'dummy';
            $item->setCustomOption($custom_text);
            $item->getProduct()->setIsSuperMode(true);
            $item->save();
    }
    $quote->save();
    $this->_redirect('checkout/cart/');
        // return $this->resultPageFactory->create();
 }
}