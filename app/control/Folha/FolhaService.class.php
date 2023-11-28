
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

class FolhaService
{
  public static function onCheckCPF($param)
  {
    TTransaction::open('sample');
    $repo1 = new TRepository('Folha');
    $criteria = new TCriteria;
    $folhaService = new FolhaService();

    if ($param['cpf']) {
      $criteria->add(new TFilter('cpf', 'like', $param['cpf']));



      $folhas = $repo1->load($criteria);

      if ($folhas) {

        $folhas = Folha::where('cpf', 'like', $param['cpf'])->orderBy(1)->load();

        $parcelasUnicas = new \Ds\Set();  // Certifique-se de instanciar o conjunto antes de utilizá-lo

        // Verificar se o CPF contém parcelas
        foreach ($folhas as $folha) {
            $itemfolha = ItemFolha::where('folha_id', '=', $folha->id)
                ->orderBy('parcela', 'ASC')->LOAD();
            
            foreach ($itemfolha as $item) {
                if (isset($item->parcela)) {
                    $parcela = $item->parcela;
                    $partesParcela = explode('/', $parcela);
                    $ultimoDigito = end($partesParcela);
                    $primeiroDigito = reset($partesParcela);

                    // Transforme $parcelas em um conjunto
                    $parcelasSet = new \Ds\Set([$primeiroDigito]);
        
                    // Verifica o status das parcelas e exibe o resultado
                    $resultadoVerificacao = $folhaService->verificarStatusParcelas($parcelasSet, $ultimoDigito);
                    TToast::show('info', "o Evento: ". $item->evento_id ." " .$resultadoVerificacao);
        
                    break;
                }
            }
        }
        




        $anoMesUtilizados = [];

        foreach ($folhas as $folha) {
          $anoMesExistentes = explode(',', $folha->anoMes);
          $anoMesUtilizados = array_merge($anoMesUtilizados, $anoMesExistentes);
        }

        $anoMesTodos = AnoMes::orderBy(1)->load();
        //$anoMesTodos = AnoMes::where('id','<>','999999')->load();

        $anoMesTodosArray = array_column($anoMesTodos, 'descricao');

        $anoMesNaoUtilizados = array_diff($anoMesTodosArray, $anoMesUtilizados);

        $options = array_combine($anoMesNaoUtilizados, $anoMesNaoUtilizados);
        TCombo::reload('form_folha', 'anoMes', $options);
      } else {


        $anoMes = AnoMes::where('descricao', '<>', '999999')->load();

        $options = array();
        if ($anoMes) {
          foreach ($anoMes as $item) {
            $options[$item->descricao] = $item->descricao;
          }
        }
        TCombo::reload('form_folha', 'anoMes', $options);
      }
    }


    TTransaction::close();
  }

  function verificarStatusParcelas($parcelasPorEvento, $quantidadeTotalParcelas)
{
    // Itera sobre as parcelas por evento
    foreach ($parcelasPorEvento as $evento_id => $ultimaParcela) {
        // Calcula a diferença entre a quantidade total e o último número de parcela para cada evento
        $diferencaParcelas = $quantidadeTotalParcelas[$evento_id] - $ultimaParcela;

        if ($diferencaParcelas > 0) {
            // Ainda há parcelas a serem criadas para o evento_id
            return "Ainda é necessário criar $diferencaParcelas parcela(s) para o evento_id $evento_id.";
        } elseif ($diferencaParcelas === 0) {
            // Todas as parcelas foram quitadas para o evento_id
            return  "Todas as parcelas para o evento_id $evento_id foram quitadas.";
        } else {
            // A quantidade total de parcelas é menor do que o último número de parcela para o evento_id (situação incomum)
            return "A quantidade total de parcelas para o evento_id $evento_id é menor do que o último número de parcela.";
        }
    }
}

  public static function onFormula($param)
  {
    try {
      TTransaction::open('sample');

      if ($param['formula']) {
        $folhaService = new FolhaService();

        $expressao = preg_replace('/[^0-9+\-.*\/()\sSP]/', '', $param['formula']);

        $salario = 0;

        if (isset($param['eventos_list_evento_id'])) {
          foreach ($param['eventos_list_evento_id'] as $key => $evento) {
            if ($evento == 2) {
              $salario = $param['eventos_list_valor'][$key];
            }
          }
        }

        // Mapeia eventos para valores
        $eventos = [
          'S' => $salario,
          'P' => $folhaService->calcularINSS($salario) //INSS
        ];

        TForm::sendData('form_folha', (object) ['valor' =>  $eventos['P']]);

        // Substitui os eventos pelos valores correspondentes na expressão
        foreach ($eventos as $evento => $valor) {
          $expressao = str_replace($evento, $valor, $expressao);
        }

        // Avalia a expressão
        $resultado = eval("return $expressao;");


        // Feedback ao usuário
        // TToast::show('info', "Resultado do cálculo: $resultado");

      } else {
        //TToast::show('info', 'Sem formula');

      }


      TTransaction::close();
    } catch (ParseError $e) {
      // Manipule erros de análise (se houver)
      TToast::show('error', "Erro de análise: " . $e->getMessage());
    } catch (Exception $e) {
      // Manipule outros erros
      TToast::show('error', "Erro ao calcular: " . $e->getMessage());
    }
  }

  function calcularINSS($salario)
  {
    $faixas = [
      ['limite_inferior' => 0, 'limite_superior' => 1320, 'aliquota' => 0.075], //Faixa 1
      ['limite_inferior' => 1320.01, 'limite_superior' => 2571.29, 'aliquota' => 0.09],  //Faixa 2
      ['limite_inferior' => 2571.30, 'limite_superior' => 3856.94, 'aliquota' => 0.12], //Faixa 3
      ['limite_inferior' => 3856.95, 'limite_superior' => 7507.49, 'aliquota' => 0.14], //Faixa 4
      // Adicione mais faixas conforme necessário
    ];

    $contribuicao_total = 0;

    foreach ($faixas as $faixa) {
      // Calcula a contribuição para cada faixa
      $contribuicao_faixa = max(0, min($faixa['limite_superior'], $salario) - $faixa['limite_inferior']) * $faixa['aliquota'];

      // Adiciona à contribuição total
      $contribuicao_total += $contribuicao_faixa;
    }

    return $contribuicao_total;
  }
}
