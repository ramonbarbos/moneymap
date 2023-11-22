<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Control\TWindow;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredListValidator;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TToast;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TFormSeparator;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TActionLink;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class DespesaView extends TPage
{
  private $form;
  private $fieldlist;

  use Adianti\base\AdiantiStandardFormTrait;


  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct();

    // create form and table container
    $this->form = new BootstrapFormBuilder('my_form');
    $this->form->setFormTitle('Novas Despesas');
    $this->form->setClientValidation(true);
    $this->form->setColumnClasses(3, ['col-sm-3', 'col-sm-3', 'col-sm-3','col-sm-3']);

    $this->setDatabase('sample');
    $this->setActiveRecord('Despesa');


    // Criação de fields
    $id = new TEntry('id');

    $anoMes         = new TDBUniqueSearch('anoMes', 'sample', 'anoMes', 'descricao', 'descricao');
    $anoMes->addValidation('anoMes', new TRequiredValidator);
    $anoMes->setChangeAction(new TAction(['DespesaService', 'onCPFChange']));

    $cpf         = new TDBUniqueSearch('cpf', 'sample', 'Folha', 'cpf', 'cpf');
    $cpf->setChangeAction(new TAction(['DespesaService', 'onCPFChange']));

    $vl_despesa = new TEntry('vl_despesa');
    $vl_salario = new TEntry('vl_salario');


    $this->form->addFields([new TLabel('Codigo')], [$id]);
    $this->form->addFields([new TLabel('Mês (*)')], [$anoMes],[new TLabel('CPF (*)')], [$cpf]);
    $this->form->addFields([new TLabel('Salario')], [$vl_salario], [new TLabel('Despesa')], [$vl_despesa]);
    $this->form->addContent([new TFormSeparator('Itens')]);

    $id->setEditable(false);
    $id->setSize('100%');
    $cpf->addValidation('cpf', new TRequiredValidator);
    $cpf->setMinLength(0);
    //$cpf->setMask('<b>{cpf}</b>');
    $cpf->setSize('100%');
    $anoMes->setMinLength(0);
    
    $anoMes->setSize('100%');

    $vl_despesa->setEditable(false);
    $vl_despesa->setNumericMask(2, '.', '', true);
    $vl_salario->setEditable(false);
    $vl_salario->setNumericMask(2, '.', '', true);


    $uniq = new THidden('uniq[]');

    $evento_id = new TDBCombo('evento_id[]', 'sample', 'Evento', 'id', 'descricao');
    $evento_id->enableSearch();
   // $evento_id->addItems(['1' => '<b>One</b>', '2' => '<b>Two</b>', '3' => '<b>Three</b>', '4' => '<b>Four</b>', '5' => '<b>Five</b>']);
    $evento_id->setSize('100%');

    $descricao = new TEntry('descricao[]');
    $descricao->setSize('100%');

    $valor = new TEntry('valor[]');
    $valor->setNumericMask(2, ',', '.', true);
    $valor->setSize('100%');
    $valor->style = 'text-align: right';

    $saldo = new TEntry('saldo[]');
    $saldo->setNumericMask(2, ',', '.', true);
    $saldo->setSize('100%');
    $saldo->style = 'text-align: right';

    $dt_despesa = new TDate('dt_despesa[]');
    $dt_despesa->setSize('100%');

    $this->fieldlist = new TFieldList;
    $this->fieldlist->generateAria();
    $this->fieldlist->width = '100%';
    $this->fieldlist->name  = 'my_field_list';
    $this->fieldlist->addField('<b>Unniq</b>',  $uniq,   ['width' => '0%', 'uniqid' => true]);
    $this->fieldlist->addField('<b>Date</b>',   $dt_despesa,   ['width' => '25%']);
    $this->fieldlist->addField('<b>Combo</b>',  $evento_id,  ['width' => '25%']);
    $this->fieldlist->addField('<b>Text</b>',   $descricao,   ['width' => '25%']);
    $this->fieldlist->addField('<b>Number</b>', $valor, ['width' => '25%', 'sum' => true]);
    $this->fieldlist->addField('<b>Number</b>', $saldo, ['width' => '25%', 'sum' => true]);

    // $this->fieldlist->setTotalUpdateAction(new TAction([$this, 'onTotalUpdate']));

    $this->fieldlist->enableSorting();

    $this->form->addField($evento_id);
    $this->form->addField($descricao);
    $this->form->addField($valor);
    $this->form->addField($saldo);
    $this->form->addField($dt_despesa);

    $descricao->addValidation('descricao', new TRequiredListValidator);

    //$this->fieldlist->addButtonFunction("__adianti_message('Row data', JSON.stringify(tfieldlist_get_row_data(this)))", 'fa:info-circle blue', 'Show "Text" field');

    $this->fieldlist->addButtonAction(new TAction([$this, 'showRow']), 'fa:info-circle purple', 'Show text');
    //$this->fieldlist->disableRemoveButton();
    $this->fieldlist->setRemoveAction(new TAction([$this, 'actionX']));


    $this->fieldlist->addHeader();
    $this->fieldlist->addDetail(new stdClass);
    $this->fieldlist->addCloneAction();
    // add field list to the form
    $this->form->addContent([$this->fieldlist]);

    // form actions
    //$this->form->addActionLink(_t('New'), new TAction(['DespesaList', 'onEdit'], ['register_state' => 'false']), 'fa:plus green'  );
    $bt5b = new TButton('Voltar');
    $bt5b->class = 'btn ';
    $bt5b->style = 'background-color: grey; color: white';

    $bt5b->setLabel('Voltar');
    $bt5b->addFunction("__adianti_load_page('index.php?class=DespesaList');");
    $this->form->addAction('Save', new TAction([$this, 'onSave'], ['static' => '1']), 'fa:save blue');
    $this->form->addAction('Clear', new TAction([$this, 'onClear']), 'fa:eraser red');

    // wrap the page content using vertical box
    $vbox = new TVBox;
    $vbox->style = 'width: 100%';
    $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
    $vbox->add($this->form);
    $vbox->add($bt5b);

    parent::add($vbox);
  }

  public static function actionX($param)
  {
    $win = TWindow::create('test', 0.6, 0.8);
    $win->add('<pre>' . str_replace("\n", '<br>', print_r($param, true)) . '</pre>');
    $win->show();
  }

  public static function showRow($param)
  {
    new TMessage('info', str_replace(',', '<br>', json_encode($param)));
  }


  public static function onClear($param)
  {
    TFieldList::clear('my_field_list');
    TFieldList::addRows('my_field_list', 1);
  }


  public function onSave($param)
  {
    try {
      TTransaction::open('sample');

      $data = $this->form->getData();
      $this->form->validate();


      $despesa = new Despesa;
      $despesa->fromArray((array) $data);


      if (!empty($despesa->id)) {
        ItemDespesa::where('despesa_id', '=', $despesa->id)->delete();

        $total = 0;

        if (!empty($param['evento_id'])) {
          foreach ($param['evento_id'] as $key => $item_id) {

            $item = new ItemDespesa;
            $item->despesa_id   = $despesa->id;
            $item->dt_despesa   = $param['dt_despesa'][$key];
            $item->evento_id   = $param['evento_id'][$key];
            $item->descricao   = $param['descricao'][$key];
            $item->valor      = (float) $param['valor'][$key];
            $item->saldo      = (float) $param['saldo'][$key];
            $item->store();
            $total +=  $item->valor;
          }
        }
        $despesa->vl_despesa = $total;
        $despesa->anoMes = date('Ym');
        $despesa->store();

        new TMessage('info', 'Alterado com sucesso',$this->afterSaveAction); //$this->afterSaveAction

      } else {
        $despesa->store();

        ItemDespesa::where('despesa_id', '=', $despesa->id)->delete();

        $total = 0;

        if (!empty($param['evento_id'])) {
          foreach ($param['evento_id'] as $key => $item_id) {

            $item = new ItemDespesa;
            $item->despesa_id   = $despesa->id;
            $item->dt_despesa   = $param['dt_despesa'][$key];
            $item->evento_id   = $param['evento_id'][$key];
            $item->descricao   = $param['descricao'][$key];
            $item->valor      = (float) $param['valor'][$key];
            $item->saldo      = (float) $param['saldo'][$key];
            $item->store();
            $total +=  $item->valor;
          }
        }


        $despesa->vl_despesa = $total;
        $despesa->anoMes = date('Ym');
        $despesa->store();

        TForm::sendData('my_form', (object) ['id' => $despesa->id]);
        new TMessage('info', 'Registos Salvos',$this->afterSaveAction); //
      }

      TTransaction::close();
    } catch (Exception $e) {
      new TMessage('error', $e->getMessage());
      $this->form->setData($this->form->getData());
      TTransaction::rollback();
    }
  }

  
  public function onEdit($param)
  {
    try {

      TTransaction::open('sample');

      if (isset($param['key'])) {
        $key = $param['key'];

        $object = new Despesa($key);
        $item_despesas = ItemDespesa::where('despesa_id', '=', $object->id)->load();
        $folha   =  Folha::where('cpf', '=', $object->cpf)->first();

        $this->form->getField('cpf')->setEditable(false);


        $data = new stdClass;
        $data->id_item = [];
        $data->dt_despesa = [];
        $data->evento_id = [];
        $data->descricao = [];
        $data->valor = [];
        $data->saldo = [];


        foreach ($item_despesas as $item) {

          TFieldList::addRows('my_field_list', 1);
          $data->id_item[] = $item->id_item;
          $data->dt_despesa[] = $item->dt_despesa;
          $data->evento_id[] = $item->evento_id;
          $data->descricao[] = $item->descricao;
          $data->valor[] = $item->valor;
          $data->saldo[] = $item->saldo;

          TForm::sendData('my_form', $data);

        }
        TForm::sendData('my_form', (object) ['vl_salario' => $folha->vl_salario]);

        $this->form->setData($object);
        TTransaction::close();
      } else {
        $this->form->clear();
      }
    } catch (Exception $e) {
      new TMessage('error', $e->getMessage());
      TTransaction::rollback();
    }
  }
 

}
