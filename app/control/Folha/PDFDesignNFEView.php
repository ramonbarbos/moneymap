<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Control\TWindow;
use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TNumeric;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

class PDFDesignNFEView extends TPage
{
    private $form; // form
    private $pdf;
    private $total_produtos;
    private $total_icms;
    private $count_produtos;
    public function __construct($param)
    {
        parent::__construct($param);
        $this->form = new BootstrapFormBuilder('form_nota');
        $this->form->setFormTitle('Nota fiscal completa');
        $this->form->setFieldSizes('100%');
        $customer_id = new TDBUniqueSearch('customer_id', 'samples', 'Customer', 'id', 'name');
        $customer_id->setMinLength(1);
        $customer_id->setValue(1);
        $customer_id->setMask('{name} ({id})');
        $cfop = new TEntry('cfop');
        $ie = new TEntry('ie');
        $cnpj = new TEntry('cnpj');
        $tipo = new TEntry('tipo');
        $number= new TEntry('numero');
        $frete = new TNumeric('frete', 2, ',', '.');
        $seguro = new TNumeric('seguro', 2, ',', '.');
        $despesas = new TNumeric('despesas', 2, ',', '.');
        $number->setMask('9999');
        $cnpj->setMask('99.999.999/9999-99');
        
        $cfop->setValue('5.949');
        $ie->setValue('123456789');
        $number->setValue('001');
        $cnpj->setValue('12.345.678/0001-99');
        $tipo->setValue('VENDA');
        $frete->setValue('0,00');
        $seguro->setValue('50,00');
        $despesas->setValue('200,00');
        $this->form->addAction('Generate', new TAction([$this, 'onGenerator'], ['static' => 1]), 'fa:cogs');
        $product = new TDBUniqueSearch('product_id[]', 'samples', 'Product', 'id', 'description');
        $product->setMinLength(0);
        $product->setSize('100%');
        $product->setMask('({id}) {description}');
        $qtde = new TNumeric('qtd[]', 2, ',', '.');
        $price = new TNumeric('price[]', 2, ',', '.');
        $total = new TNumeric('total[]', 2, ',', '.');
        $price->setEditable(FALSE);
        $total->setEditable(FALSE);
        $product->setChangeAction(new TAction([$this, 'onChange']));
        $qtde->setExitAction(new TAction([$this, 'onChange']));
        $qtde->setSize('100%');
        $price->setSize('100%');
        $total->setSize('100%');
        $this->products = new TFieldList;
        $this->products->style = ('width: 100%');
        $this->products->addField( '<b>Product</b>', $product, ['width' => '50%']);
        $this->products->addField( '<b>Qtd</b>', $qtde, ['width' => '10%', 'sum' => true]);
        $this->products->addField( '<b>Price</b>', $price, ['width' => '20%', 'sum' => true]);
        $this->products->addField( '<b>Total</b>', $total, ['width' => '20%', 'sum' => true]);
        $this->products->addHeader();
        $this->form->addField($product);
        $this->form->addField($qtde);
        $this->form->addField($total);
        $row = $this->form->addFields( [new TLabel('Number'), $number], [new TLabel('Customer'), $customer_id] );
        $row->layout = ['col-sm-2', 'col-sm-10'];
        $row = $this->form->addFields( [new TLabel('Type'), $tipo],[new TLabel('CFOP'), $cfop], [new TLabel('CNPJ'), $cnpj], [new TLabel('IE'), $ie] );
        $row->layout = ['col-sm-3','col-sm-3','col-sm-3','col-sm-3'];
       
        $row = $this->form->addFields( [new TLabel('Shipping'), $frete],[new TLabel('Indemnity'), $seguro], [new TLabel('Expenses'), $despesas] );
        $row->layout = ['col-sm-4','col-sm-4','col-sm-4'];
        $row = $this->form->addContent( [ new TLabel('Products') ] );
        $row->layout = ['col-sm-12'];
        $row = $this->form->addContent( [ $this->products ] );
        $row->layout = ['col-sm-12'];
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);
        parent::add($vbox);
    }
    
    /**
     *
     */
    public function onGenerator()
    {
        try
        {
            TTransaction::open('samples');
            $data = $this->form->getData();
            $customer = Customer::find($data->customer_id);
            $this->pdf = new FPDF('P', 'pt');
            $this->pdf->SetMargins(2,2,2); // define margins
            $this->pdf->AddPage();
            $this->pdf->Ln();
            $this->pdf->Image('app/images/logo.png', 10, 20, 200);
            $this->pdf->SetLineWidth(1);
            $this->pdf->SetTextColor(0,0,0);
            $this->pdf->SetFont('Arial','B',10);
            
            $this->pdf->SetXY(470, 27);
            $this->pdf->Cell(100, 20, 'NOTA FISCAL: ' . $data->numero, 1, 0, 'L');
            
            $this->addCabecalhoNota($data->cfop, $data->tipo, $data->cnpj, $data->ie);
            $this->addCliente($customer);
            $this->addCabecalhoProduto();
            if ($data->product_id)
            {
                foreach($data->product_id as $index => $product_id)
                {
                    $produto = Product::find($product_id);
                    $produto->qtd = $data->qtd[$index]??1;
                    $this->addProduto($produto);
                }
            }
            $this->addRodapeProduto();
            $this->addTotaisNota($data->frete, $data->seguro, $data->despesas);
            
            $this->addRodapeNota();
            
            $file = 'app/output/danfe.pdf';
            
            if (!file_exists($file) OR is_writable($file))
            {
                $this->pdf->Output($file);
                
                $window = TWindow::create(_t('Designed Danfe'), 0.8, 0.8);
                $object = new TElement('object');
                $object->data  = $file;
                $object->type  = 'application/pdf';
                $object->style = "width: 100%; height:calc(100% - 10px)";
                $object->add('O navegador não suporta a exibição deste conteúdo, <a style="color:#007bff;" target=_newwindow href="'.$object->data.'"> clique aqui para baixar</a>...');
    
                $window->add($object);
                $window->show();
            }
            else
            {
                throw new Exception(_t('Permission denied') . ': ' . $file);
            }
            
            TTransaction::close();
        }
        catch(Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * Método addCliente
     * Adiciona um cliente na nota
     * @param $cliente Objeto contendo os atributos do cliente
     */
    public function addCliente($cliente)
    {
        $this->pdf->SetY(130);
        
        $this->pdf->SetFont('Arial','',8);
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetX(20);
        $this->pdf->Cell(300, 12, utf8_decode('DESTINATÁRIO/REMETENTE: '), 0, 0, 'L');
        
        $this->pdf->Ln(12);
        
        $this->pdf->SetTextColor(100,100,100);
        $this->pdf->SetX(20);
        $this->pdf->Cell(300, 12, utf8_decode('Nome/Razão Social: '), 'LTR', 0, 'L');
        $this->pdf->Cell(150, 12, utf8_decode('CNPJ/CPF: '), 'LTR', 0, 'L');
        $this->pdf->Cell(100, 12, utf8_decode('Data de emissão: '), 'LTR', 0, 'L');
        
        $this->pdf->Ln(8);
        
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetX(20);
        $this->pdf->Cell(300, 16, $cliente->name, 'LBR', 0, 'L');
        $this->pdf->Cell(150, 16, '999.999.999-99', 'LBR', 0, 'L');
        $this->pdf->Cell(100, 16, date('d/m/Y'), 'LBR', 0, 'L');
        
        $this->pdf->Ln(16);
        
        $this->pdf->SetTextColor(100,100,100);
        $this->pdf->SetX(20);
        $this->pdf->Cell(250, 12, utf8_decode('Endereço: '), 'LTR', 0, 'L');
        $this->pdf->Cell(100, 12, utf8_decode('Bairro: '), 'LTR', 0, 'L');
        $this->pdf->Cell(100, 12, utf8_decode('CEP: '), 'LTR', 0, 'L');
        $this->pdf->Cell(100, 12, utf8_decode('Data de saída: '), 'LTR', 0, 'L');
        
        $this->pdf->Ln(8);
        
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetX(20);
        $this->pdf->Cell(250, 16, utf8_decode($cliente->address), 'LBR', 0, 'L');
        $this->pdf->Cell(100, 16, utf8_decode('centro'), 'LBR', 0, 'L');
        $this->pdf->Cell(100, 16, '99.999-000', 'LBR', 0, 'L');
        $this->pdf->Cell(100, 16, date('d/m/Y'), 'LBR', 0, 'L');
        
        $this->pdf->Ln(16);
        
        $this->pdf->SetTextColor(100,100,100);
        $this->pdf->SetX(20);
        $this->pdf->Cell(180, 12, utf8_decode('Município: '), 'LTR', 0, 'L');
        $this->pdf->Cell(70,  12, utf8_decode('Fone/Fax: '), 'LTR', 0, 'L');
        $this->pdf->Cell(100, 12, utf8_decode('UF: '), 'LTR', 0, 'L');
        $this->pdf->Cell(100, 12, utf8_decode('Inscrição Estadual: '), 'LTR', 0, 'L');
        $this->pdf->Cell(100, 12, utf8_decode('Hora Saída: '), 'LTR', 0, 'L');
        
        $this->pdf->Ln(8);
        
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetX(20);
        $this->pdf->Cell(180, 16, utf8_decode($cliente->city->name), 'LBR', 0, 'L');
        $this->pdf->Cell(70,  16, utf8_decode($cliente->phone), 'LBR', 0, 'L');
        $this->pdf->Cell(100, 16, utf8_decode($cliente->city->state->name), 'LBR', 0, 'L');
        $this->pdf->Cell(100, 16, '', 'LBR', 0, 'L');
        $this->pdf->Cell(100, 16, date('H:i'), 'LBR', 0, 'L');
        
        $this->pdf->Ln(16);
    }
    /**
     * Adiciona o cabeçalho da nota
     * @param $cfop Código fiscal da operação
     * @param $operacao Descrição da operação
     * @param $cnpj Código do Cadastro Nacional de Pessoas Jurídicas
     * @param $ie Código de Inscrição Estadual
     */
    public function addCabecalhoNota($cfop, $operacao, $cnpj, $ie)
    {
        $this->pdf->SetY(100);
    
        $this->pdf->SetFont('Arial','',8);
        $this->pdf->SetTextColor(100,100,100);
        $this->pdf->SetX(20);
        $this->pdf->Cell(250, 12, utf8_decode('Natureza da operação: '), 'LTR', 0, 'L');
        $this->pdf->Cell(50, 12, 'CFOP: ', 'LTR', 0, 'L');
        $this->pdf->Cell(125, 12, 'CNPJ: ', 'LTR', 0, 'L');
        $this->pdf->Cell(125, 12, 'IE: ', 'LTR', 0, 'L');
        
        $this->pdf->Ln(8);
        
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetX(20);
        $this->pdf->Cell(250, 16, $operacao, 'LBR', 0, 'L');
        $this->pdf->Cell(50,  16, $cfop, 'LBR', 0, 'L');
        $this->pdf->Cell(125, 16, $cnpj, 'LBR', 0, 'L');
        $this->pdf->Cell(125, 16, $ie, 'LBR', 0, 'L');
        
        $this->pdf->Ln(16);
    }
    /**
     * Adiciona a linha de cabeçalho para os produtos
     */
    public function addCabecalhoProduto()
    {
        $this->pdf->SetY(220);
        
        $this->pdf->SetFont('Arial','',8);
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetX(20);
        $this->pdf->Cell(300, 12, 'DADOS DO PRODUTO: ', 0, 0, 'L');
        
        $this->pdf->Ln(12);
        $this->pdf->SetX(20);
        $this->pdf->SetFillColor(230,230,230);
        $this->pdf->Cell(40,  12, utf8_decode('Código'),     1, 0, 'L', 1);
        $this->pdf->Cell(330, 12, utf8_decode('Descrição'),  1, 0, 'L', 1);
        $this->pdf->Cell(45,  12, 'Quantidade', 1, 0, 'L', 1);
        $this->pdf->Cell(50,  12, 'Valor',      1, 0, 'L', 1);
        $this->pdf->Cell(55,  12, 'Total',      1, 0, 'L', 1);
        $this->pdf->Cell(30,  12, 'ICMS',       1, 0, 'L', 1);
    }
    /**
     * Adiciona um produto na nota
     * @param $produto Objeto com os atributos do produto
     */
    public function addProduto($produto)
    {
        $this->pdf->Ln(12);
        $this->pdf->SetX(20);
        $this->pdf->SetFillColor(230,230,230);
        $total = $produto->sale_price * $produto->qtd;
        
        $this->pdf->Cell(40,  12, $produto->id, 'LR', 0, 'C');
        $this->pdf->Cell(330, 12, $produto->description, 'LR', 0, 'L');
        $this->pdf->Cell(45,  12, $produto->qtd, 'LR', 0, 'C');
        $this->pdf->Cell(50,  12, number_format($produto->sale_price, 2), 'LR', 0, 'R');
        $this->pdf->Cell(55,  12, number_format($total, 2), 'LR', 0, 'R');
        $this->pdf->Cell(30,  12, number_format(6, 2),       'LR', 0, 'C');
        
        $this->total_produtos += $total;
        $this->total_icms     += (6 * $total / 100);
        $this->count_produtos ++;
    }
    /**
     * Adiciona o rodapé ao final da lista de produtos
     * Este método completa o espaço da listagem
     */
    public function addRodapeProduto()
    {
        if ($this->count_produtos < 20)
        {
            for ($n=0; $n< 20-$this->count_produtos; $n++)
            {
                $this->pdf->Ln(12);
                $this->pdf->SetX(20);
                $this->pdf->Cell(40,  12, '', 'LR', 0, 'C');
                $this->pdf->Cell(300, 12, '', 'LR', 0, 'L');
                $this->pdf->Cell(45,  12, '', 'LR', 0, 'C');
                $this->pdf->Cell(50,  12, '', 'LR', 0, 'R');
                $this->pdf->Cell(55,  12, '', 'LR', 0, 'R');
                $this->pdf->Cell(30,  12, '', 'LR', 0, 'C');
            }
        }
        $this->pdf->Ln(12);
        $this->pdf->Line(20, $this->pdf->GetY(), 570, $this->pdf->GetY());
    }
    /**
     * Adiciona os totais da nota
     * @param $frete valor do frete
     * @param $seguro valor do seguro
     * @param $despesas valor das despesas
     */
    public function addTotaisNota($frete, $seguro, $despesas)
    {
        $this->pdf->Ln(4);
        
        $this->pdf->SetFont('Arial','',8);
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetX(20);
        $this->pdf->Cell(300, 12, utf8_decode('CÁLCULO DO IMPOSTO: '), 0, 0, 'L');
        
        $this->pdf->Ln(12);
        $this->pdf->SetTextColor(100,100,100);
        $this->pdf->SetX(20);
        $this->pdf->Cell(110, 12, utf8_decode('Base cálculo ICMS'), 'LTR', 0, 'L');
        $this->pdf->Cell(110, 12, utf8_decode('Valor do ICMS: '), 'LTR', 0, 'L');
        $this->pdf->Cell(110, 12, utf8_decode('Base cáluclo ICMS subst: '), 'LTR', 0, 'L');
        $this->pdf->Cell(110, 12, utf8_decode('Valor do ICMS subs: '), 'LTR', 0, 'L');
        $this->pdf->Cell(110, 12, utf8_decode('Valor total dos produtos: '), 'LTR', 0, 'L');
        
        $this->pdf->Ln(8);
        
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetX(20);
        $this->pdf->Cell(110, 16, number_format($this->total_produtos, 2), 'LBR', 0, 'R');
        $this->pdf->Cell(110, 16, number_format($this->total_icms, 2), 'LBR', 0, 'R');
        $this->pdf->Cell(110, 16, 0, 'LBR', 0, 'R');
        $this->pdf->Cell(110, 16, 0, 'LBR', 0, 'R');
        $this->pdf->Cell(110, 16, number_format($this->total_produtos, 2), 'LBR', 0, 'R');
        
        $this->pdf->Ln(16);
        
        $this->pdf->SetTextColor(100,100,100);
        $this->pdf->SetX(20);
        $this->pdf->Cell(110, 12, 'Valor do frete', 'LTR', 0, 'L');
        $this->pdf->Cell(110, 12, 'Valor do seguro: ', 'LTR', 0, 'L');
        $this->pdf->Cell(110, 12, 'Outras despesas: ', 'LTR', 0, 'L');
        $this->pdf->Cell(110, 12, 'Valor do IPI: ', 'LTR', 0, 'L');
        $this->pdf->Cell(110, 12, 'Valor total da nota: ', 'LTR', 0, 'L');
        
        $this->pdf->Ln(8);
        
        $total_nota = $this->total_produtos + $frete + $seguro + $despesas;
        
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetX(20);
        $this->pdf->Cell(110, 16, number_format($frete, 2), 'LBR', 0, 'R');
        $this->pdf->Cell(110, 16, number_format($seguro, 2), 'LBR', 0, 'R');
        $this->pdf->Cell(110, 16, number_format($despesas, 2), 'LBR', 0, 'R');
        $this->pdf->Cell(110, 16, number_format((float) $this->total_ipi, 2), 'LBR', 0, 'R');
        $this->pdf->Cell(110, 16, number_format($total_nota, 2), 'LBR', 0, 'R');
    }
    /**
     * Adiciona o rodapé da nota
     * @param $razao razão social
     */
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
        $this->pdf->Cell(280, 12, utf8_decode('Informações complementares'), 'LTR', 0, 'L');
        $this->pdf->Cell(270, 12, 'Reservado ao fisco', 'LTR', 0, 'L');
        
        $this->pdf->Ln(8);
        
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetX(20);
        $this->pdf->Cell(280, 48, '', 'LBR', 0, 'L');
        $this->pdf->Cell(270, 48, '', 'LBR', 0, 'L');
        
        $this->pdf->Ln(52);
        
        $this->pdf->SetX(20);
        $this->pdf->Cell(300, 12, utf8_decode('INFORMAÇÕES DE RECEBIMENTO: '), 0, 0, 'L');
        
        $this->pdf->Ln(12);
        $this->pdf->SetTextColor(100,100,100);
        $this->pdf->SetX(20);
        $this->pdf->Cell(400, 12, 'Recebemos de Tutor os produtos constantes na nota fiscal indicada ao lado', 'LTR', 0, 'L');
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->Cell(150, 12, 'NOTA FISCAL', 'LTR', 0, 'C');
        
        $this->pdf->Ln(12);
        
        $this->pdf->SetX(20);
        $this->pdf->SetTextColor(100,100,100);
        $this->pdf->Cell(200, 12, 'Data do recebimento', 'LTR', 0, 'L');
        $this->pdf->Cell(200, 12, utf8_decode('Identificação e assinatura do recebedor'), 'LTR', 0, 'L');
        $this->pdf->Cell(150, 12, '', 'LR', 0, 'L');
        
        $this->pdf->Ln(8);
        $this->pdf->SetX(20);
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->Cell(200, 30, '', 'LBR', 0, 'L');
        $this->pdf->Cell(200, 30, '', 'LBR', 0, 'L');
        $this->pdf->Cell(150, 30, '', 'LBR', 0, 'L');
    }
    
    /**
     *
     */
    public static function onChange($param)
    {
        try
        {
            TTransaction::open('samples');
            $price = [];
            $total = [];
            
            foreach($param['product_id'] as $i => $product_id)
            {
                $product = new Product($product_id);
                $qtde = (float) str_replace(',', '.', str_replace('.', '', ($param['qtd'][$i] ?? 1 )));
                $price[] = number_format($product->sale_price, 2, ',', '.');
                $total[] = number_format($product->sale_price * $qtde, 2, ',', '.');
            }
            $data = new stdClass;
            $data->price = $price;
            $data->total = $total;
            TForm::sendData('form_nota', $data, false, true);
            TTransaction::close();
        }
        catch(Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * Insert some data at screen
     */
    public function onShow()
    {
        try
        {
            TTransaction::open('samples');
       
            $product1 = array_product()::find(1);
            $product2 = Product::find(2);
            $product3 = Product::find(3);
            $p1 = new stdClass;
            $p1->product_id = $product1->id;
            $p1->qtd = 1;
            $p1->price = $product1->sale_price;
            $p1->total = $product1->sale_price * $p1->qtd;
            
    
            $p2 = new stdClass;
            $p2->product_id = $product2->id;
            $p2->qtd = 2;
            $p2->price = $product2->sale_price;
            $p2->total = $product2->sale_price * $p2->qtd;
            
    
            $p3 = new stdClass;
            $p3->product_id = $product3->id;
            $p3->qtd = 3;
            $p3->price = $product3->sale_price;
            $p3->total = $product3->sale_price * $p3->qtd;
            
            $this->products->addDetail( $p1 );
            $this->products->addDetail( $p2 );
            $this->products->addDetail( $p3 );
            $this->products->addCloneAction();
            
            TTransaction::close();
        }
        catch(Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}