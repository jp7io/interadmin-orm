<?php

class Jp7_InterAdmin_Registro extends InterAdmin
{
	public function getUrl()
	{
		$tipoObj = new Jp7_InterAdmin_Tipo($this->getTipo()->id_tipo);
		return $tipoObj->getUrl() . '/id/' . $this->id;
	}
}