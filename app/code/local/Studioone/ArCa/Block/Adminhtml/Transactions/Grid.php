<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Smasoft
 * @package     Smasoft_Oneclikorder
 * @copyright   Copyright (c) 2013 Slabko Michail. <l.nagash@gmail.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Studioone_ArCa_Block_Adminhtml_Transactions_Grid extends Mage_Adminhtml_Block_Widget_Grid
{


    /**
     * Init Grid default properties
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('studioone_list_grid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);

    }

    protected function _prepareCollectionBefore()
    {
        /** @var $collection Smasoft_Oneclickorder_Model_Resource_Order_Collection */
        $collection = Mage::getModel('studioone_arca/arca')->getCollection();
        $collection->getSelect();
        $collection->joinCustomerAttribute('firstname');
        $collection->joinCustomerAttribute('lastname');

        return $collection;
    }


    /**
     * @return this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_prepareCollectionBefore();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }


    /**
     * Prepare Grid columns
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        $this->_customFieldsOptions();
        return parent::_prepareColumns();
    }

    protected function _customFieldsOptions()
    {
        /** @var $helper Studioone_ArCa_Helper_Data */
        $helper = Mage::helper('studioone_arca');
        $this->addColumn('entity_id', array(
            'header' => $helper->__('#'),
            'width' => '10px',
            'index' => 'entity_id',
            'filter_index' => 'main_table.entity_id',
        ));


        $this->addColumn('customer_id', array(
            'header' => $helper->__('Customer Name'),
            'index' => 'customer_id',
            'getter' => 'getGridCustomerName',
            'renderer' => 'smasoft_oneclickorder/adminhtml_orders_grid_renderer_customer',
        ));
      

        $this->addColumn('create_date', array(
            'header' => $helper->__('Create  Date'),
            'index' => 'create_date',
            'filter_index' => 'create_date',
            'type' => 'datetime',
            'width' => '220px',
        ));

        if (Mage::getSingleton('admin/session')
        	->isAllowed('sales/arca/view')) {
            $this->addColumn('action',
                array(
                    'header' => $helper->__('Action'),
                    'type' => 'action',
                    'actions' => array(
                        array(
                            'caption' => $helper->__('View'),
                            'url' => $this->getUrl('*/arca/view', array('id' => '$entity_id')),
                            'field' => 'entity_id'
                        ),
                    ),
                    'filter' => false,
                    'sortable' => false,
                    'index' => 'studioone_arca',
                    'is_system' => true,
                    'width' => 100
                ));
        }

    }

    /**
     * Return row URL for js event handlers
     *
     * @param null $row
     * @return string
     */
    public function getRowUrl($row)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/arca/view')) {
            return $this->getUrl('*/arca/view', array('id' => $row->getEntityId()));
        }
        return null;
    }

    /**
     * Grid url getter
     *
     * @return string current grid url
     */
    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/arca/grid', array('_current' => true));
    }


}
