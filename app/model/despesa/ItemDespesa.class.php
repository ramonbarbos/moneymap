<?php

use Adianti\Database\TRecord;


class ItemDespesa extends TRecord
{
    const TABLENAME = 'item_despesa';
    const PRIMARYKEY= 'id_item';
    const IDPOLICY =  'max'; // {max, serial}
    
    

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('despesa_id');
        parent::addAttribute('dt_despesa');
        parent::addAttribute('evento_id');
        parent::addAttribute('descricao');
        parent::addAttribute('valor');
        parent::addAttribute('saldo');
        parent::addAttribute('fl_situacao');

    }
}
