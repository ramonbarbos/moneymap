
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

class DespesaCartaoService
{
  private static $saldos = [];


  public static function onCPFChange($params)
  {
    if (!empty($params['cpf'])) {
      try {
        TTransaction::open('sample');

        if ($params['anoMes'] && $params['cpf'] && $params['id_cartao_credito']) {


          $cartao = CartoesCredito::where('cpf', 'like', $params['cpf'])
                                 ->where('banco_associado', '=', $params['id_cartao_credito'])->first();

              //Validar banco
          if (@$cartao->cpf == $params['cpf'] && @$cartao->banco_associado == $params['id_cartao_credito']) {

            TFieldList::enableField('my_field_list');

            $despesa = DespesaCartao::where('cpf', '=', $params['cpf'])
                   ->where('id_cartao_credito', '=', $params['id_cartao_credito'])->first();

          

            if ($params['cpf'] ==  @$despesa->cpf &&  $params['id_cartao_credito'] == $despesa->id_cartao_credito&&  $params['anoMes'] == $despesa->anoMes) { 
              //Já existe despesa  com o CPF

              $item_despesas = ItemDespesaCartao::where('despesa_cartao_id', '=', $despesa->id)->orderBy(1)->load();

              $data = new stdClass;
              $data->id_item = [];
              $data->dt_despesa = [];
              $data->evento_id = [];
              $data->descricao = [];
              $data->valor = [];

              if (!empty($data->cpf)) {
                $data->anoMes = $despesa->anoMes;
                $data->cpf = $despesa->cpf;
              }
              $data->id = $despesa->id;
              $data->valot_total = $despesa->valot_total;


              foreach ($item_despesas as $item) {
                $dt_despesa_formatada = (new DateTime($item->dt_despesa))->format('d/m/Y');

                $data->id_item[] = $item->id_item;
                $data->dt_despesa[] = $dt_despesa_formatada;
                $data->evento_id[] = $item->evento_id;
                $data->descricao[] = $item->descricao;
                $data->valor[] = $item->valor;
                TFieldList::addRows('my_field_list', 1);
              }
              TForm::sendData('my_form_despesa_cartao', (object) $data, false, true, 200);

              TToast::show('info', 'Dados Encontrado.');
            } else if (!$cartao) { //Quando nao tiver folha encontrada
              TToast::show('info', 'Folha não encontrada');
            } else { //Quando tiver Folha mas não tem Despesa com o CPF

                  //verificar se existe desconto vinculado ao cpf
                  $folha  =  Folha::where('cpf', 'like', $params['cpf'])
                  ->where('anoMes', '=', $params['anoMes'])->first();

                $item_folhas = ItemFolha::where('folha_id', '=', $folha->id)
                  ->where('parcela', '<>', ' ')
                  ->where('tipo', 'like', 'D')->orderby(1)
                  ->load();

                  if ($item_folhas || $folha) {
                    TFieldList::clear('my_field_list');
    
                    $dataF = new stdClass;
                    $dataF->evento_id = [];
                    $dataF->valor = [];
    
                    foreach ($item_folhas as $item) {
                      TFieldList::addRows('my_field_list', 1);
    
                      $dataF->evento_id[] = $item->evento_id;
                      $dataF->valor[] = $item->valor;
                    }
                    TForm::sendData('my_form_despesa_cartao',  $dataF,  false, true, 300);
    
                    TForm::sendData('my_form_despesa_cartao', (object) ['id' => '']);
                    TForm::sendData('my_form_despesa_cartao', (object) ['valor_total' => '']);
                  } else {
                    TFieldList::clear('my_field_list');
                  }
              
              TFieldList::clear('my_field_list');
              
             
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

    $repo1 = new TRepository('CartoesCredito');
    $criteria = new TCriteria;

    if ($param['id_cartao_credito']) {
      $criteria->add(new TFilter('id', '=', $param['id_cartao_credito']));


      $folhas = $repo1->load($criteria);

      if ($folhas) {
        TFieldList::clear('my_field_list');

        $folhasF = CartoesCredito::where('id', '=', $param['id_cartao_credito'])->load();
        $options = array();

        $options[''] = 'Selecione uma opção';

        foreach ($folhasF as $item) {
          $options[$item->cpf] = $item->cpf;
        }
        TCombo::reload('my_form_despesa_cartao', 'cpf', $options);
      } else {
        TFieldList::clear('my_field_list');

        TCombo::reload('my_form_despesa_cartao', 'cpf', '');
      }
    }

    TTransaction::close();
  }
}
