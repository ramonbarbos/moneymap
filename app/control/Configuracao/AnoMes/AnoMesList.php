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
use Adianti\Widget\Util\TDropDown;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class AnoMesList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    private $formgrid;
    private $deleteButton;

    use Adianti\base\AdiantiStandardListTrait;
    
    public function __construct(){

        parent::__construct();


        //Conexão com a tabela
        $this->setDatabase('sample');
        $this->setActiveRecord('AnoMes');
        $this->setDefaultOrder('id', 'asc');
        $this->setLimit(10);

        $this->addFilterField('descricao', 'like', 'descricao');

        //Criação do formulario 
        $this->form = new BootstrapFormBuilder('form_search_anomes');
        $this->form->setFormTitle('Ano Mês');

        //Criação de fields
        $campo1 = new TCombo('descricao');
        //$campo1->addItems(['0' => 'Não', '1' => 'Sim']);

        $this->form->addFields( [new TLabel('Descricão')], [ $campo1 ]  );
        //$this->form->addFields( [new TLabel('Descricão')], [ $campo2 ]  );

        //Tamanho dos fields
        $campo1->setSize('100%');
        //$campo2->setSize('100%');

        $this->form->setData( TSession::getValue( __CLASS__.'_filter_data') );

        //Adicionar field de busca
        $btn = $this->form->addAction(_t('Find'), new TAction([$this, 'onSearch']), 'fa:search');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('New'), new TAction(['AnoMesForm', 'onEdit'], ['register_state' => 'false']), 'fa:plus green'  );

        //Criando a data grid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        //Criando colunas da datagrid
        $column_1 = new TDataGridColumn('id', 'Codigo', 'left');
        $column_2 = new TDataGridColumn('descricao', 'Descricão', 'left', );

        //add coluna da datagrid
        $this->datagrid->addColumn($column_1);
        $this->datagrid->addColumn($column_2);
 

        //Criando ações para o datagrid
        $column_1->setAction(new TAction([$this, 'onReload']), ['order'=> 'id']);
        $column_2->setAction(new TAction([$this, 'onReload']), ['order'=> 'descricao']);

        $action1 = new TDataGridAction(['AnoMesForm', 'onEdit'], ['id'=> '{id}', 'register_state' => 'false']);
        $action2 = new TDataGridAction([ $this, 'onDelete'], ['id'=> '{id}']);

        //Adicionando a ação na tela
        $this->datagrid->addAction($action1, _t('Edit'), 'fa:edit blue' );
        $this->datagrid->addAction($action2, _t('Delete'), 'fa:trash-alt red' );


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
          $drodown->addAction('Salvar como CSV', new TAction([$this, 'onExportCSV'], ['register_state' => 'false', 'static'=>'1']), 'fa:table green');
          $drodown->addAction('Salvar como PDF', new TAction([$this, 'onExportPDF'], ['register_state' => 'false',  'static'=>'1']), 'fa:file-pdf red');
          $panel->addHeaderWidget( $drodown);

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
    
                $anomes = new AnoMes($id);
                $folhas = Folha::where('anoMes', 'like', $anomes->descricao)->load();
    
                if (count($folhas) > 0) {
                    new TMessage('warning', 'Não é possível excluir. Vínculo com Folha!');
                } else {
                    $anomes->delete();
                    new TMessage('info', 'Registro excluído', $this->afterSaveAction);
                    $this->onReload([]);
                }
            }
    
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage(), $this->afterSaveAction);
            TTransaction::rollback();
        }
    }

  
}