<?php
namespace Adianti\Widget\Dialog;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;

use Exception;

/**
 * Toast
 *
 * @version    7.5
 * @package    widget
 * @subpackage dialog
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TToast
{
    /**
     * Class Constructor
     * @param $message Message to be shown
     */
    public static function show($type, $message, $place = 'bottom center', $icon = null)
    {
        if (in_array($type, ['show', 'info', 'success', 'warning', 'error']))
        {
            $message64 = base64_encode(mb_convert_encoding($message,"UTF-8"));
            TScript::create("__adianti_show_toast64('{$type}', '{$message64}', '{$place}', '{$icon}')");
        }
        else
        {
            throw new Exception(AdiantiCoreTranslator::translate('Invalid parameter (^1) in ^2', $type, __METHOD__));
        }
    }
}
