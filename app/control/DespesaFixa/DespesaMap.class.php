
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
use Adianti\Widget\Base\TElement;
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
use Adianti\Widget\Form\Tdata;
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
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBevento_id;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class DespesaMap  extends TPage
{
    private $datagrid;


    public function __construct()
    {
        parent::__construct();

        // creates one datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        // create the datagrid columns
        $data    = new TDataGridColumn('dt_despesa',  'Data',       'center',  '20%');
        $evento_id   = new TDataGridColumn('evento_id',          'Centro de Custo',      'left',  '20%');
        $descricao  = new TDataGridColumn('descricao',          'Descrição',     'right', '20%');
        $valor   = new TDataGridColumn('valor',          'Valor',      'right', '20%');
        $saldo = new TDataGridColumn('saldo',          'Saldo',    'right', '20%');

     
        $valor->setTransformer(function ($value, $object, $row, $cell, $previous_row) {
           
                return "<span style='color:red'>" . number_format($value, 2, ',', '.') . "</span>";
            
        });

       

     
        // add the columns to the datagrid
        $this->datagrid->addColumn($data);
        $this->datagrid->addColumn($evento_id);
        $this->datagrid->addColumn($descricao);
        $this->datagrid->addColumn($valor);
        $this->datagrid->addColumn($saldo);

        // creates the datagrid model
        $this->datagrid->createModel();

        $panel = new TPanelGroup('Mapa de Despesas');
        $panel->add($this->datagrid);
        $panel->addHeaderActionLink('Save as PDF', new TAction([$this, 'exportAsPDF'], ['register_state' => 'false']), 'far:file-pdf red');
        $panel->addHeaderActionLink('Save as CSV', new TAction([$this, 'exportAsCSV'], ['register_state' => 'false']), 'fa:table blue');

        // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($panel);
        parent::add($vbox);
    }

    /**
     * Export datagrid as PDF
     */
    public function exportAsPDF($param)
    {
        try {

            // string with HTML contents
            $html = clone $this->datagrid;
            $contents = file_get_contents('app/resources/styles-print.html') . $html->getContents();

            // converts the HTML template into PDF
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($contents);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $file = 'app/output/cash-register.pdf';

            // write and open file
            file_put_contents($file, $dompdf->output());

            $window = TWindow::create('Invoice', 0.8, 0.8);
            $object = new TElement('object');
            $object->data  = $file;
            $object->type  = 'application/pdf';
            $object->style = "width: 100%; height:calc(100% - 10px)";
            $object->add('O navegador não suporta a exibição deste conteúdo, <a style="color:#007bff;" target=_newwindow href="' . $object->data . '"> clique aqui para baixar</a>...');

            $window->add($object);
            $window->show();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Export datagrid as CSV
     */
    public function exportAsCSV($param)
    {
        try {
            // get datagrid raw data
            $data = $this->datagrid->getOutputData();

            if ($data) {
                $file    = 'app/output/cash-register.csv';
                $handler = fopen($file, 'w');
                foreach ($data as $row) {
                    fputcsv($handler, $row);
                }

                fclose($handler);
                parent::openFile($file);
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Load the data into the datagrid
     */
    public function onReload($param)
    {
        try {
            TTransaction::open('sample');

            $this->datagrid->clear();

            $itemDespesa =  ItemDespesa::where('id_item','=',@$param['id'])->load();
            
            foreach ($itemDespesa as $row) {
                // add an regular object to the datagrid
                $item = new StdClass;
                $item->dt_despesa = $row->dt_despesa;
                $item->evento_id     = $row->evento_id;
                $item->descricao         = $row->descricao;
                $item->valor         = $row->valor;
                $item->saldo         = $row->saldo;

                $this->datagrid->addItem($item);

            TTransaction::close();

            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage(), $this->afterSaveAction);
            TTransaction::rollback();
        }
    }

    /**
     * shows the page
     */
    public function show()
    {
        $this->onReload([]);
        parent::show();
    }
}
