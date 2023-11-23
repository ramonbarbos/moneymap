<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Validator\TEmailValidator;
use Adianti\Validator\TMinLengthValidator;
use Adianti\Validator\TNumericValidator;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\THBox;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TAlert;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCheckList;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TDateTime;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TFormSeparator;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class FichaCadastralForm extends TPage
{
  private $form;

  use Adianti\base\AdiantiStandardFormTrait;

  public function __construct()
  {
    parent::__construct();

    parent::setTargetContainer('adianti_right_panel');
    $this->setAfterSaveAction(new TAction(['FichaCadastralList', 'onReload'], ['register_state' => 'true']));

    $this->setDatabase('sample');
    $this->setActiveRecord('FichaCadastral');


    // Criação do formulário
    $this->form = new BootstrapFormBuilder('form_ficha');
    $this->form->setFormTitle('Cadastro');
    $this->form->setClientValidation(true);
    $this->form->setColumnClasses(3, ['col-sm-4', 'col-sm-4', 'col-sm-4']);

    // Criação de fields
    $id = new TEntry('id');
    $cpf = new TEntry('cpf');
    $nome = new TEntry('nome');
    $dt_nascimento = new TDate('dt_nascimento');
    $sexo = new TCombo('sexo');
    $sexo->addItems(['M' => 'Masculino', 'F' => 'Feminino']);
    $email = new TEntry('email');
    $celular = new TEntry('celular');
    $lotacao =  new TEntry('lotacao');
    $cnpj = new TEntry('cnpj');
    $ds_empresa = new TEntry('ds_empresa');
    $dt_inicio = new TDate('dt_inicio');
    $dt_fim = new TDate('dt_fim');



    $this->form->addFields([new TLabel('Codigo')], [$id]);
    $this->form->addFields([new TLabel('CPF (*)')], [$cpf], [new TLabel('Nome (*)')], [$nome]);
    $this->form->addFields([new TLabel('Idade (*)')], [$dt_nascimento], [new TLabel('Sexo')], [$sexo]);
    $this->form->addContent([new TFormSeparator('Contato')]);
    $this->form->addFields([new TLabel('Email')], [$email], [new TLabel('Celular')], [$celular]);
    $this->form->addContent([new TFormSeparator('Lotação')]);
    $this->form->addFields([new TLabel('Cargo (*)')], [$lotacao], [new TLabel('CNPJ')], [$cnpj]);
    $this->form->addFields([new TLabel('Empresa')], [$ds_empresa], [new TLabel('Inicio (*)')], [$dt_inicio]);
    $this->form->addFields([new TLabel('Fim')], [$dt_fim]);



    $id->setEditable(false);
    $id->setSize('100%');
    $dt_nascimento->setSize('100%');
    $dt_nascimento->addValidation('Nascimento', new TRequiredValidator);
    $dt_nascimento->setMask('dd/mm/yyyy');
    $dt_nascimento->setDatabaseMask('yyyy-mm-dd');
    $cpf->setSize('100%');
    $cpf->addValidation('CPF', new TRequiredValidator);
    $cpf->setMask('999.999.999-99',true);
    $nome->addValidation('Nome', new TRequiredValidator);
    $email->addValidation('Email', new TEmailValidator);
    $celular->setMask('(99)99999-99999',true);
    $lotacao->setSize('100%');
    $lotacao->addValidation('Lotacao', new TRequiredValidator);
    $dt_inicio->addValidation('Inicio', new TRequiredValidator);
    $dt_inicio->setMask('dd/mm/yyyy');
    $dt_inicio->setDatabaseMask('yyyy-mm-dd');
    // Adicionar botão de salvar
    $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:plus green');
    $btn->class = 'btn btn-sm btn-primary';

    // Adicionar link para criar um novo registro
    $this->form->addActionLink(_t('New'), new TAction([$this, 'onEdit']), 'fa:eraser red');

    // Adicionar link para fechar o formulário
    $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

    // Vertical container
    $container = new TVBox;
    $container->style = 'width: 100%';
    $container->add($this->form);

    parent::add($container);
  }




  // Método fechar
  public function onClose($param)
  {
    TScript::create("Template.closeRightPanel()");
  }
}
