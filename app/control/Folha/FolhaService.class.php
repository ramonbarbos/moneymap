
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

    if ($param['cpf']) {
      $criteria->add(new TFilter('cpf', 'like', $param['cpf']));


      $folhas = $repo1->load($criteria);

      if ($folhas) {

        $folha = Folha::where('cpf', '=', $param['cpf'])->first();


        $anoMes = AnoMes::where('descricao', '<>', $folha->anoMes)
          ->load();
        $options = array();
        if ($anoMes) {
          foreach ($anoMes as $item) {
            $options[$item->id] = $item->descricao;
          }
        }
        TCombo::reload('form_folha', 'anoMes', $options);
      } else {

        $anoMes = AnoMes::where('descricao', '<>', '999999')
          ->load();
        $options = array();
        if ($anoMes) {
          foreach ($anoMes as $item) {
            $options[$item->descricao] = $item->descricao;
          }
        }
        TCombo::reload('form_folha', 'anoMes', $options);
        TToast::show('info', 'CPF sem folha no mês');
      }
    }

    TTransaction::close();
  }

  public static function onCityChange($param)
  {
    if (!empty($param['anoMes'])) {
      TTransaction::open('sample');

      $folhas = Folha::where('anoMes', 'like', $param['anoMes'])->load();

      if($folhas){

        $data = new stdClass;
        $data->cpf = [];

        foreach ($folhas as $folha) {
         $data->cpf[] = $folha->cpf;
        TToast::show('info', $folha->cpf);

        TForm::sendData('my_form', (object) $data);

        }

      }
     

      //
      TTransaction::close();
    }
  }
}