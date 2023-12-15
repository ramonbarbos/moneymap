<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Control\TWindow;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredListValidator;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TToast;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TCheckButton;
use Adianti\Widget\Form\TCheckGroup;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TFormSeparator;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TRadioGroup;
use Adianti\Widget\Form\TSelect;
use Adianti\Widget\Util\TActionLink;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCheckGroup;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class DespesaCartaoView extends TPage
{
  private $form;
  private $fieldlist;

  use Adianti\base\AdiantiStandardFormTrait;


  /**
   * Constructor
   */
  public function __construct($param)
  {
    parent::__construct();

    // create form and table container
    $this->form = new BootstrapFormBuilder('my_form_despesa_cartao');
    $this->form->setFormTitle('Novas Despesas do Cartão');
    $this->form->setClientValidation(true);
    $this->form->setColumnClasses(3, ['col-sm-3', 'col-sm-3', 'col-sm-3', 'col-sm-3']);

    $this->setDatabase('sample');
    $this->setActiveRecord('DespesaCartao');


    // Criação de fields
    $id = new TEntry('id');


    $cartao         = new TDBUniqueSearch('id_cartao_credito', 'sample', 'CartoesCredito', 'id', 'id');
    $cartao->addValidation('cartao', new TRequiredValidator);
    $cartao->setChangeAction(new TAction(['DespesaCartaoService', 'onCheckCPF']));

    $anoMes         = new TDBUniqueSearch('anoMes', 'sample', 'AnoMes', 'descricao', 'descricao');
    $anoMes->addValidation('anoMes', new TRequiredValidator);
    $anoMes->setChangeAction(new TAction(['DespesaCartaoService', 'onCheckCPF']));



    if (isset($param['key'])) {
      $cpf         = new TEntry('cpf');
      $cpf->setEditable(false);
    } else {

      $cpf         = new TCombo('cpf');
      $cpf->setChangeAction(new TAction(['DespesaCartaoService', 'onCPFChange']));
    }


    $valor_total = new TEntry('valor_total');


    $this->form->addFields([new TLabel('Codigo')], [$id],[new TLabel('Cartão')], [$cartao]);
    $this->form->addFields([new TLabel('Mês (*)')], [$anoMes], [new TLabel('CPF (*)')], [$cpf]);
    $this->form->addFields([new TLabel('Total')], [$valor_total]);
    $this->form->addContent([new TFormSeparator('Itens')]);

    $id->setEditable(false);
    $id->setSize('100%');

    $cpf->addValidation('cpf', new TRequiredValidator);
    //$cpf->setMask('<b>{cpf}</b>');
    $cpf->setSize('100%');
    $anoMes->setMinLength(0);
    $cartao->setMinLength(0);

    $anoMes->setSize('100%');

    $valor_total->setEditable(false);
    $valor_total->setNumericMask(2, '.', '', false);

    $uniq = new THidden('uniq[]');

    $criteria_event = new TCriteria();
    $criteria_event->setProperty('order', 'id');
    $criteria_event->add(new TFilter('incidencia', 'like', 'D'));
    $evento_id = new TDBCombo('evento_id[]', 'sample', 'Evento', 'id', 'descricao', null, $criteria_event);
    $evento_id->enableSearch();
    $evento_id->setSize('100%');

    $descricao = new TEntry('descricao[]');
    $descricao->setSize('100%');

    $valor = new TEntry('valor[]');
    $valor->setNumericMask(2, '.', '', false);
   
    $valor->setSize('100%');
    $valor->style = 'text-align: right';

   
    $dt_despesa = new TDate('dt_despesa[]');
    $dt_despesa->setMask('dd/mm/yyyy', false);
    //$dt_despesa->setDatabaseMask('yyyy-mm-dd');
    $dt_despesa->setSize('100%');





    $this->fieldlist = new TFieldList;
    $this->fieldlist->generateAria();
    $this->fieldlist->width = '100%';
    $this->fieldlist->name  = 'my_field_list';
    $this->fieldlist->addField('<b>Unniq</b>',  $uniq,   ['width' => '0%', 'uniqid' => true]);
    $this->fieldlist->addField('<b>Data</b>',   $dt_despesa,   ['width' => '15%']);
    $this->fieldlist->addField('<b>C.Custo</b>',  $evento_id,  ['width' => '25%']);
    $this->fieldlist->addField('<b>Descrição</b>',   $descricao,   ['width' => '25%']);
    $this->fieldlist->addField('<b>Valor</b>', $valor, ['width' => '15%', 'sum' => true]);

    // $this->fieldlist->setTotalUpdateAction(new TAction([$this, 'onTotalUpdate']));

    $this->fieldlist->enableSorting();

    $this->form->addField($evento_id);
    $this->form->addField($descricao);
    $this->form->addField($valor);
    $this->form->addField($dt_despesa);

    //$dt_despesa->addValidation('Data', new TRequiredListValidator);
    $evento_id->addValidation('Centro de custo', new TRequiredListValidator);
    $descricao->addValidation('Descrição', new TRequiredListValidator);
    $valor->addValidation('Valor', new TRequiredListValidator);

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
    $bt5b->addFunction("__adianti_load_page('index.php?class=DespesaCartaoList');");
    $this->form->addAction('Save', new TAction([$this, 'onSave'], ['static' => '1']), 'fa:save blue');
    $this->form->addAction('Clear', new TAction([$this, 'onClear']), 'fa:eraser red');
    $this->form->addAction('Mapa', new TAction(['DespesaMap', 'onReload'], ['id' => '{id}']), 'fa:light fa-map green');


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
    // $win = TWindow::create('test', 0.6, 0.8);
    // $win->add('<pre>' . str_replace("\n", '<br>', print_r($param, true)) . '</pre>');
    // $win->show();
    TToast::show('info', 'Linha removida.');
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


      $despesa = new DespesaCartao;
      $despesa->fromArray((array) $data);
      $cartao = CartoesCredito::where('cpf', '=', $data->cpf)->first();



      if (!empty($despesa->id)) {
        ItemDespesaCartao::where('despesa_cartao_id', '=', $despesa->id)->delete();

        $total = 0;

        if (!empty($param['evento_id'])) {
          foreach ($param['evento_id'] as $key => $item_id) {
            //  new TMessage('info', $param['fl_situacao'][$key]); //

            if ($param['dt_despesa'][$key]) {
              $dataOriginal = $param['dt_despesa'][$key];
              $dateTime = DateTime::createFromFormat('d/m/Y', $dataOriginal);
              $dataFormatada = $dateTime->format('Y-m-d');
            } else {
              $dataFormatada = '';
            }
           
            $item = new ItemDespesaCartao;
            $item->despesa_cartao_id   = $despesa->id;
            $item->dt_despesa   = $dataFormatada;
            $item->evento_id   = $param['evento_id'][$key];
            $item->descricao   = $param['descricao'][$key];
            $item->valor      = (float) $param['valor'][$key];

            $item->store();
            $total +=  $item->valor;
          }
        }
        $despesa->valor_total = $total;
        $despesa->store();

        new TMessage('info', 'Alterado com sucesso', $this->afterSaveAction); //$this->afterSaveAction

      } else {
        $despesa->store();

        ItemDespesaCartao::where('despesa_cartao_id', '=', $despesa->id)->delete();

        $total = 0;

        if (!empty($param['evento_id'])) {
          foreach ($param['evento_id'] as $key => $item_id) {


            if ($param['dt_despesa'][$key]) {
              $dataOriginal = $param['dt_despesa'][$key];
              $dateTime = DateTime::createFromFormat('d/m/Y', $dataOriginal);
              $dataFormatada = $dateTime->format('Y-m-d');
            } else {
              $dataFormatada = '';
            }

            $item = new ItemDespesaCartao;
            $item->despesa_cartao_id   = $despesa->id;
            $item->dt_despesa   = $dataFormatada;
            $item->evento_id   = $param['evento_id'][$key];
            $item->descricao   = $param['descricao'][$key];
            $item->valor      = (float) $param['valor'][$key];
            $item->store();
            $total +=  $item->valor;
          }
        }


        $despesa->valor_total = $total;
        $despesa->id_cartao_credito = $cartao->id;
        $despesa->store();

        TForm::sendData('my_form_despesa_cartao', (object) ['id' => $despesa->id]);
        new TMessage('info', 'Registos Salvos', $this->afterSaveAction); //
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

        $object = new DespesaCartao($key);
        $item_despesas = ItemDespesaCartao::where('despesa_cartao_id', '=', $object->id)->orderBy(1)->load();
 
        $this->form->getField('anoMes')->setEditable(false);
        $this->form->getField('id_cartao_credito')->setEditable(false);

        //$this->form->getField('cpf')->setEditable(false);


        $data = new stdClass;
        $data->id_item = [];
        $data->dt_despesa = [];
        $data->evento_id = [];
        $data->descricao = [];
        $data->valor = [];
        $data->saldo = [];


        foreach ($item_despesas as $item) {

          TFieldList::addRows('my_field_list', 1);

          if ($item->dt_despesa) {
            $dt_despesa_formatada = (new DateTime($item->dt_despesa))->format('d/m/Y');
          } else {
            $dt_despesa_formatada = $item->dt_despesa;
          }


          $data->id_item[] = $item->id_item;
          $data->dt_despesa[] = $dt_despesa_formatada;
          $data->evento_id[] = $item->evento_id;
          $data->descricao[] = $item->descricao;
          $data->valor[] = $item->valor;
         

      
          TForm::sendData('my_form_despesa_cartao', $data);
        }

      

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
