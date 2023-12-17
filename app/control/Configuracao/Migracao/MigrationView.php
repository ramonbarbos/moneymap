<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TConnection;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TFile;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapFormBuilder;

class MigrationView extends TPage
{
    private $form;

    use Adianti\base\AdiantiStandardFormTrait;

    public function __construct($param)
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('my_form');
        $this->form->setFormTitle('Novas Despesas');
        $this->form->setClientValidation(true);
        $this->form->setColumnClasses(3, ['col-sm-3', 'col-sm-3', 'col-sm-3', 'col-sm-3']);

        $file = new TFile('arq_plan');
        $this->form->addFields([new TLabel('Arquivo')], [$file]);

        // Adicione campos dinâmicos para mapeamento
        foreach (['id_item', 'despesa_id', 'dt_despesa', 'evento_id', 'descricao', 'valor', 'saldo', 'fl_situacao'] as $coluna) {
            $campo = new TEntry("mapeamento_{$coluna}");
            $mapeamentoFields["mapeamento_{$coluna}"] = $campo;
            $this->form->addFields([new TLabel("{$coluna}")], [$campo]);
        }

        // Adicione o botão de importar
        $importar = TButton::create('importar', [$this, 'onImportCSV'], 'Importar', 'fa:plus-circle green');
        $importar->getAction()->setParameter('static', '1');
        $this->form->addFields([], [$importar]);

        // Salve os campos dinâmicos para uso posterior
        $this->form->setFields($mapeamentoFields);

        // wrap the page content using vertical box
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $vbox->add($this->form);

        parent::add($vbox);
    }

    public function onImportCSV($params)
    {
        $data = $this->form->getData();

        if ($data->arq_plan == 'Nenhum arquivo selecionado' || $data->arq_plan == '') {
            new TMessage('error', 'Favor selecionar arquivo antes de importar.');
        } else {
            // Carregue o conteúdo do CSV
            $csv = new TReadCsv('tmp/' . $data->arq_plan);
            $conteudo = $csv->abre();

            // Sete a conexão
            $dbconn = TConnection::open('changeman');

            // Inicialize a instrução de update
            foreach ($conteudo as $dados) {
                // Verifique se a tabela está configurada no array de parâmetros
                if (!empty($params['tabela'])) {
                    $tabela = $params['tabela'];

                    // Inicializa a instrução de update
                    $mssql = "UPDATE {$tabela} SET ";

                    // Percorre o mapeamento de colunas
                    foreach ($params['mapeamento_colunas'] as $colunaCSV => $colunaTabela) {
                        if (isset($dados[$colunaCSV])) {
                            // Adiciona a coluna da tabela e o valor da coluna CSV
                            $mssql .= "{$colunaTabela} = '{$dados[$colunaCSV]}', ";
                        }
                    }

                    // Remove a vírgula final e executa o update
                    $mssql = rtrim($mssql, ', ');
                    $mssql .= " WHERE produto = '{$dados[0]}'";

                    $result = $dbconn->exec($mssql);
                }
            }

            new TMessage('info', 'Arquivo importado com sucesso!');
        }
    }
}
