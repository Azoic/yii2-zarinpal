<?php

namespace vendor\amirasaran\zarinpal\controllers;

use vendor\amirasaran\zarinpal\models\Payment;
use vendor\amirasaran\zarinpal\models\PaymentSearch;
use Yii;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * PaymentController implements the CRUD actions for Payment model.
 */
class PaymentController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class'   => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Payment models.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PaymentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Payment model.
     *
     * @param int $id
     *
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Payment model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Payment();

        if ($model->load(Yii::$app->request->post())) {
            $model->ip = $_SERVER['REMOTE_ADDR'];
            $model->status = Payment::STATUS_WAITING;
            $res = $model->createPayment($this);
            if ($res->Status == 100) {
                $model->authority = $res->Authority;
                $model->save();

                return $this->render('waiting', ['model' => $model]);
            } else {
                throw new BadRequestHttpException('Can Not Connect To Zarinpal Bad Request !');
            }
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Payment model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param int $id
     *
     * @return mixed
     */
    public function actionPay($id)
    {
        $model = $this->findModel(['id' => $id, 'status' => Payment::STATUS_WAITING]);

        $res = $model->createPayment($this);

        if ($res->Status == 100) {
            $model->authority = $res->Authority;
            $model->save();

            return $this->render('waiting', ['model' => $model]);
        } else {
            throw new BadRequestHttpException('Can Not Connect To Zarinpal Bad Request !');
        }
    }

    public function actionVerify($Authority, $Status)
    {
        $model = $this->findModel(['authority' => $Authority, 'status' => Payment::STATUS_WAITING]);

        if ($Status == 'OK' || $Status == 'NOK') {
            $model->checkAuthority($this);
            $model->save();

            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Finds the Payment model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param int $id
     *
     * @throws NotFoundHttpException if the model cannot be found
     *
     * @return Payment the loaded model
     */
    protected function findModel($id)
    {
        if (($model = Payment::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
