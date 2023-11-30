<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Validator\TincidenciaValidator;
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
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Util\TTextDisplay;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class EventoForm extends TPage
{
  private $form;

  use Adianti\base\AdiantiStandardFormTrait;

  public function __construct()
  {
    parent::__construct();

    parent::setTargetContainer('adianti_right_panel');
    $this->setAfterSaveAction(new TAction(['EventoList', 'onReload'], ['register_state' => 'true']));

    $this->setDatabase('sample');
    $this->setActiveRecord('Evento');


    // Criação do formulário
    $this->form = new BootstrapFormBuilder('form_evento');
    $this->form->setFormTitle('Cadastro');
    $this->form->setClientValidation(true);
    $this->form->setColumnClasses(3, ['col-sm-4', 'col-sm-4', 'col-sm-4']);

    // Criação de fields
    $id = new TEntry('id');
    $descricao = new TEntry('descricao');
    $fixo = new TCombo('fixo');
    $fixo->addItems(['0' => 'Não', '1' => 'Sim']);
    $incidencia = new TCombo('incidencia');
    $incidencia->addItems(['DD' => 'Dedução','D' => 'Desconto', 'P' => 'Provento']);
    $formula = new TEntry('formula');
  
    $a = new TTextDisplay('Operadores: {S - Salario} | {P - Previdencia}', 'red', 12, 'bi');

    $this->form->addFields([new TLabel('Codigo (*)')], [$id],);
    $this->form->addFields(  [new TLabel('Descricao (*)')], [$descricao], [new TLabel('Fixo')], [$fixo]);
    $this->form->addFields( [new TLabel('Formula')], [$formula], [new TLabel('Incidencia (*)')], [$incidencia], );
    $this->form->addFields( [new TLabel('')],[$a]);
    //$this->form->add($a);
  

    $id->setEditable(false);
    $id->setSize('100%');
    $descricao->addValidation('descricao', new TRequiredValidator);

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
