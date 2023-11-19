<?php

use Adianti\Database\TRecord;


class FichaCadastral extends TRecord
{
    const TABLENAME = 'ficha_cadastral';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('cpf');
        parent::addAttribute('nome');
        parent::addAttribute('dt_nascimento');
        parent::addAttribute('sexo');
        parent::addAttribute('email');
        parent::addAttribute('celular');
        parent::addAttribute('lotacao');
        parent::addAttribute('cnpj');
        parent::addAttribute('ds_empresa');
        parent::addAttribute('dt_inicio');
        parent::addAttribute('dt_fim');

    }
}
