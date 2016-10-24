<?php

namespace app\modules\client\controllers;

use app\modules\client\models\SearchApiInvoices;
use app\modules\user\models\User;
use Yii;
use yii\filters\AccessControl;
use app\modules\client\models\Message;
use app\modules\client\models\ServerSettings;
use app\modules\client\models\Invoices;
use app\lightspeedapi\LightspeedApi;
use app\modules\admin\models\SearchLog;
use app\components\Helpers;
use yii\db\Query;

class ApiController extends \yii\web\Controller
{

    /**
     *
     * @return array
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'roles' => ['client'],
                        'actions' => ['index', 'invoices', 'logs', 'check-for-invoices'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['save-invoices'],
                        'allow' => true,
                        'roles' => [],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * /client/api/save-invoices
     *
     * @return string
     */
    public function actionSaveInvoices()
    {
        $users = $this->getUsers();
        $model = new ServerSettings();

        $date = date("Y-m-d", time() - 60 * 60 * 24);
                $date = '2015-10-01';
        $filter = "(date_created = \"$date\")";

        foreach($users as $user){
            $userId = $user->id;

            $settings = $model->find()->where('user_id=:user_id', array(':user_id'=> $userId))->one();

            if($settings !== null){
                $server_address       = $settings->server_address;
                $server_username      = $settings->server_username;
                $server_password      = $settings->server_password;
                $server_port_number   = $settings->server_port_number;
            }else{
                return $this->redirect('/client/server-settings/create', 302);
            }
            $api = new LightspeedApi($server_address, $server_username, $server_password, $server_port_number);

            $this->_saveInvoices($api, $filter, $user);
        }

        return 'Success';
    }

    public function actionCheckForInvoices(){
        $Querydate = Yii::$app->request->getQueryParam('date');
        $user= Yii::$app->user->identity;

        $userId = $user->id;

        $model = new ServerSettings();
        $date = $Querydate !== '' ? $Querydate : date("Y-m-d", time());

        $filter = "(date_created = \"$date\")";

        $settings = $model->find()->where('user_id=:user_id', array(':user_id'=>$user->id))->one();

        if($settings !== null){
            $server_address       = $settings->server_address;
            $server_username      = $settings->server_username;
            $server_password      = $settings->server_password;
            $server_port_number   = $settings->server_port_number;
        }else{
            return $this->redirect('/client/server-settings/create', 302);
        }
        $api = new LightspeedApi($server_address, $server_username, $server_password, $server_port_number);

        $this->_saveInvoices($api, $filter, $user);
        return $this->redirect('/client/invoices/index?SearchInvoices%5Bdatetime_created%5D='.$date);
    }


    private function _saveInvoices($api, $filter, $user){
        $api->getObject('invoices', $filter);
        if($api->isAuthFailed()){
            // Send an email to the client to let them know that we were unable to connect to their database
            $to = $user->email;
            $this->emailToClient($to, $user);
        }else{
            $arrayInvoices = $api->getArrayResponse();

            $countSuccess = count($arrayInvoices);

            foreach($arrayInvoices as $invoice){
                // If customer id is set
                if(isset($invoice['customer_id']) && $invoice['customer_id'] != null){
                    // extract email from feedback info
                    $matches = array();
                    $pattern = '/[a-z\d._%+-]+@[a-z\d.-]+\.[a-z]{2,4}\b/i';
                    preg_match_all($pattern,$invoice['contact_info'],$matches);

                    $invoice['email'] = isset($matches[0][0]) ?  $matches[0][0] : null;
                    $invoice['user_id'] =   $user->id;

                    $invoiceModel = new Invoices();
                    $invoiceModel->setAttributes($invoice);

                    if( !$invoiceModel::find()->where( [ 'invoice_id' => $invoice['invoice_id'] ] )->exists()){
                         $invoiceModel->save();
                    }

                    if($invoice['email'] !== null && $user->status == User::STATUS_ACTIVE){
                        // Send an email to the customer.
                        $inv = $invoiceModel::findOne([ 'invoice_id' => $invoice['invoice_id'] ]);

                        if(!$inv->email_sent){
                            $success = $this->emailToCustomer($user->email, $invoice['email'], $invoice,  $user->id);
                            if($success){
                                // Update invoice email status
                                $inv->email_sent = true;
                                $inv->save();
                                // Log an email
                                Helpers::logEvent("An email was sent to ". $invoice['mainname'] ." <". $invoice['email'] . "> for " .$invoice['invoice_id'],  $user->id);
                            }
                        }
                    }
                }else{
                    // Customer id not set
                    $countSuccess--;
                    // Log an event
                    Helpers::logEvent('Invoice ' .$invoice['invoice_id'] . ' is not downloaded. Customer id doesn\'t set.', $user->id);
                }
            }
            if($api->hasError()){
                $api->logEvents(false, 0,  $user->id);
            }else{
                // logging successfully downloaded invoices
                $api->logEvents(true, $countSuccess,  $user->id);
            }
        }
    }

    /***
     *
     * @return \yii\web\Response
     */
    public function actionInvoices(){
        $searchModel = new SearchApiInvoices();

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('invoices',
            [
            'searchModel' => $searchModel,
            'provider' => $dataProvider,
            'forDate' => isset(Yii::$app->request->queryParams["SearchApiInvoices"]['datetime_created']) ? Yii::$app->request->queryParams["SearchApiInvoices"]['datetime_created'] : date("Y/m/d", time()),
            ]);
    }

    public function actionLogs(){

            $searchModel = new SearchLog();
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

            return $this->render('logs', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);
    }

    private function emailToCustomer($from, $to, $invoice, $userId){
        $messageModel = new Message();

        $defaultMessage = $messageModel->find()->where(['user_id' => $userId, 'default' => true ])->one();

        if($defaultMessage == null){
            $message = $messageModel->find()->where(['user_id' => $userId])->one();
        }else{
            $message = $defaultMessage;
        }

        if($message !== null){

            if((bool)$message->ignore_negative){
                if($invoice['total'] < 0){
                    //Log the event
                    Helpers::logEvent( "Invoice " .$invoice['invoice_id']." was skipped because the total was negative. An email was not sent.", $userId);
                    return false;
                }
            }
            return \Yii::$app->mailer->compose('CustomerInvoice', ['invoice' => $invoice, 'message' => $message->message])
                ->setFrom($from)
                ->setTo($to)
                ->setSubject($message->subject)
                ->send();
        }

        return false;
    }

    private function emailToClient($to, $user){
        return \Yii::$app->mailer->compose('ClientNotification', ['user' => $user ])
                ->setFrom(\Yii::$app->params['supportEmail'])
                ->setTo($to)
                ->setSubject(\Yii::$app->name . ' notification')
                ->send();
    }

    private function getUsers(){
        $model = new User();

        $users = $model->find()
            ->join('LEFT JOIN', 'server_settings', 'user.id = server_settings.user_id')
            ->where(['not', ['server_settings.server_address' => null]])
            ->all();

        return $users;
    }
}
