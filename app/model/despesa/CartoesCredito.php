<?php

use Adianti\Database\TRecord;


class CartoesCredito extends TRecord
{
    const TABLENAME = 'cartoes_credito';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome_titular');
        parent::addAttribute('cpf');
        parent::addAttribute('numero_cartao');
        parent::addAttribute('data_validade');
        parent::addAttribute('banco_associado');
    }

    
    public function get_banco()
    {
        return Bancos::find($this->banco_associado);
    }
    
}
