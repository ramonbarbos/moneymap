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
        parent::addAttribute('cpf');
        parent::addAttribute('anoMes');
        parent::addAttribute('vl_salario');

    }

  
}
