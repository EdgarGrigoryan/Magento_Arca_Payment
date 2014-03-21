<?php
class Studioone_ArCa_Block_Form_Payment extends Mage_Payment_Block_Form
{
	/**
	 * Instructions text
	 *
	 * @var string
	 */
	protected $_instructions;
	
	/**
	 * Block construction. Set block template.
	 */
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('arca/payment/form.phtml');
		Mage::log('My log entry', null, 'Studioone_ArCa_Block_Form_Arca.log');
	}
	
	/**
	 * Get instructions text from config
	 *
	 * @return string
	 */
	
	public function getInstructions()
	{
		if (is_null($this->_instructions)) {
			$this->_instructions = $this->getMethod()->getInstructions();
		}
		return $this->_instructions;
	}
}
?>