
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

class DespesaService
{
  private static $saldos = [];

  public static function onValorChange($param)
  {
    try {
      TTransaction::open('sample');

      if (!empty($param['cpf'])) {
        $folha = Folha::where('cpf', '=', $param['cpf'])
          ->where('anoMes', '=', $param['anoMes'])
          ->where('tp_folha', '=', $param['tp_folha'])->first();

        $data = new stdClass;
        $data->saldo = [];

        $acumulado = 0; // Nova variável para acumular os valores retirados

        if (!empty($param['evento_id'])) {
          $formatter = new NumberFormatter('pt_BR', NumberFormatter::CURRENCY);
          foreach ($param['evento_id'] as $key => $item) {
            $acumulado += (float) $param['valor'][$key];

            $saldoAtual = $folha->vl_salario - $acumulado;

            // Formatação do saldo sem o "R$"
            $saldoFormatado = number_format($saldoAtual, 2, '.', '');

            self::$saldos[$param['cpf']][$item] = $saldoAtual;

            $data->saldo[] = $saldoFormatado;
          }
        }

        // Envia os saldos calculados e formatados para a interface do usuário
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
        TTransaction::open('sample');

        if ($params['anoMes'] && $params['cpf']) {


          $folhas = Folha::where('cpf', 'like', $params['cpf'])
            ->where('anoMes', 'like', $params['anoMes'])
            ->where('tp_folha', '=', $params['tp_folha'])->first();

          if (@$folhas->cpf == $params['cpf'] && @$folhas->anoMes == $params['anoMes'] && @$folhas->tp_folha == $params['tp_folha']) {
            TFieldList::enableField('my_field_list');

            $despesa = Despesa::where('cpf', '=', $params['cpf'])
              ->where('anoMes', '=', $params['anoMes'])
              ->where('tp_folha', '=', $params['tp_folha'])->first();

            $folha   =  Folha::where('cpf', '=', $params['cpf'])
              ->where('anoMes', '=', $params['anoMes'])
              ->where('tp_folha', '=', $params['tp_folha'])->first();

            if (@$folha->cpf ==  @$despesa->cpf && @$folha->anoMes ==  @$despesa->anoMes && @$despesa->tp_folha == $params['tp_folha']) { //Já existe despesa  com o CPF

              $item_despesas = ItemDespesa::where('despesa_id', '=', $despesa->id)->orderBy(1)->load();

              $data = new stdClass;
              $data->id_item = [];
              $data->fl_situacao = [];
              $data->dt_despesa = [];
              $data->evento_id = [];
              $data->descricao = [];
              $data->valor = [];

              if (!empty($data->cpf)) {
                $data->anoMes = $despesa->anoMes;
                $data->cpf = $despesa->cpf;
              }
              $data->id = $despesa->id;
              $data->vl_despesa = $despesa->vl_despesa;
              $data->vl_salario = $folha->vl_salario;


              foreach ($item_despesas as $item) {
                $dt_despesa_formatada = (new DateTime($item->dt_despesa))->format('d/m/Y');

                $data->id_item[] = $item->id_item;
                $data->dt_despesa[] = $dt_despesa_formatada;
                $data->evento_id[] = $item->evento_id;
                $data->descricao[] = $item->descricao;
                $data->valor[] = $item->valor;
                $data->saldo[] = $item->saldo;
                $data->fl_situacao[] = $item->fl_situacao;
                TFieldList::addRows('my_field_list', 1);
              }
              TForm::sendData('my_form', (object) $data, false, true, 200);

              TToast::show('info', 'Dados Encontrado.');
            } else if (!$folha) { //Quando nao tiver folha encontrada
              TToast::show('info', 'Folha não encontrada');
            } else { //Quando tiver Folha mas não tem Despesa com o CPF

              //verificar se existe desconto vinculado ao cpf
              $folha  =  Folha::where('cpf', 'like', $params['cpf'])
                ->where('anoMes', '=', $params['anoMes'])
                ->where('tp_folha', '=', $params['tp_folha'])->first();

              $item_folhas = ItemFolha::where('folha_id', '=', $folha->id)
                ->where('tipo', 'like', 'D')->orderby(1)
                ->load();


              $despesaCartao  =  DespesaCartao::where('cpf', 'like', $params['cpf'])
                ->where('anoMes', '=', $params['anoMes'])->load();

               
              if ($item_folhas || $folha) {
                TFieldList::clear('my_field_list');
                $dataF = new stdClass;
             
               
                $dataF->evento_id = [];
                $dataF->valor = [];

                foreach ($item_folhas as $item) {

                  //NÃO ENTRAR PARCELAS DE CARTÃO
                  $evento = Evento::where('id', '=', $item->evento_id)
                    ->where('cartao', '=', ' ')->first();

                  if (@$evento->id == $item->evento_id) {
                    TFieldList::addRows('my_field_list', 1);

                
                      $dataF->evento_id[] = $item->evento_id;
                      $dataF->valor[] = $item->valor;

    
                  }
                  
                 
                }

                foreach ($despesaCartao as $despesas){
                  if($despesas){
                    TFieldList::addRows('my_field_list', 1);
                    
                    $evento = Evento::where('cartao','=', $despesas->id_cartao_credito)->first();

                    TToast::show('info', 'Despesa de cartão encontrado');
                    $dataF->evento_id[] =  $evento->id;
                    $dataF->valor[] = $despesas->valor_total;
                   
                  }
                }
                
                TForm::sendData('my_form',  $dataF,  false, true, 300);

                TForm::sendData('my_form', (object) ['vl_salario' => $folha->vl_salario]);
                TForm::sendData('my_form', (object) ['vl_despesa' => $folha->vl_despesa]);
                TForm::sendData('my_form', (object) ['id' => '']);
              } else {
                TFieldList::clear('my_field_list');
              }
            }
          } else {
            TFieldList::clear('my_field_list');
            TToast::show('info', 'Não existe folha para esse mês');
            TFieldList::disableField('my_field_list');
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

  public static function onCheckCPF($param)
  {
    TTransaction::open('sample');

    $repo1 = new TRepository('Folha');
    $criteria = new TCriteria;

    if ($param['anoMes']) {
      $criteria->add(new TFilter('anoMes', 'like', $param['anoMes']));
      $criteria->add(new TFilter('tp_folha', '=', $param['tp_folha']));


      $folhas = $repo1->load($criteria);

      if ($folhas) {
        TFieldList::clear('my_field_list');

        $folhasF = Folha::where('anoMes', 'like', $param['anoMes'])
          ->where('tp_folha', '=', $param['tp_folha'])->load();
        $options = array();

        $options[''] = 'Selecione uma opção';

        foreach ($folhasF as $item) {
          $options[$item->cpf] = $item->cpf;
        }
        TCombo::reload('my_form', 'cpf', $options);
      } else {
        TFieldList::clear('my_field_list');

        TCombo::reload('my_form', 'cpf', '');
      }
    }

    TTransaction::close();
  }
}
