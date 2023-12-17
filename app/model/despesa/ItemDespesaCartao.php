<?php

use Adianti\Database\TRecord;


class ItemDespesaCartao extends TRecord
{
    const TABLENAME = 'item_despesa_cartao';
    const PRIMARYKEY= 'id_item';
    const IDPOLICY =  'max'; // {max, serial}
    
    

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('despesa_cartao_id');
        parent::addAttribute('evento_id');
        parent::addAttribute('dt_despesa');
        parent::addAttribute('descricao');
        parent::addAttribute('parcela');
        parent::addAttribute('valor');
        parent::addAttribute('fl_situacao');

    }
}
