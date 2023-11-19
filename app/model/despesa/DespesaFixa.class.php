<?php

use Adianti\Database\TRecord;


class DespesaFixa extends TRecord
{
    const TABLENAME = 'despesa_fixa';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('cpf');
        parent::addAttribute('evento_id');
        parent::addAttribute('evento_descricao');
        parent::addAttribute('valor');

    }
}
