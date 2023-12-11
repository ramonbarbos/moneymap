<?php

use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Widget\Util\TXMLBreadCrumb;

/**
 * WelcomeView
 *
 * @version    1.0
 * @package    control
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class WelcomeView extends TPage
{
    /**
     * Class constructor
     * Creates the page
     */
    function __construct()
    {
        parent::__construct();

        try {
            TTransaction::open('sample');


           
            
               

        

            $html = new THtmlRenderer('app/templates/theme3/system_dashboard.html');

            $indicator1 = new THtmlRenderer('app/templates/theme3/info-box.html');
            $indicator2 = new THtmlRenderer('app/templates/theme3/info-box.html');
            $indicator3 = new THtmlRenderer('app/templates/theme3/info-box.html');

            $saldoAtual = new Despesa(1);


            $indicator1->enableSection('main', ['title' => 'Folha',    'icon' => 'cube', 'background' => 'orange', 'text' => 'SALDO ATUAL', 'value' => $saldoAtual->saldo]);

          //  $indicator2->enableSection('main', ['title' => 'Estoque',    'icon' => 'box', 'background' => 'blue', 'text' => 'QUANTIDADE DE PRODUTO NO ESTOQUE', 'value' => $estoqueTotal]);

           // $indicator3->enableSection('main', ['title' => 'Estoque',    'icon' => 'dollar-sign', 'background' => 'green', 'text' => 'CUSTO TOTAL DE PRODUTOS', 'value' =>   'R$ '.$valorTotal]);

            $html->enableSection('main', ['indicator1' => $indicator1]);

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
