<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Validator\Tnumero_cartaoValidator;
use Adianti\Validator\TMinLengthValidator;
use Adianti\Validator\TNumericValidator;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\THBox;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TAlert;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TToast;
use Adianti\Widget\Form\TCheckList;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TDateTime;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TFormSeparator;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Util\TTextDisplay;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class CartaoForm extends TPage
{
  private $form;

  use Adianti\base\AdiantiStandardFormTrait;

  public function __construct()
  {
    parent::__construct();

    parent::setTargetContainer('adianti_right_panel');
    $this->setAfterSaveAction(new TAction(['CartaoList', 'onReload'], ['register_state' => 'true']));

    $this->setDatabase('sample');
    $this->setActiveRecord('CartoesCredito');


    // Criação do formulário
    $this->form = new BootstrapFormBuilder('form_cartao');
    $this->form->setFormTitle('Cadastro de Cartão');
    $this->form->setClientValidation(true);
    $this->form->setColumnClasses(3, ['col-sm-4', 'col-sm-4', 'col-sm-4']);

    // Criação de fields
    $id = new TEntry('id');
    $nome_titular = new TEntry('nome_titular');
    $numero_cartao = new TEntry('numero_cartao');
    $data_validade = new TDate('data_validade');
    $banco_associado = new TDBUniqueSearch('banco_associado', 'sample', 'Bancos', 'id', 'nome');
    $cpf = new TDBUniqueSearch('cpf', 'sample', 'FichaCadastral', 'cpf', 'cpf');
    $nome_cartao = new THidden('nome_cartao');
    
  

    $this->form->addFields([new TLabel('Codigo')], [$id],[new TLabel('Banco')],[$banco_associado]);
    $this->form->addFields(  [new TLabel('Titular (*)')], [$nome_titular], [new TLabel('CPF (*)')], [$cpf] );
    $this->form->addFields( [new TLabel('Numero (*)')], [$numero_cartao], [new TLabel('Data')], [$data_validade],);
    $this->form->addFields([new TLabel('')], [$nome_cartao]);

    //$this->form->add($a);
  

    $id->setEditable(false);
    $id->setSize('100%');
    $nome_titular->addValidation('nome_titular', new TRequiredValidator);
    $nome_titular->setSize('100%');
    $numero_cartao->setSize('100%');
    $data_validade->setSize('100%');
    $banco_associado->setSize('100%');
    $banco_associado->setMinLength(0);
    $cpf->setSize('100%');
    $cpf->setMinLength(0);

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

  public function onSave($param)
  {
    try {
      TTransaction::open('sample');

      $data = $this->form->getData();
      $this->form->validate();


      $cartao = new CartoesCredito;
      $cartao->fromArray((array) $data);
      $banco = new Bancos($param['banco_associado']);
      $cartao->nome_cartao = $banco->nome;
      // if (!empty($cartao->id)) {

      // }
      $cartao->store();
      new TMessage('info', 'Registos Salvos', $this->afterSaveAction); //
      TTransaction::close();
    } catch (Exception $e) {
      new TMessage('error', $e->getMessage());
      $this->form->setData($this->form->getData());
      TTransaction::rollback();
    }
  }

  // Método fechar
  public function onClose($param)
  {
    TScript::create("Template.closeRightPanel()");
  }
}
