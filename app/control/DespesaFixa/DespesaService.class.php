
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

class DespesaService
{
  private static $saldos = [];

  public static function onValorChange($param)
  {
      try {
          TTransaction::open('sample');
  
          if (!empty($param['cpf'])) {
              $folha = Folha::where('cpf', '=', $param['cpf'])->first();
              $data = new stdClass;
              $data->saldo = [];

              $acumulado = 0; // Nova variável para acumular os valores retirados

              if (!empty($param['evento_id'])) {
                  foreach ($param['evento_id'] as $key => $item) {
                      $evento = Evento::where('id', '=', $item)->first();
  
                      // Vamos atualizar o acumulado para incluir o valor atual
                      $acumulado += (float) $param['valor'][$key];
                      
                      // Calcula o novo saldo subtraindo o acumulado do saldo anterior
                      $saldoAtual = $folha->vl_salario - $acumulado;
                      
                      // Atualiza a memória temporária com o novo saldo
                      self::$saldos[$param['cpf']][$item] = $saldoAtual;
                      
                      // Adiciona o novo saldo ao array $data->saldo
                      $data->saldo[] = $saldoAtual;
                      
                  }
              }
  
              // Envia os saldos calculados para a interface do usuário
              TForm::sendData('my_form', (object) $data);

          }
  
          TTransaction::close();
      } catch (Exception $e) {
          new TMessage('error', $e->getMessage());
          TTransaction::rollback();
      }
  }
  
  public static function onCPFChange($params)
  {
    if (!empty($params['cpf'])) {
      try {

        //$obj = new DespesaForm();
        TTransaction::open('sample');
        $despesa = Despesa::where('cpf', '=', $params['cpf'])->first();
        $folha   =  Folha::where('cpf', '=', $params['cpf'])->first();
        if (@$folha->cpf ==  @$despesa->cpf) { //Já existe despesa  com o CPF
          $despesa = Despesa::where('cpf', '=', $params['cpf'])->first();
          $item_despesas = ItemDespesa::where('despesa_id', '=', $despesa->id)->load();

          $data = new stdClass;
          $data->id_item = [];
          $data->dt_despesa = [];
          $data->evento_id = [];
          $data->descricao = [];
          $data->valor = [];
          $data->id = [];
          $data->anoMes = [];
          $data->vl_despesa = [];
          $data->vl_salario = [];


          foreach ($item_despesas as $item) {

            TFieldList::addRows('my_field_list', 1);
            $data->id_item[] = $item->id_item;
            $data->dt_despesa[] = $item->dt_despesa;
            $data->evento_id[] = $item->evento_id;
            $data->descricao[] = $item->descricao;
            $data->valor[] = $item->valor;
            $data->saldo[] = $item->saldo;

            $data->id = $despesa->id;
            $data->anoMes = $despesa->anoMes;
            $data->vl_despesa = $despesa->vl_despesa;
            $data->vl_salario = $folha->vl_salario;

            TForm::sendData('my_form', (object) $data);
          }
          new TMessage('info', 'Dados Carregados.');
        } else if (!$folha) { //Quando nao tiver folha encontrada
          new TMessage('info',  'Folha não encontrada');
        } else { //Quando tiver Folha mas não tem Despesa com o CPF

          //verificar se existe desconto vinculado ao cpf
          $folha  =  Folha::where('cpf', 'like', $params['cpf'])->first();
          $item_folhas = ItemFolha::where('folha_id', '=', $folha->id)
            ->where('tipo', 'like', 'D')
            ->load();

          if ($item_folhas || $folha) {

            $dataF = new stdClass;
            $dataF->evento_id = [];
            $dataF->valor = [];
            $cont = 0;

            foreach ($item_folhas as $item) {

              TFieldList::addRows('my_field_list', $cont);
              $dataF->evento_id[] = $item->evento_id;
              $dataF->valor[] = $item->valor;
               TForm::sendData('my_form', (object) $dataF);

              $cont++;
            }
           TForm::sendData('my_form', (object) ['vl_salario' => $folha->vl_salario]);

         //   new TMessage('info', 'Descontos Carregados.');


          } else {
           TFieldList::clear('my_field_list');
          }
        }

        TTransaction::close();
      } catch (Exception $e) {
        new TMessage('error', $e->getMessage());
        TTransaction::rollback();
      }
    }
  }

  public static function showRow($param)
  {
    new TMessage('info', str_replace(',', '<br>', json_encode($param)));
  }
}