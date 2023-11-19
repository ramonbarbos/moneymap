<?php

use Adianti\Database\TRecord;


class Lotacao extends TRecord
{
    const TABLENAME = 'lotacao';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('cpf');
        parent::addAttribute('ds_cargo');
        parent::addAttribute('cnpj');
        parent::addAttribute('ds_empresa');
        parent::addAttribute('dt_inicio');
        parent::addAttribute('dt_fim');
    }
}
