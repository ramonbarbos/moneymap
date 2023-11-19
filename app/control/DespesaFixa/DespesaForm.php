<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Control\TWindow;
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
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TAlert;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TToast;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TCheckList;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\Tevento_id;
use Adianti\Widget\Form\Tdt_despesa;
use Adianti\Widget\Form\Tdt_despesaTime;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TFormSeparator;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBevento_id;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class DespesaForm extends TPage
{
  private $form;

  use Adianti\base\AdiantiStandardFormTrait;

  public function __construct()
  {
    parent::__construct();

    parent::setTargetContainer('adianti_right_panel');
    $this->setAfterSaveAction(new TAction(['DespesaList', 'onReload'], ['register_state' => 'true']));

    $this->setDatabase('sample');
    $this->setActiveRecord('Folha');

    // create form and table container
    $this->form = new BootstrapFormBuilder('my_form');
    $this->form->setFormTitle('Novas Despesas');
    $this->form->setClientValidation(true);
    $this->form->setColumnClasses(3, ['col-sm-4', 'col-sm-4', 'col-sm-4']);


    // Criação de fields
    $id = new TEntry('id');
    $cpf         = new TDBUniqueSearch('cpf', 'sample', 'FichaCadastral', 'cpf', 'cpf');
    $anoMes = new TEntry('anoMes');
    $vl_despesa = new TEntry('vl_despesa');


    $this->form->addFields([new TLabel('Codigo')], [$id], [new TLabel('Mês')], [$anoMes]);
    $this->form->addFields([new TLabel('CPF (*)')], [$cpf], [new TLabel('Despesa')], [$vl_despesa]);
    $this->form->addContent([new TFormSeparator('Itens')]);

    $id->setEditable(false);
    $id->setSize('100%');
    $cpf->addValidation('cpf', new TRequiredValidator);
    $cpf->setMinLength(0);
    $cpf->setMask('<b>{cpf}</b> - {nome}');
    $cpf->setSize('100%');
    $anoMes->setEditable(false);
    $vl_despesa->setEditable(false);
    $vl_despesa->setNumericMask(2, '.', '', true);


    $id_item = new THidden('id_item[]');

    $dt_despesa = new TDate('dt_despesa[]');
    $dt_despesa->setSize('100%');

    $evento_id = new TDBCombo('evento_id[]', 'sample', 'Evento', 'id', 'descricao');
    $evento_id->enableSearch();
    $evento_id->setSize('100%');

    $descricao = new TEntry('descricao[]');
    $descricao->setSize('100%');

    $valor = new TEntry('valor[]');
    $valor->setNumericMask(2, ',', '.', true);
    $valor->setSize('100%');
    $valor->style = 'valor-align: right';

    $saldo = new TEntry('saldo[]');
    $saldo->setNumericMask(2, ',', '.', true);
    $saldo->setSize('100%');
    $saldo->style = 'valor-align: right';



    $this->fieldlist = new TFieldList;
    $this->fieldlist->generateAria();
    $this->fieldlist->width = '100%';
    $this->fieldlist->name  = 'my_field_list';
    $this->fieldlist->addField('<b>Unniq</b>',  $id_item,   ['width' => '0%', 'uniqid' => true]);
    $this->fieldlist->addField('<b>Data</b>',   $dt_despesa,   ['width' => '25%']);
    $this->fieldlist->addField('<b>C.Custo</b>', $evento_id,  ['width' => '25%']);
    $this->fieldlist->addField('<b>Descricao</b>', $descricao,  ['width' => '25%']);
    $this->fieldlist->addField('<b>Valor</b>', $saldo, ['width' => '25%', 'sum' => true]);
    $this->fieldlist->addField('<b>Saldo</b>',   $valor,   ['width' => '25%']);

    // $this->fieldlist->setTotalUpdt_despesaAction(new TAction([$this, 'x']));

    $this->fieldlist->enableSorting();

    $this->form->addField($evento_id);
    $this->form->addField($valor);
    $this->form->addField($saldo);
    $this->form->addField($dt_despesa);

    $this->fieldlist->addButtonAction(new TAction([$this, 'showRow']), 'fa:info-circle purple', 'Show valor');
    $this->fieldlist->addButtonFunction("__adianti_message('Row data', JSON.stringify(tfieldlist_get_row_data(this)))", 'fa:info-circle blue', 'Show "valor" field');

    $this->fieldlist->addHeader();
    $this->fieldlist->addDetail(new stdClass);
    $this->fieldlist->addCloneAction();

    // add field list to the form
    $this->form->addContent([$this->fieldlist]);


    // Adicionar botão de salvar
    $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:plus green');
    $btn->class = 'btn btn-sm btn-primary';

    // Adicionar link para criar um novo registro
    $this->form->addActionLink(_t('New'), new TAction([$this, 'onEdit']), 'fa:eraser red');

    // Adicionar link para fechar o formulário
    $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');
    $this->form->addAction('Clear', new TAction([$this, 'onClear']), 'fa:eraser red');
    $this->form->addAction('Fill', new TAction([$this, 'onFill']), 'fas:pencil-alt green');
    $this->form->addAction('Clear/Fill', new TAction([$this, 'onClearFill']), 'fas:pencil-alt orange');

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


      $despesa = new Despesa;
      $despesa->fromArray((array) $data);


      if (!empty($despesa->id)) {
        ItemFolha::where('folha_id', '=', $despesa->id)->delete();

        $total = 0;

        if (!empty($param['eventos_list_evento_id'])) {

          foreach ($param['eventos_list_evento_id'] as $key => $item_id) {
            $item = new ItemFolha;
            $evento = Evento::where('id', '=', $item_id)->first();
            $item->evento_id  = $item_id;
            $item->valor      = (float) $param['eventos_list_valor'][$key];
            $item->folha_id = $despesa->id;
            $item->tipo = $param['eventos_list_tipo'][$key];
            $item->store();
            $total += ($evento->incidencia == 'P') ? $item->valor : 0;
          }
        }

        $despesa->vl_salario = $total;
        $despesa->store();
        new TMessage('info', 'Alterado com sucesso', $this->afterSaveAction); //$this->afterSaveAction

      } else {
        $despesa->store();

        ItemDespesa::where('despesa_id', '=', $despesa->id)->delete();

        $total = 0;

        if (!empty($param['evento_id'])) {
          foreach ($param['evento_id'] as $key => $item_id) {

            if (empty($param['dt_despesa'][$key])) {
              throw new Exception('Todos os campos devem ser preecnhidos!');
            } else {
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
        }


        $despesa->vl_salario = $total;
        $despesa->anoMes = date('Ym');
        $despesa->store();

        TForm::sendData('my_form', (object) ['id' => $despesa->id]);
        new TMessage('info', 'Registos Salvos', $this->afterSaveAction); //$this->afterSaveAction
      }

      TTransaction::close();
    } catch (Exception $e) {
      new TMessage('error', $e->getMessage());
      $this->form->setData($this->form->getData());
      TTransaction::rollback();
    }
  }
  public static function showRow($param)
  {
    new TMessage('info', str_replace(',', '<br>', json_encode($param)));
  }

  /**
   * Clear form
   */
  public static function onClear($param)
  {
    TFieldList::clear('my_field_list');
    TFieldList::addRows('my_field_list', 4);
  }

  /**
   * Fill data
   */
  public static function onFill($param)
  {
  }

  /**
   * Fill data
   */
  public static function onClearFill($param)
  {
  }

  // Método fechar
  public function onClose($param)
  {
    TScript::create("Template.closeRightPanel()");
  }
}
