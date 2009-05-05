<?php

class Jp7_InterAdmin_Form extends InterAdminTipo
{
	public static function getForm($id_tipo)
	{
		$form = new Zend_Form;
		$form->setAction('/path/to/action');
		$form->setMethod('post');
		$form->setAttrib('id', 'login');
		
		$form->addElement(new Zend_Form_Element_Text('teste'));
		
		echo $form;
		exit;
	}
}