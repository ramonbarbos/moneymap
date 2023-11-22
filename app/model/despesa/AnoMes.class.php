<?php

use Adianti\Database\TRecord;


class AnoMes extends TRecord
{
    const TABLENAME = 'ano_mes';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('descricao');
    }

    
}
