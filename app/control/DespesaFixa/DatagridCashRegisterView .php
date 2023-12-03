
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
use Adianti\Widget\Form\TCombo;
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

class DatagridCashRegisterView extends TPage
{
    private $datagrid;
    
    public function __construct()
    {
        parent::__construct();
        
        // creates one datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        
        // create the datagrid columns
        $date    = new TDataGridColumn('register_date',  'Date',       'center',  '20%');
        $notes   = new TDataGridColumn('notes',          'Notes',      'left',  '20%');
        $credit  = new TDataGridColumn('value',          'Credit',     'right', '20%');
        $debit   = new TDataGridColumn('value',          'Debit',      'right', '20%');
        $balance = new TDataGridColumn('value',          'Balance',    'right', '20%');
        
        $credit->setTransformer( function($value, $object, $row, $cell, $previous_row) {
            if ($object->operation == 'C') {
                return "<span style='color:blue'>".number_format($value, 2, ',', '.')."</span>";
            }
        });
        
        $debit->setTransformer( function($value, $object, $row, $cell, $previous_row) {
            if ($object->operation == 'D') {
                return "<span style='color:red'>".number_format($value, 2, ',', '.')."</span>";
            }
        });
        
        $balance->setTransformer( function($value, $object, $row, $cell, $previous_object) {
            $previous_balance = (isset($previous_object->balance)) ? $previous_object->balance : 0;
            
            if ($object->operation == 'C') {
                $current_balance = $previous_balance + $value;
            }
            else {
                $current_balance = $previous_balance - $value;
            }
            
            $color = ($current_balance > 0) ? 'blue' : 'red';
            $result = "<span style='color:{$color}'>".number_format($current_balance, 2, ',', '.')."</span>";
            
            $object->balance = $current_balance;
            return $result;
        });
        
        $balance->setTotalFunction( function($totals, $objects) {
            $balance = 0;
            foreach ($objects as $object) {
                $balance += ($object->operation == 'C') ? $object->value : ($object->value *(-1));
            }
            
            $color = ($balance > 0) ? 'blue' : 'red';
            $result = "<span style='color:{$color}'>".number_format($balance, 2, ',', '.')."</span>";
            return $result;
        }, false);
        
        // add the columns to the datagrid
        $this->datagrid->addColumn($date);
        $this->datagrid->addColumn($notes);
        $this->datagrid->addColumn($credit);
        $this->datagrid->addColumn($debit);
        $this->datagrid->addColumn($balance);
        
        // creates the datagrid model
        $this->datagrid->createModel();
        
        $panel = new TPanelGroup( _t('Cash entries') );
        $panel->add( $this->datagrid );
        $panel->addHeaderActionLink( 'Save as PDF', new TAction([$this, 'exportAsPDF'], ['register_state' => 'false']), 'far:file-pdf red' );
        $panel->addHeaderActionLink( 'Save as CSV', new TAction([$this, 'exportAsCSV'], ['register_state' => 'false']), 'fa:table blue' );
        
        // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add( $panel );
        parent::add($vbox);
    }
    
    /**
     * Export datagrid as PDF
     */
    public function exportAsPDF($param)
    {
        try
        {
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
            $object->add('O navegador não suporta a exibição deste conteúdo, <a style="color:#007bff;" target=_newwindow href="'.$object->data.'"> clique aqui para baixar</a>...');
            
            $window->add($object);
            $window->show();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * Export datagrid as CSV
     */
    public function exportAsCSV($param)
    {
        try
        {
            // get datagrid raw data
            $data = $this->datagrid->getOutputData();
            
            if ($data)
            {
                $file    = 'app/output/cash-register.csv';
                $handler = fopen($file, 'w');
                foreach ($data as $row)
                {
                    fputcsv($handler, $row);
                }
                
                fclose($handler);
                parent::openFile($file);
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * Load the data into the datagrid
     */
    public function onReload()
    {
        $this->datagrid->clear();
        
        $data = [];
        $data[] = [ '2019-04-01', 'C', 'Previous balance', 1000];
        $data[] = [ '2019-04-02', 'C', 'Customer payment', 100];
        $data[] = [ '2019-04-02', 'C', 'Customer payment', 100];
        $data[] = [ '2019-04-02', 'C', 'Customer payment', 100];
        $data[] = [ '2019-04-02', 'D', 'Vendor payment', 50];
        $data[] = [ '2019-04-02', 'D', 'Vendor payment', 50];
        $data[] = [ '2019-04-02', 'D', 'Vendor payment', 50];
        $data[] = [ '2019-04-02', 'D', 'Vendor payment', 50];
        
        foreach ($data as $row)
        {
            // add an regular object to the datagrid
            $item = new StdClass;
            $item->register_date = $row[0];
            $item->operation     = $row[1];
            $item->notes         = $row[2];
            $item->value         = $row[3];
            
            $this->datagrid->addItem($item);
        }
    }
    
    /**
     * shows the page
     */
    public function show()
    {
        $this->onReload();
        parent::show();
    }
}