<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TActionLink;
use Adianti\Widget\Util\TDropDown;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class DespesaList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    private $formgrid;
    private $deleteButton;

    use Adianti\base\AdiantiStandardListTrait;

    public function __construct()
    {

        parent::__construct();


        //Conexão com a tabela
        $this->setDatabase('sample');
        $this->setActiveRecord('Despesa');
        $this->setDefaultOrder('id', 'asc');
        $this->setLimit(10);

        $this->addFilterField('cpf', 'like', 'cpf');
        $this->addFilterField('anoMes', 'like', 'anoMes');
        $this->addFilterField('tp_folha', '=', 'tp_folha');


        //Criação do formulario 
        $this->form = new BootstrapFormBuilder('form_search_despesa');
        $this->form->setFormTitle('Despesa');

        //Criação de fields
        $campo1 =  new TDBUniqueSearch('cpf', 'sample', 'FichaCadastral', 'cpf', 'cpf');
        $campo2         = new TDBUniqueSearch('anoMes', 'sample', 'AnoMes', 'descricao', 'descricao');

        $this->form->addFields([new TLabel('CPF')], [$campo1]);
        $this->form->addFields([new TLabel('Ano Mes')], [$campo2]);


        //Tamanho dos fields
        $campo1->setSize('100%');
        $campo1->setMinLength(0);
        $campo2->setMinLength(0);

        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        //Adicionar field de busca
        $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';
        // $this->form->addActionLink(_t('New'), new TAction(['DespesaForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus green'  );

        $this->form->addActionLink(_t('New'), new TAction(['DespesaView', 'onEdit'], ['register_state' => 'false']), 'fa:plus green');



        //Criando a data grid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        //Criando colunas da datagrid
        $column_1 = new TDataGridColumn('id', 'Codigo', 'left');
        $column_2 = new TDataGridColumn('cpf', 'CPF', 'left',);
        $column_3 = new TDataGridColumn('folha->descricao', 'Tipo', 'left',);
        $column_4 = new TDataGridColumn('anoMes', 'Mês', 'left',);
        $column_5 = new TDataGridColumn('vl_despesa', 'Despesas', 'left',);
        $column_6 = new TDataGridColumn('saldo', 'Saldo', 'left',);
        $column_7 = new TDataGridColumn('situacao', 'Situação', 'left',);

        $formato_vl_despesa = function ($value) {
            if (is_numeric($value)) {
                return 'R$ ' . number_format($value, 2, ',', '.');
            }
            return $value;
        };
        $column_5->setTransformer($formato_vl_despesa);

        $formato_vl_saldo = function ($value) {
            if (is_numeric($value)) {
                return 'R$ ' . number_format($value, 2, ',', '.');
            }
            return $value;
        };
        $column_6->setTransformer($formato_vl_saldo);

        $column_4->setTransformer(function ($value, $object, $row) {
            // Verifica se os dois últimos caracteres da string são '01'
            if (substr($value, -2) === '01') {
              return "<span style='color:black'>JANEIRO</span>";
            } else if (substr($value, -2) === '02') {
              return "<span style='color:black'>FERVEREIRO</span>";
            } else if (substr($value, -2) === '03') {
              return "<span style='color:black'>MARÇO</span>";
            } else if (substr($value, -2) === '04') {
              return "<span style='color:black'>ABRIL</span>";
            }else if (substr($value, -2) === '05') {
              return "<span style='color:black'>MAIO</span>";
            }else if (substr($value, -2) === '06') {
              return "<span style='color:black'>JUNHO</span>";
            }else if (substr($value, -2) === '07') {
              return "<span style='color:black'>JULHO</span>";
            }else if (substr($value, -2) === '08') {
              return "<span style='color:black'>AGOSTO</span>";
            }else if (substr($value, -2) === '09') {
              return "<span style='color:black'>SETEMBRO</span>";
            }else if (substr($value, -2) === '10') {
              return "<span style='color:black'>OUTUBRO</span>";
            }else if (substr($value, -2) === '11') {
              return "<span style='color:black'>NOVEMBRO</span>";
            }else if (substr($value, -2) === '12') {
              return "<span style='color:black'>DEZEMBRO</span>";
            }
          });

          $column_7->setTransformer(function ($value, $object, $row) {
            // Verifica se os dois últimos caracteres da string são '01'
            if ($value == 1) {
              return "<span style='color:black'>Bloquado</span>";
            } else    {
              return "<span style='color:black'>Desbloqueado</span>";
            }
          });
        //add coluna da datagrid
        //$this->datagrid->addColumn($column_1);
        $this->datagrid->addColumn($column_2);
        $this->datagrid->addColumn($column_3);
        $this->datagrid->addColumn($column_4);
        $this->datagrid->addColumn($column_5);
        $this->datagrid->addColumn($column_6);
        $this->datagrid->addColumn($column_7);

        //Criando ações para o datagrid
        $column_1->setAction(new TAction([$this, 'onReload']), ['order' => 'id']);
        $column_2->setAction(new TAction([$this, 'onReload']), ['order' => 'cpf']);

        $action1 = new TDataGridAction(['DespesaView', 'onEdit'], ['id' => '{id}','situacao' => '{situacao}', 'register_state' => 'false']);
        $action2 = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);
        $action3 = new TDataGridAction(['DespesaMap', 'onReload'], ['id' => '{id}']);

        //Adicionando a ação na tela
        $this->datagrid->addAction($action1, _t('Edit'), 'fa:edit blue');
        $this->datagrid->addAction($action2, _t('Delete'), 'fa:trash-alt red');
        $this->datagrid->addAction($action3, 'Mapa', 'fa:light fa-map green');


        //Criar datagrid 
        $this->datagrid->createModel();

        //Criação de paginador
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));



        //Enviar para tela
        $panel = new TPanelGroup('', 'white');
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);

        //Exportar
        $drodown = new TDropDown('Exportar', 'fa:list');
        $drodown->setPullSide('right');
        $drodown->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $drodown->addAction('Salvar como CSV', new TAction([$this, 'onExportCSV'], ['register_state' => 'false', 'static' => '1']), 'fa:table green');
        $drodown->addAction('Salvar como PDF', new TAction([$this, 'onExportPDF'], ['register_state' => 'false',  'static' => '1']), 'fa:file-pdf red');
        $panel->addHeaderWidget($drodown);

        //Vertical container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);

        parent::add($container);
    }

    public function onDelete($param)
    {
        try {
            if (isset($param['key'])) {

                $id = $param['key'];


                // Exiba uma mensagem de confirmação antes de excluir
                $action = new TAction(array($this, 'delete'));
                $action->setParameter('id', $id);

                new TQuestion('Deseja excluir?', $action);
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage(), $this->afterSaveAction);
            TTransaction::rollback();
        }
    }


    public function delete($param)
    {
        try {
            TTransaction::open('sample');

            if (isset($param['id'])) {
                $id = $param['id'];

                $despesa = new Despesa($id);
                ItemDespesa::where('despesa_id', '=', $despesa->id)->delete();
                $despesa->delete();
                new TMessage('info', 'Registo Excluido', $this->afterSaveAction); //$this->afterSaveAction
                $this->onReload([]);
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage(), $this->afterSaveAction);
            TTransaction::rollback();
        }
    }
}
