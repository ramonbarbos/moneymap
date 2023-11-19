<?php

use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Widget\Util\TXMLBreadCrumb;

/**
 * CommonPage
 *
 * @version    1.0
 * @package    control
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class InicialPage extends TPage
{
    public function __construct()
    {
        parent::__construct();

        try {
            TTransaction::open('sample');


                $conn = TTransaction::get();
                $sqlQuant = "SELECT SUM(quantidade) as total_quantidade FROM estoque";
                $result = $conn->query($sqlQuant);

            
                if ($result) {
                    $row = $result->fetch(PDO::FETCH_ASSOC);
                    $estoqueTotal = $row['total_quantidade'];
                } else {
                    $estoqueTotal = 0;
                }

                $sqlVal = "SELECT SUM(valor_total) as total_valor FROM estoque";
                $resultVal = $conn->query($sqlVal);


                if ($resultVal) {
                    $row = $resultVal->fetch(PDO::FETCH_ASSOC);
                    $valorTotal = $row['total_valor'];
                } else {
                    $valorTotal = 0;
                }

        

            $html = new THtmlRenderer('app/templates/theme3/system_dashboard.html');

            $indicator1 = new THtmlRenderer('app/templates/theme3/info-box.html');
            $indicator2 = new THtmlRenderer('app/templates/theme3/info-box.html');
            $indicator3 = new THtmlRenderer('app/templates/theme3/info-box.html');

            $estoqueZero = Estoque::where('quantidade', '=', 0)->count();


            $indicator1->enableSection('main', ['title' => 'Estoque',    'icon' => 'cube', 'background' => 'orange', 'text' => 'PRODUTO COM ESTOQUE BAIXO', 'value' => $estoqueZero]);

            $indicator2->enableSection('main', ['title' => 'Estoque',    'icon' => 'box', 'background' => 'blue', 'text' => 'QUANTIDADE DE PRODUTO NO ESTOQUE', 'value' => $estoqueTotal]);

            $indicator3->enableSection('main', ['title' => 'Estoque',    'icon' => 'dollar-sign', 'background' => 'green', 'text' => 'CUSTO TOTAL DE PRODUTOS', 'value' =>   'R$ '.$valorTotal]);

            $html->enableSection('main', ['indicator1' => $indicator1, 'indicator2' => $indicator2,  'indicator3' => $indicator3]);

            $container = new TVBox;
            $container->style = 'width: 100%';
            $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
            $container->add($html);

            TTransaction::close();
            parent::add($container);
        } catch (Exception $e) {
            parent::add($e->getMessage());
        }
    }
}
