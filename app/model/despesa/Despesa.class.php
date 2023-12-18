<?php

use Adianti\Database\TRecord;


class Despesa extends TRecord
{
    const TABLENAME = 'despesa';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('folha_id');
        parent::addAttribute('tp_folha');
        parent::addAttribute('cpf');
        parent::addAttribute('anoMes');
        parent::addAttribute('vl_despesa');
        parent::addAttribute('saldo');
        parent::addAttribute('situacao');

    }

    public function get_folha()
    {
        return TipoFolha::find($this->tp_folha);
    }
}
