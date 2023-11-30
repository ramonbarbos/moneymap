<?php

use Adianti\Database\TRecord;


class TipoFolha extends TRecord
{
    const TABLENAME = 'tipo_folha';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('descricao');
    }

    
}
