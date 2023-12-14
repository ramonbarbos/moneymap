<?php

use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Wrapper\BootstrapFormBuilder;

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

    private $form;

    function __construct()
    {
        parent::__construct();

        try {
            TTransaction::open('sample');
            $this->form = new BootstrapFormBuilder('my_form_painel');
            $this->form->setFormTitle('');
            $this->form->setColumnClasses(3, ['col-sm-3', 'col-sm-3', 'col-sm-3', 'col-sm-3']);

            $anoMes         = new TDBUniqueSearch('anoMes', 'sample', 'AnoMes', 'descricao', 'descricao');
            $anoMes->setMinLength(0);
            $cpf         = new TDBUniqueSearch('cpf', 'sample', 'FichaCadastral', 'cpf', 'cpf');
            $cpf->setMinLength(0);
            $tp_folha         = new TDBUniqueSearch('tp_folha', 'sample', 'TipoFolha', 'id', 'descricao');
            $tp_folha->setMinLength(0);

            $this->form->addFields([new TLabel('Mês')], [$anoMes], [new TLabel('CPF')], [$cpf],[new TLabel('Folha')], [$tp_folha],);


            $anoMes = '202312';
            $cpf = '85778905548';
            $tp_folha = '1';

      
            $html = new THtmlRenderer('app/templates/theme3/system_dashboard.html');

            $indicator1 = new THtmlRenderer('app/templates/theme3/info-box.html');
            $indicator2 = new THtmlRenderer('app/templates/theme3/info-box.html');
            $indicator3 = new THtmlRenderer('app/templates/theme3/info-box.html');

            $saldoAtual = Despesa::where('anoMes', '=', $anoMes)
                ->where('tp_folha', '=', $tp_folha)
                ->where('cpf', '=', $cpf)->first();

            $receita = $saldoAtual->saldo + $saldoAtual->vl_despesa;



            $indicator1->enableSection('main', ['title' => 'Folha',    'icon' => 'cube', 'background' => 'orange', 'text' => 'SALDO ATUAL', 'value' => 'R$ ' . $saldoAtual->saldo]);

            $indicator2->enableSection('main', ['title' => 'Despesa',    'icon' => 'box', 'background' => 'blue', 'text' => 'RECEITAS', 'value' => 'R$ ' . $receita]);

            $indicator3->enableSection('main', ['title' => 'Despesa',    'icon' => 'dollar-sign', 'background' => 'green', 'text' => 'DESPESAS', 'value' =>   'R$ ' . $saldoAtual->vl_despesa]);

            $html->enableSection('main', ['indicator1' => $indicator1, 'indicator2' => $indicator2, 'indicator3' => $indicator3]);

            $container = new TVBox;
            $container->style = 'width: 100%';
            $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
            $container->add($this->form);
            $container->add($html);

            TTransaction::close();
            parent::add($container);
        } catch (Exception $e) {
            parent::add($e->getMessage());
        }
    }
}
