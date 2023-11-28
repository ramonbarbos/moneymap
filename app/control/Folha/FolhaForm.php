<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
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
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class FolhaForm extends TPage
{
  private $form;
  private $eventos_list;
  private $loaded;

  use Adianti\base\AdiantiStandardFormTrait;

  public function __construct($param)
  {
    parent::__construct();

    parent::setTargetContainer('adianti_right_panel');
    $this->setAfterSaveAction(new TAction(['FolhaList', 'onReload'], ['register_state' => 'true']));

    $this->setDatabase('sample');
    $this->setActiveRecord('Folha');


    // Criação do formulário
    $this->form = new BootstrapFormBuilder('form_folha');
    $this->form->setFormTitle('Nova Folha');
    $this->form->setClientValidation(true);
    $this->form->setColumnClasses(3, ['col-sm-4', 'col-sm-4', 'col-sm-4']);



    // Criação de fields
    $id = new TEntry('id');
    $criteria_cpf = new TCriteria();
    $criteria_cpf->setProperty('order', 'id');
    //$criteria_cpf->add(new TFilter('cpf', '<>', cpf));
    // $cpf         = new TCombo('cpf');
    $cpf         = new TDBUniqueSearch('cpf', 'sample', 'FichaCadastral', 'cpf', 'cpf');
    $cpf->setChangeAction(new TAction(array('FolhaService', 'onCheckCPF')));


    if (isset($param['key'])) {
      $anoMes         = new TEntry('anoMes[]');
      $anoMes->setEditable(false);
    } else {
      // $dados_param = array('key'=>$param['key']);
      // $anoMes         = new TDBUniqueSearch('anoMes', 'sample', 'AnoMes', 'descricao', 'descricao');
      $anoMes         = new TCombo('anoMes');
    }


    $vl_salario = new TEntry('vl_salario');
    $vl_desconto = new TEntry('vl_desconto');

    $uniqid      = new THidden('uniqid');
    $detail_id         = new THidden('detail_id');
    $criteria_event = new TCriteria();
    $criteria_event->setProperty('order', 'id');
    //$criteria_event->add(new TFilter('incidencia', '=', 1));
    $evento_id = new TDBUniqueSearch('evento_id', 'sample', 'Evento', 'id', 'id', null, $criteria_event);
    $valor      = new TEntry('valor');
    $tipo      = new TEntry('tipo');
    $evento_descricao     = new TEntry('evento_descricao');
    $formula = new TEntry('formula');
    $formula_action = new TAction(array('FolhaService', 'onFormula'));
    $formula->setExitAction($formula_action);
    $parcela      = new TEntry('parcela');



    $this->form->addFields([new TLabel('Codigo')], [$id],);
    $this->form->addFields([new TLabel('CPF (*)')], [$cpf], [new TLabel('Mês (*)')], [$anoMes]);
    $this->form->addFields([new TLabel('Salario')], [$vl_salario], [new TLabel('Desconto')], [$vl_desconto],);
    $this->form->addContent([new TFormSeparator('Eventos')]);

    $id->setEditable(false);
    $id->setSize('100%');
    $cpf->addValidation('cpf', new TRequiredValidator);
    $cpf->setMinLength(0);
    $cpf->setMask('<b>{cpf}</b> - {nome}');
    $cpf->setSize('100%');
    //$cpf->setMask('Selecione');
    $anoMes->setSize('100%');
    $anoMes->addValidation('anoMes', new TRequiredValidator);
    //$anoMes->setMinLength(0);
    //$anoMes->setDefaultOption('Selecione');

    $vl_salario->setEditable(false);
    $vl_salario->setNumericMask(2, '.', '', true);
    $vl_desconto->setEditable(false);
    $vl_desconto->setNumericMask(2, '.', '', true);


    $evento_id->setChangeAction(new TAction([$this, 'onEventChange']));

    $this->form->addFields([$uniqid], [$detail_id],);
    $this->form->addFields([new TLabel('Evento (*)')], [$evento_id], [new TLabel('Descrição')], [$evento_descricao],);
    $this->form->addFields([new TLabel('Tipo')], [$tipo], [new TLabel('Valor (*)')], [$valor]);
    $this->form->addFields([new TLabel('Formula')], [$formula],[new TLabel('Parcela')], [$parcela],);
    $add_event = TButton::create('add_event', [$this, 'onEventAdd'], 'Registrar', 'fa:plus-circle green');
    $add_event->getAction()->setParameter('static', '1');
    $this->form->addFields([], [$add_event]);


    $evento_id->setMinLength(0);
    $evento_id->setSize('100%');
    $evento_id->setMask('{id} - {descricao}');
    $evento_descricao->setEditable(false);
    $tipo->setEditable(false);
    $valor->setSize('100%');
    $valor->setNumericMask(4, '.', '', true);
    $formula->setSize('100%');
    $formula->setEditable(false);



    $this->eventos_list = new BootstrapDatagridWrapper(new TDataGrid);
    $this->eventos_list->setHeight(150);
    $this->eventos_list->makeScrollable();
    $this->eventos_list->setId('eventos_list');
    $this->eventos_list->generateHiddenFields();
    $this->eventos_list->style = "min-width: 700px; width:100%;margin-bottom: 10px";
    $this->eventos_list->setMutationAction(new TAction([$this, 'onMutationAction']));

    $col_uniq   = new TDataGridColumn('uniqid', 'Uniqid', 'center', '10%');
    $col_id     = new TDataGridColumn('id', 'ID', 'center', '10%');
    $col_evento  = new TDataGridColumn('evento_id', 'Evento', 'center', '10%');
    $col_desc = new TDataGridColumn('evento_id', 'Descrição', 'left', '30%');
    $col_tipo = new TDataGridColumn('tipo', 'Tipo', 'left', '30%');
    $col_parcela = new TDataGridColumn('parcela', 'Parcela', 'left', '15%%');
    $col_valor  = new TDataGridColumn('valor', 'Valor', 'right', '15%');

    $this->eventos_list->addColumn($col_uniq);
    $this->eventos_list->addColumn($col_id);
    $this->eventos_list->addColumn($col_evento);
    $this->eventos_list->addColumn($col_desc);
    $this->eventos_list->addColumn($col_tipo);
    $this->eventos_list->addColumn($col_parcela);
    $this->eventos_list->addColumn($col_valor);

    $col_desc->setTransformer(function ($value) {
      return Evento::findInTransaction('sample', $value)->descricao;
    });
    $format_tipo = function ($value, $object, $row) {
      if ($value == 'P') {
        return "<span style='color:green'>Provento</span>";
      } else {
        return "<span style='color:red'>Desconto</span>";
      }
    };

    $col_tipo->setTransformer($format_tipo);


    $col_id->setVisibility(false);
    $col_uniq->setVisibility(false);

    // creates two datagrid actions
    $action1 = new TDataGridAction([$this, 'onEditItemProduto']);
    $action1->setFields(['uniqid', '*']);

    $action2 = new TDataGridAction([$this, 'onDeleteItem']);
    $action2->setField('uniqid');

    // add the actions to the datagrid
    $this->eventos_list->addAction($action1, _t('Edit'), 'far:edit blue');
    $this->eventos_list->addAction($action2, _t('Delete'), 'far:trash-alt red');

    $format_value = function ($value) {
      if (is_numeric($value)) {
        return 'R$ ' . number_format($value, 2, ',', '.');
      }
      return $value;
    };

    $col_valor->setTransformer($format_value);

    $this->eventos_list->createModel();

    $panel = new TPanelGroup();
    $panel->add($this->eventos_list);
    $panel->getBody()->style = 'overflow-x:auto';
    $this->form->addContent([$panel]);



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

  
  public static function onEventChange($params)
  {
    if (!empty($params['evento_id'])) {
      try {
        TTransaction::open('sample');
        $eventos   = new Evento($params['evento_id']);

        // Certifique-se de que os campos não são nulos
        $descricao = $eventos->descricao ?? '';
        $incidencia = $eventos->incidencia ?? '';
        $formula = $eventos->formula ?? '';



        // Consolidar as atualizações em uma única chamada
        TForm::sendData('form_folha', (object) [
          'evento_descricao' => $descricao,
          'tipo' => $incidencia,
          'formula' => $formula
        ]);
        TTransaction::close();
      } catch (Exception $e) {
        new TMessage('error', $e->getMessage());
        TTransaction::rollback();
      }
    }
  }
  public function onSave($param)
  {
    try {
      TTransaction::open('sample');

      $data = $this->form->getData();
      $this->form->validate();


      $folha = new Folha;
      $folha->fromArray((array) $data);

      if (empty($param['eventos_list_evento_id'])) {
        throw new Exception('É necessário informar pelo menos um item na folha.');
      }
      if ($this->hasNegativeValues($param['eventos_list_valor'])) {
        throw new Exception('Não é permitido inserir valores negativos.');
      }
      // Verificar se há pelo menos um ItemFolha informado

      if (!empty($folha->id)) {
        ItemFolha::where('folha_id', '=', $folha->id)->delete();

        $total = 0;

        if (!empty($param['eventos_list_evento_id'])) {

          foreach ($param['eventos_list_evento_id'] as $key => $item_id) {
            $item = new ItemFolha;
            $evento = Evento::where('id', '=', $item_id)->first();
            $item->evento_id  = $item_id;
            $item->valor      = (float) $param['eventos_list_valor'][$key];
            $item->folha_id = $folha->id;
            $item->tipo = $param['eventos_list_tipo'][$key];
            $item->parcela = $param['eventos_list_parcela'][$key];
            $item->store();
            $total += ($evento->incidencia == 'P') ? $item->valor : 0;
          }
        }

        $folha->vl_salario = $total;
        $folha->store();
        new TMessage('info', 'Alterado com sucesso', $this->afterSaveAction); //$this->afterSaveAction

      } else {
        $folha->store();

        ItemFolha::where('folha_id', '=', $folha->id)->delete();

        $total = 0;

        if (!empty($param['eventos_list_evento_id'])) {

          foreach ($param['eventos_list_evento_id'] as $key => $item_id) {
            $item = new ItemFolha;
            $evento = Evento::where('id', '=', $item_id)->first();
            $item->evento_id  = $item_id;
            $item->valor      = (float) $param['eventos_list_valor'][$key];
            $item->folha_id = $folha->id;
            $item->tipo = $param['eventos_list_tipo'][$key];
            $item->parcela = $param['eventos_list_parcela'][$key];

            $item->store();
            $total += ($evento->incidencia == 'P') ? $item->valor : 0;
          }
        }
        $folha->vl_salario = $total;
        $folha->store();

        TForm::sendData('form_folha', (object) ['id' => $folha->id]);
        new TMessage('info', 'Registos Salvos', $this->afterSaveAction); //$this->afterSaveAction
      }

      TTransaction::close();
    } catch (Exception $e) {
      new TMessage('error', $e->getMessage());
      $this->form->setData($this->form->getData());
      TTransaction::rollback();
    }
  }

  private function hasNegativeValues($array)
  {
    foreach ($array as $value) {
      if ((float) $value < 0) {
        return true;
      }
    }
    return false;
  }

  public function onEdit($param)
  {
    try {
      TTransaction::open('sample');


      if (isset($param['key'])) {
        $key = $param['key'];

        $object = new Folha($key);
        $item_folhas = ItemFolha::where('folha_id', '=', $object->id)->orderBy(1)->load();
        $this->form->getField('cpf')->setEditable(false);
        //$this->form->getField('anoMes')->setEditable(false);

        foreach ($item_folhas as $item) {
          $item->uniqid = uniqid();
          $row = $this->eventos_list->addItem($item);
          $row->id = $item->uniqid;
        }
        $this->form->setData($object);

        if (empty($param['id'])) {
          $mes = $object->anoMes;
          $anoMes = AnoMes::where('id', '=', $mes)
            ->load();
          $options = array();
          if ($anoMes) {
            foreach ($anoMes as $item) {
              $options[$item->descricao] = $item->descricao;
            }
          }
          TCombo::reload('form_folha', 'anoMes', $options);
          $dataF = new stdClass;
          $dataF->anoMes = $mes;
          TForm::sendData('form_folha', (object) $dataF);
        }




        TTransaction::close();
      } else {
        $this->form->clear();
      }
    } catch (Exception $e) {
      new TMessage('error', $e->getMessage());
      TTransaction::rollback();
    }
  }

  public function onEventAdd($param)
  {
    try {
      $this->form->validate();
      $data = $this->form->getData();

      if ((!$data->evento_id) || (!$data->evento_descricao) || (!$data->valor)) {
        throw new Exception('Para incluir é necessario informar o evento.');
      }

      $uniqid = !empty($data->uniqid) ? $data->uniqid : uniqid();

      $grid_data = [
        'uniqid'      => $uniqid,
        'id'          => $data->detail_id,
        'evento_id'  => $data->evento_id,
        'evento_descricao'      => $data->evento_descricao,
        'tipo'      => $data->tipo,
        'parcela'      => $data->parcela,
        'valor'  => $data->valor,

      ];

      // insert row dynamically
      $row = $this->eventos_list->addItem((object) $grid_data);
      $row->id = $uniqid;

      TDataGrid::replaceRowById('eventos_list', $uniqid, $row);


      // clear product form fields after add
      $data->uniqid     = '';
      $data->detail_id         = '';
      $data->evento_id = '';
      $data->evento_descricao       = '';
      $data->tipo       = '';
      $data->parcela       = '';
      $data->valor     = '';
      $data->formula     = '';



      TForm::sendData('form_folha', $data, false, false);



      TTransaction::close();
    } catch (Exception $e) {
      $this->form->setData($this->form->getData());
      new TMessage('error', $e->getMessage());
    }
  }

  public static function onEditItemProduto($param)
  {
    $data = new stdClass;
    $data->uniqid     = $param['uniqid'];
    $data->detail_id         = $param['id'];
    $data->evento_id = $param['evento_id'];
    $data->evento_descricao       = $param['evento_id'];
    $data->tipo       = $param['tipo'];
    @$data->parcela       = $param['parcela'];
    $data->valor     = $param['valor'];


    TForm::sendData('form_folha', $data, false, false);
  }


  public static function onDeleteItem($param)
  {
    $data = new stdClass;
    $data->uniqid     = '';
    $data->detail_id         = '';
    $data->evento_id = '';
    $data->evento_descricao       = '';
    $data->tipo       = '';
    $data->parcela       = '';
    $data->valor     = '';
    $data->formula     = '';

    // send data, do not fire change/exit events
    TForm::sendData('form_folha', $data, false, false);

    // remove row
    TDataGrid::removeRowById('eventos_list', $param['uniqid']);
  }
  public static function onMutationAction($param)
  {

    try {
      TTransaction::open('sample');

      $totalP = 0;
      $totalD = 0;

      if (@$param['list_data']) {
        foreach (@$param['list_data'] as $row) {
          $evento = Evento::where('id', '=', $row['evento_id'])->first();

          $totalP += ($evento->incidencia == 'P') ? floatval($row['valor']) : 0;
          $totalD += ($evento->incidencia == 'D') ? floatval($row['valor']) : 0;
        }
      } else {
      }


      TForm::sendData('form_folha', (object) ['vl_salario' => $totalP]);
      TForm::sendData('form_folha', (object) ['vl_desconto' => $totalD]);
      TToast::show('info', 'Total: <b>' . 'R$ ' . number_format($totalP, 2, ',', '.') . '</b>', 'bottom right');
    } catch (Exception $e) {
      new TMessage('error', $e->getMessage());
      TTransaction::rollback();
    }
  }
  // Método fechar
  public function onClose($param)
  {
    TScript::create("Template.closeRightPanel()");
  }



}
