
<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Control\TWindow;
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

class RelatorioFolha
{

  private $pdf;
  private $count_produtos;


  public function onGenerator($data)
  {

    try {
      TTransaction::open('sample');

      //$customer = Customer::find($data->customer_id);
      $this->pdf = new FPDF('P', 'pt');
      $this->pdf->SetMargins(2, 2, 2); // define margins
      $this->pdf->AddPage();
      $this->pdf->Ln();
      $this->pdf->Image('app/images/logo_vs1.png', 25, 25, 100);
      $this->pdf->SetLineWidth(1);
      $this->pdf->SetTextColor(0, 0, 0);
      $this->pdf->SetFont('Arial', 'B', 10);

      $this->pdf->SetXY(470, 27);
      $this->pdf->Cell(100, 20, 'FOLHA: ' . $data['id'], 1, 0, 'L');

      $this->addCabecalhoNota($data['cpf'], $data['tp_folha'], $data['anoMes'], $data['vl_salario'], $data['vl_desconto']);

      $this->addCabecalhoProduto();

      if ($data['id']) {
        $itemFolha = ItemFolha::where('folha_id', '=', $data['id'])->orderby(1)->load();
        foreach ($itemFolha as $index =>  $item) {
          $this->AddEvento($item);
        }
      }
      $this->addRodapeFolha();
      $this->addRodapeNota();
      $file = 'app/output/ResumoFolha.pdf';

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
      TTransaction::rollback();
      new TMessage('error', $e->getMessage());
    }
  }
  public function addCabecalhoNota($cpf, $tp_folha, $anoMes, $vl_salario, $vl_despesas)
  {
    $tipoFolha = new TipoFolha($tp_folha);

    $this->pdf->SetY(80);

    $this->pdf->SetFont('Arial', '', 8);
    $this->pdf->SetTextColor(100, 100, 100);
    $this->pdf->SetX(20);
    $this->pdf->Cell(150, 12, mb_convert_encoding('CPF: ',"ISO-8859-1","UTF-8"), 'LTR', 0, 'L');
    $this->pdf->Cell(100, 12, 'Tipo de Folha: ', 'LTR', 0, 'L');
    $this->pdf->Cell(100, 12, mb_convert_encoding('Ano Mês: ',"ISO-8859-1","UTF-8"), 'LTR', 0, 'L');
    $this->pdf->Cell(100, 12, 'Salario: ', 'LTR', 0, 'L');
    $this->pdf->Cell(100, 12, 'Despesas: ', 'LTR', 0, 'L');

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
    $this->pdf->Cell(300, 12, 'ITENS DA FOLHA: ', 0, 0, 'L');

    $this->pdf->Ln(12);
    $this->pdf->SetX(20);
    $this->pdf->SetFillColor(230, 230, 230);
    $this->pdf->Cell(40,  12, mb_convert_encoding('Evento',"ISO-8859-1","UTF-8"),     1, 0, 'L', 1);
    $this->pdf->Cell(315, 12, mb_convert_encoding('Descrição',"ISO-8859-1","UTF-8"),  1, 0, 'L', 1);
    $this->pdf->Cell(50,  12, 'Tipo', 1, 0, 'L', 1);
    $this->pdf->Cell(40,  12, 'Ref',      1, 0, 'L', 1);
    $this->pdf->Cell(45,  12, 'Parcelas',      1, 0, 'L', 1);
    $this->pdf->Cell(70,  12, 'Valor',       1, 0, 'L', 1);
  }
  public function AddEvento($itemFolha)
  {
    TTransaction::open('sample');
    $evento = new Evento($itemFolha->evento_id);

    if ($evento->incidencia == 'P') {
      $evento->incidencia =  'Provento';
    } else if ($evento->incidencia == 'D') {
      $evento->incidencia =  'Desconto';
    } else if ($evento->incidencia == 'DD') {
      $evento->incidencia =  'Dedução';
    }

    $this->pdf->Ln(12);
    $this->pdf->SetX(20);
    $this->pdf->SetFillColor(230, 230, 230);

    $this->pdf->Cell(40,  12, $itemFolha->id, 'LR', 0, 'C');
    $this->pdf->Cell(315, 12, mb_convert_encoding($evento->descricao, "ISO-8859-1","UTF-8"), 'LR', 0, 'L');
    $this->pdf->Cell(50,  12,   mb_convert_encoding($evento->incidencia, "ISO-8859-1","UTF-8"), 'LR', 0, 'C');
    $this->pdf->Cell(40,  12, $itemFolha->ref, 'LR', 0, 'C');
    $this->pdf->Cell(45,  12, $itemFolha->parcela, 'LR', 0, 'C');
    $this->pdf->Cell(70,  12, 'R$ ' . number_format($itemFolha->valor, 2), 'LR', 0, 'R');

    $this->count_produtos++;
  }
  public function addRodapeFolha()
  {
    if ($this->count_produtos < 20) {
      for ($n = 0; $n < 20 - $this->count_produtos; $n++) {
        $this->pdf->Ln(12);
        $this->pdf->SetX(20);
        $this->pdf->Cell(40,  12, '', 'LR', 0, 'C');
        $this->pdf->Cell(315, 12, '', 'LR', 0, 'L');
        $this->pdf->Cell(50,  12, '', 'LR', 0, 'C');
        $this->pdf->Cell(40,  12, '', 'LR', 0, 'R');
        $this->pdf->Cell(45,  12, '', 'LR', 0, 'R');
        $this->pdf->Cell(70,  12, '', 'LR', 0, 'C');
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
      $this->pdf->Cell(270, 12, 'Reservado', 'LTR', 0, 'L');
      
      $this->pdf->Ln(8);
      
      $this->pdf->SetTextColor(0,0,0);
      $this->pdf->SetX(20);
      $this->pdf->Cell(280, 48, '', 'LBR', 0, 'L');
      $this->pdf->Cell(270, 48, '', 'LBR', 0, 'L');
      
      $this->pdf->Ln(52);
    
  }
  
}
