<?php

use Adianti\Database\TRecord;


class ItemFolha extends TRecord
{
    const TABLENAME = 'item_folha';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('folha_id');
        parent::addAttribute('evento_id');
        parent::addAttribute('tipo');
        parent::addAttribute('ref');
        parent::addAttribute('parcela');
        parent::addAttribute('valor');

    }
}
