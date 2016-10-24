<?php

namespace app\modules\admin\controllers;

use Yii;
use app\modules\user\models\User;
use yii\base\ErrorException;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\modules\client\models\ServerSettings;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => [],
                        'allow' => true,
                        'roles' => ['admin'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => User::find()->leftJoin('auth_assignment', '`auth_assignment`.`user_id` = `user`.`id`')
                ->where(['`auth_assignment`.`item_name`' => 'client'])
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single User model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {

        $serverSettingsModel = ServerSettings::find()->where('user_id=:user_id', array(':user_id'=>$id))->one();
        if($serverSettingsModel === null){

            $serverSettingsModel = new ServerSettings();
        }
        return $this->render('view', [
            'model' => $this->findModel($id),
            'serverSettingsModel' => $serverSettingsModel,
        ]);
    }

    /**
     * Sets user status to 0 (Active)
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionActivate($id){
        $user = $this->findModel($id);
        $user->status = User::STATUS_ACTIVE;

        if ($user->save()) {
            return $this->redirect('/admin/user/');
        }
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $user = new User();

        $serverSettingsModel = new ServerSettings();

        if ($user->load(Yii::$app->request->post())) {
            $randomPassword  =Yii::$app->security->generateRandomString(8);
            $user->setPassword($randomPassword);
            $user->status = User::STATUS_ACTIVE;
            $user->generateAuthKey();
            $user->generateEmailConfirmToken();

            if ($user->save()) {
                $auth = \Yii::$app->authManager;
                $authorRole = $auth->getRole('client');
                $auth->assign($authorRole, $user->getId());

                $serverSettingsModel->user_id = $user->getId();

                if ($serverSettingsModel->load(Yii::$app->request->post()) && $serverSettingsModel->save()) {
                    Yii::$app->mailer->compose('changeAotuPassword', ['user' => $user, 'password' => $randomPassword])
                        ->setFrom([\Yii::$app->params['supportEmail'] => Yii::$app->name])
                        ->setTo($user->email)
                        ->setSubject('Created new account for ' . Yii::$app->name)
                        ->send();

                    return $this->redirect(['/admin/user']);
                }else{
                    throw new ErrorException();
                }
            }
        }

        return $this->render('create', [
            'model' => $user,
            'serverSettingsModel' => $serverSettingsModel,
            'statuses' => $user->getStatusesArray(),
        ]);
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $serverSettingsModel = ServerSettings::find()->where('user_id=:user_id', array(':user_id'=>$id))->one();

        if($serverSettingsModel === null){
            $serverSettingsModel = new ServerSettings();
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $serverSettingsModel->user_id = $id;
            if ($serverSettingsModel->load(Yii::$app->request->post()) && $serverSettingsModel->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }else{

            }
        } else {
            return $this->render('update', [
                'model' => $model,
                'statuses' => $model->getStatusesArray(),
                'serverSettingsModel' =>  $serverSettingsModel
            ]);
        }
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
