<?php

use Adianti\Database\TRecord;


class Folha extends TRecord
{
    const TABLENAME = 'folha';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('tp_folha');
        parent::addAttribute('cpf');
        parent::addAttribute('anoMes');
        parent::addAttribute('vl_salario');
        parent::addAttribute('vl_desconto');

    }
    public function get_folha()
    {
        return TipoFolha::find($this->tp_folha);
    }
  
}
