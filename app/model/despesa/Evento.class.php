<?php

use Adianti\Database\TRecord;


class Evento extends TRecord
{
    const TABLENAME = 'evento';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('descricao');
        parent::addAttribute('fixo');
        parent::addAttribute('incidencia');
        parent::addAttribute('formula');
        parent::addAttribute('banco_associado');
        parent::addAttribute('cartao');
    }

        
    public function get_banco()
    {
        return CartoesCredito::find($this->cartao);
    }
}
