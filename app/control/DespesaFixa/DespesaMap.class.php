
<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Control\TWindow;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TSqlSelect;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
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
    private $pdf;

    use Adianti\base\AdiantiStandardListTrait;
    public function __construct()
    {
        parent::__construct();



        // creates one datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        // create the datagrid columns
        $fl_situacao    = new TDataGridColumn('fl_situacao',  'Situação',       'center',  '20%');
        $data    = new TDataGridColumn('dt_despesa',  'Data',       'center',  '20%');
        $evento_id   = new TDataGridColumn('evento_id',          'Centro de Custo',      'center',  '20%');
        $descricao  = new TDataGridColumn('descricao',          'Descrição',     'center', '20%');
        $valor   = new TDataGridColumn('valor',          'Valor',      'center', '20%');
        $saldo = new TDataGridColumn('saldo',          'Saldo',    'center', '20%');


        $valor->setTransformer(function ($value, $object, $row, $cell, $previous_row) {

            return "<span style='color:red'>" . number_format($value, 2, ',', '.') . "</span>";
        });

        $fl_situacao->setTransformer(function ($value, $object, $row) {
            if ($value == 1) {
              return  "<span style='color:green'>Pago</span>";
            } else if ($value == 0) {
              return "<span style='color:blue'>Pendente</span>";
            }
          });




        // add the columns to the datagrid
        $this->datagrid->addColumn($fl_situacao);
        $this->datagrid->addColumn($data);
        $this->datagrid->addColumn($evento_id);
        $this->datagrid->addColumn($descricao);
        $this->datagrid->addColumn($valor);
        $this->datagrid->addColumn($saldo);

        // creates the datagrid model
        $this->datagrid->createModel();

        $bt5b = new TButton('Voltar');
        $bt5b->class = 'btn ';
        $bt5b->style = 'background-color: grey; color: white';

        $bt5b->setLabel('Voltar');
        $bt5b->addFunction("__adianti_load_page('index.php?class=DespesaList');");

        $panel = new TPanelGroup('Mapa de Despesas');
        $panel->add($this->datagrid);
        $panel->addHeaderActionLink('Save as PDF', new TAction([$this, 'exportAsPDF'], ['static' => 1]), 'far:file-pdf red');
        $panel->addHeaderActionLink('Save as CSV', new TAction([$this, 'onExportCSV'], ['static' => 1]), 'fa:table blue');
        // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($panel);
        $vbox->add($bt5b);

        parent::add($vbox);
    }

    /**
     * Export datagrid as PDF
     */
    public function exportAsPDF($param)
    {
        try {
            TTransaction::open('sample');

            $id = TSession::getValue('id_despesa');

            $despesa = new Despesa($id);


            $this->pdf = new FPDF('P', 'pt');
            $this->pdf->SetMargins(2, 2, 2); // define margins
            $this->pdf->AddPage();
            $this->pdf->Ln();
            $this->pdf->Image('app/images/logo_vs1.png', 25, 25, 100);
            $this->pdf->SetLineWidth(1);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFont('Arial', 'B', 10);

            $this->addCabecalhoNota($despesa->cpf, $despesa->tp_folha, $despesa->anoMes, $despesa->vl_despesa, $despesa->saldo);
            $this->addCabecalhoProduto();

            if ($id) {
                $itemDespesa = ItemDespesa::where('despesa_id', '=', $id)->orderby(1)->load();
                foreach ($itemDespesa as $index =>  $item) {
                    $this->AddEvento($item);
                }
            }
            $this->addRodapeFolha();
            $this->addRodapeNota();

            $file = 'app/output/mapa-despesa.pdf';


            if (!file_exists($file) or is_writable($file)) {
                $this->pdf->Output($file);

                $window = TWindow::create('Resumo da Folha', 0.8, 0.8);
                $object = new TElement('object');
                $object->data  = $file;
                $object->type  = 'application/pdf';
                $object->style = "width: 100%; height:calc(100% - 10px)";
                $object->add('O navegador não suporta a exibição deste conteúdo, <a style="color:#007bff;" target=_newwindow href="' . $object->data . '"> clique aqui para baixar</a>...');

                $window->add($object);
                $window->show();
            } else {
                throw new Exception(_t('Permission denied') . ': ' . $file);
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }



    public static function onExportCSV($param)
    {
        try
        {
            TTransaction::open('sample');
    
            $id = TSession::getValue('id_despesa');
    
            $table = 'item_despesa';
    
            if (!is_writable('tmp')) {
                throw new Exception(_t('Permission denied') . ': tmp');
            }
    
            $result = ItemDespesa::where('despesa_id', '=', $id)->load();
    
            $file = 'tmp/' . $table . '.csv';
            $handler = fopen($file, 'w');
            $first_row = $result[0];
            if ($first_row) {
                // CSV headers
                fputcsv($handler, array_keys($first_row->toArray()));
    
                // Adicionar todas as linhas
                foreach ($result as $row) {
                    fputcsv($handler, $row->toArray());
                }
    
                fclose($handler);
                parent::openFile($file);
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
   
    public function onReload($param)
    {
        try {
            TTransaction::open('sample');

            //$this->datagrid->clear();

            TSession::setValue('id_despesa', @$param['id']);

            $itemDespesa =  ItemDespesa::where('despesa_id', '=', @$param['id'])->orderby(1)->load();



            foreach ($itemDespesa as $row) {
                $evento = new Evento($row->evento_id);
                // add an regular object to the datagrid
                if ($row->dt_despesa) {
                    $dt_despesa_formatada = (new DateTime($row->dt_despesa))->format('d/m/Y');
                } else {
                    $dt_despesa_formatada = $row->dt_despesa;
                }

                $item = new StdClass;
                $item->fl_situacao     = $row->fl_situacao;
                $item->dt_despesa = $dt_despesa_formatada;
                $item->evento_id     = $evento->descricao;
                $item->descricao         = $row->descricao;
                $item->valor         = $row->valor;
                $item->saldo         = $row->saldo;

                $this->datagrid->addItem($item);
            }
            return $itemDespesa;
            TTransaction::close();
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

    public function addCabecalhoNota($cpf, $tp_folha, $anoMes, $vl_salario, $vl_despesas)
    {
        $tipoFolha = new TipoFolha($tp_folha);

        $this->pdf->SetY(80);

        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->SetTextColor(100, 100, 100);
        $this->pdf->SetX(20);
        $this->pdf->Cell(150, 12, mb_convert_encoding('CPF: ',"ISO-8859-1","UTF-8"), 'LTR', 0, 'L');
        $this->pdf->Cell(100, 12, mb_convert_encoding('Tipo de Folha: ',"ISO-8859-1","UTF-8"), 'LTR', 0, 'L');
        $this->pdf->Cell(100, 12, mb_convert_encoding('Ano Mês: ',"ISO-8859-1","UTF-8"), 'LTR', 0, 'L');
        $this->pdf->Cell(100, 12, mb_convert_encoding('Despesas: ',"ISO-8859-1","UTF-8"), 'LTR', 0, 'L');
        $this->pdf->Cell(100, 12, mb_convert_encoding('Saldo: ',"ISO-8859-1","UTF-8"), 'LTR', 0, 'L');

        $this->pdf->Ln(8);

        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetX(20);
        $this->pdf->Cell(150, 16, $cpf, 'LBR', 0, 'L');
        $this->pdf->Cell(100,  16, $tipoFolha->descricao, 'LBR', 0, 'L');
        $this->pdf->Cell(100, 16, $anoMes, 'LBR', 0, 'L');

        if (empty($vl_salario)) {
            $this->pdf->Cell(100, 16, 'R$ 0,00', 'LBR', 0, 'L');
        } else {
            $this->pdf->Cell(100, 16, 'R$ ' . $vl_salario, 'LBR', 0, 'L');
        }

        if (empty($vl_despesas)) {
            $this->pdf->Cell(100, 16, 'R$ 0,00', 'LBR', 0, 'L');
        } else {
            $this->pdf->Cell(100, 16, 'R$ ' . $vl_despesas, 'LBR', 0, 'L');
        }

        $this->pdf->Ln(16);
    }

    public function addCabecalhoProduto()
    {
        $this->pdf->SetY(140);

        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetX(20);
        $this->pdf->Cell(300, 12, 'ITENS DA DESPESA: ', 0, 0, 'L');

        $this->pdf->Ln(12);
        $this->pdf->SetX(20);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(65,  12, mb_convert_encoding('Situação',"ISO-8859-1","UTF-8"),     1, 0, 'C', 1);
        $this->pdf->Cell(65,  12, mb_convert_encoding('Data',"ISO-8859-1","UTF-8"),     1, 0, 'C', 1);
        $this->pdf->Cell(135, 12, mb_convert_encoding('Centro de Custo',"ISO-8859-1","UTF-8"),  1, 0, 'C', 1);
        $this->pdf->Cell(155,  12, mb_convert_encoding('Descrição',"ISO-8859-1","UTF-8"), 1, 0, 'C', 1);
        $this->pdf->Cell(65,  12, mb_convert_encoding('Valor',"ISO-8859-1","UTF-8"),      1, 0, 'C', 1);
        $this->pdf->Cell(65,  12, mb_convert_encoding('Saldo',"ISO-8859-1","UTF-8"),      1, 0, 'C', 1);
    }
    public function AddEvento($item)
    {
        TTransaction::open('sample');
        $evento = new Evento($item->evento_id);

        if ($item->dt_despesa) {
            $dt_despesa_formatada = (new DateTime($item->dt_despesa))->format('d/m/Y');
        } else {
            $dt_despesa_formatada = $item->dt_despesa;
        }

        if ($item->fl_situacao == 1) {
            $fl_situacao = 'Pago';
          } else if ($item->fl_situacao == 0) {
            $fl_situacao = 'Pendente';

          }

        $this->pdf->Ln(12);
        $this->pdf->SetX(20);
        $this->pdf->SetFillColor(230, 230, 230);

        $this->pdf->Cell(65,  12,  $fl_situacao, 'LR', 0, 'C');
        $this->pdf->Cell(65,  12,   $dt_despesa_formatada, 'LR', 0, 'C');
        $this->pdf->Cell(135, 12, mb_convert_encoding($evento->descricao,"ISO-8859-1","UTF-8"), 'LR', 0, 'C');
        $this->pdf->Cell(155,  12,   mb_convert_encoding($item->descricao,"ISO-8859-1","UTF-8"), 'LR', 0, 'C');
        $this->pdf->Cell(65,  12, 'R$ ' . number_format($item->valor, 2), 'LR', 0, 'C');
        $this->pdf->Cell(65,  12, 'R$ ' . number_format($item->saldo, 2), 'LR', 0, 'C');

        $this->count_produtos++;
        TTransaction::close();

    }
    public function addRodapeFolha()
    {
        if ($this->count_produtos < 20) {
            for ($n = 0; $n < 20 - $this->count_produtos; $n++) {
                $this->pdf->Ln(12);
                $this->pdf->SetX(20);
                $this->pdf->Cell(65,  12, '', 'LR', 0, 'C');
                $this->pdf->Cell(65,  12, '', 'LR', 0, 'C');
                $this->pdf->Cell(135, 12, '', 'LR', 0, 'L');
                $this->pdf->Cell(155,  12, '', 'LR', 0, 'C');
                $this->pdf->Cell(65,  12, '', 'LR', 0, 'R');
                $this->pdf->Cell(65,  12, '', 'LR', 0, 'R');
            }
        }
        $this->pdf->Ln(12);
        $this->pdf->Line(20, $this->pdf->GetY(), 570, $this->pdf->GetY());
    }

    public function addRodapeNota()
  {
      $this->pdf->Ln(20);
      
      $this->pdf->SetFont('Arial','',8);
      $this->pdf->SetTextColor(0,0,0);
      $this->pdf->SetX(20);
      $this->pdf->Cell(300, 12, 'DADOS ADICIONAIS: ', 0, 0, 'L');
      
      $this->pdf->Ln(12);
      $this->pdf->SetTextColor(100,100,100);
      $this->pdf->SetX(20);
      $this->pdf->Cell(280, 12, mb_convert_encoding('Informações complementares',"ISO-8859-1","UTF-8"), 'LTR', 0, 'L');
      $this->pdf->Cell(270, 12, mb_convert_encoding('Reservado',"ISO-8859-1","UTF-8"), 'LTR', 0, 'L');
      
      $this->pdf->Ln(8);
      
      $this->pdf->SetTextColor(0,0,0);
      $this->pdf->SetX(20);
      $this->pdf->Cell(280, 48, '', 'LBR', 0, 'L');
      $this->pdf->Cell(270, 48, '', 'LBR', 0, 'L');
      
      $this->pdf->Ln(52);
    
  }
}
