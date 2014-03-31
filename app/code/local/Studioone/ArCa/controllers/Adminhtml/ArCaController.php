<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Studioone
 * @package     Studioone_ArCa

 */


class Studioone_ArCa_Adminhtml_ArCaController extends Mage_Adminhtml_Controller_Action
{

    protected function _isActionAllowed($action)
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/arca/' . $action);
    }

    public function indexAction()
    {
    	
		
        $this->loadLayout();
		$this->_initLayoutMessages('adminhtml/session');
        $this->_setActiveMenu('sales/arca');
        $this->renderLayout();
		
    }

    /**
     * grid
     */
    public function gridAction()
    {
        $block = $this->getLayout()->createBlock('studioone_arca/adminhtml_transactions_grid');
        $this->getResponse()->setBody($block->toHtml());
    }

    /**
     * grid
     */
    public function cartgridAction()
    {
        $transaction = Mage::getModel('studioone_arca/transactions')->load(
            $this->getRequest()->getParam('id')
        );
        Mage::register('transaction', $transaction);
        $block = $this->getLayout()->createBlock('studioone_arca/adminhtml_transactions_view_tab_cart');
        $this->getResponse()->setBody($block->toHtml());
    }

    public function viewAction()
    {
        $transaction = Mage::getModel('studioone_arca/transactions')->load(
            $this->getRequest()->getParam('id')
        );
        Mage::register('transaction', $transaction);
        if (!$order->getId()) {
            return $this->_redirect('*/*/index');
        }
        $this->loadLayout();
        $this->_initLayoutMessages('adminhtml/session');
        $this->_setActiveMenu('sales/arca');


        $this->renderLayout();
    }

    /**
     * check permissions
     * @return bool
     */
    protected function _isAllowed()
    {
    	
		
        switch ($this->getRequest()->getActionName()) {
            case 'index':
            case 'grid':
            case 'cartgrid':
                return $this->_isActionAllowed('grid');
                break;
            case 'view':
                return $this->_isActionAllowed('view');
                break;
            case 'delete':
                return $this->_isActionAllowed('delete');
                break;
        }
        return false;
    }
}