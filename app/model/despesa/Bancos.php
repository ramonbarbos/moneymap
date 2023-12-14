<?php

use Adianti\Database\TRecord;


class Bancos extends TRecord
{
    const TABLENAME = 'bancos';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('codigo_banco');
        parent::addAttribute('site');
        parent::addAttribute('telefone');
    }

    
}
