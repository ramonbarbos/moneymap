<?php

use Adianti\Database\TRecord;


class DespesaCartao extends TRecord
{
    const TABLENAME = 'despesa_cartao';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id_cartao_credito');
        parent::addAttribute('cpf');
        parent::addAttribute('anoMes');
        parent::addAttribute('valor_total');

    }

    public function get_folha()
    {
        return TipoFolha::find($this->tp_folha);
    }
}
