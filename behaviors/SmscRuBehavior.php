<?php

namespace blackice\smscru\behaviors;

use yii;
use yii\base\Behavior;

class SmscRuBehavior extends Behavior
{

    public function init()
    {
        parent::init();
    }

    public static function confirmCode($phone)
    {
        $config = Yii::$app->params['smsc'];
        $code   = mt_rand(1,9).mt_rand(0,9).mt_rand(0,9).mt_rand(0,9).mt_rand(0,9).mt_rand(0,9);
        if ($config['test']) return ['code' => substr($code, (-1 * $config['digits']))];

        $operator = substr($phone, 0, 5);
        $configCall = ( $config['call'] && !in_array($operator, ['38063', '38073', '38093']) ) ? true : false;

        $params = [
            'fmt'       => 3, // json
            'charset'   => $config['charset'],
            'login'     => $config['login'],
            'psw'       => $config['password'],
            'phones'    => urlencode($phone),
            'call'      => ($configCall) ? 1 : 0
        ];
        $message  = ($configCall) ?  'code' : urlencode(\Yii::t('smsc.ru', 'confirm_code_txt').': '.$code);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $config['url']."?".http_build_query($params, '', '&')."&mes=".$message.'&param='.$config['param']);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        $result         = json_decode($result, true);
        $result['code'] = ($configCall) ? $result['code'] : $code;
        $result['code'] = substr($result['code'], (-1 * $config['digits']));

        return $result;
    }
}