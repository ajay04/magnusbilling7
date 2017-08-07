<?php

/**
 * Url for customer register http://ip/billing/index.php/user/add .
 */
class BuyCreditController extends BaseController
{

    public function actionMethod()
    {

        if (isset($_GET['l'])) {
            $data           = explode('|', $_GET['l']);
            $user           = $data[0];
            $pass           = $data[1];
            $_GET['amount'] = $data[2];

            $modelSip = $this->remoteLogin($user, $pass);

            if (!count($modelSip)) {
                echo 'User or password is invalid';
                exit;
            }
            $methodPay         = Methodpay::model()->find('payment_method = :key', array(':key' => 'Paypal'));
            $_GET['id_method'] = $methodPay->id;
        } else {
            $modelMethodPay = Methodpay::model()->findByPK((int) $_GET['id_method']);
        }

        if ($modelMethodPay->max > 0 && $_GET['amount'] > $modelMethodPay->max) {
            exit(Yii::t('yii', 'The maximum amount to') . ' ' . $modelMethodPay->show_name . ' ' . Yii::t('yii', 'is') . ' ' . Yii::app()->session['currency'] . ' ' . $modelMethodPay->max);
        } elseif ($modelMethodPay->min > 0 && $_GET['amount'] < $modelMethodPay->min) {
            exit(Yii::t('yii', 'The minimal amount to') . ' ' . $modelMethodPay->show_name . ' ' . Yii::t('yii', 'is') . ' ' . Yii::app()->session['currency'] . ' ' . $modelMethodPay->min);
        }

        $modelUser = User::model()->findByPk((int) Yii::app()->session['id_user']);

        if ($modelMethodPay->active == 0 || $modelMethodPay->id_user != Yii::app()->session['id_agent']) {
            exit('invalid option');
        }

        if ($modelMethodPay->payment_method == 'BoletoBancario') {
            $this->actionBoletoBancario();
        } else if ($modelMethodPay->payment_method == 'SuperLogica') {
            SLUserSave::criarBoleto($modelMethodPay, $modelUser);
        } else {
            $this->render(strtolower($modelMethodPay->payment_method), array(
                'modelMethodPay' => $modelMethodPay,
                'modelUser'      => $modelUser,
                'reference'      => date('YmdHis') . '-' . $modelUser->username . '-' . $modelUser->id,
            ));
        }
    }

    public function actionBoletoBancario()
    {
        $dataVencimento = date("Y-m-d ", mktime(0, 0, 0, date("m"), date("d") + 12, date("Y"))) . date('H:i:s');

        $modelBoleto              = new Boleto();
        $modelBoleto->id_user     = Yii::app()->session['id_user'];
        $modelBoleto->date        = date("Y-m-d H:i:s");
        $modelBoleto->description = 'Credito';
        $modelBoleto->status      = '0';
        $modelBoleto->payment     = $_GET['amount'];
        $modelBoleto->vencimento  = $dataVencimento;
        $modelBoleto->save();
        $idBoleto = $modelBoleto->getPrimaryKey();
        $this->redirect(
            array(
                'Boleto/secondVia',
                'id' => $idBoleto,
            ));

    }
    public function actionPayServiceLink()
    {

        $model    = new ServicesUse();
        $criteria = new CDbCriteria();
        if (isset($_GET['id_service_use'])) {
            $ids = json_decode($_GET['id_service_use']);
            $criteria->addCondition('status = 2');
        } else if (isset($_GET['id_user'])) {
            $criteriaUser = new CDbCriteria();
            $id_user      = (int) $_GET['id_user'];
            $criteriaUser->addCondition('id_user = :id_user');
            //$criteriaUser->addInCondition('reminded', array(2,3));
            $criteriaUser->params[':id_user'] = $id_user;
            $modelServicesUse                 = ServicesUse::model()->findAll($criteriaUser);
            $ids                              = array();
            foreach ($modelServicesUse as $key => $value) {
                $ids[] = $value->id;
            }

            $criteria->addCondition('status = 1');
            //$criteria->addCondition('reminded = 2 OR reminded= 3');
        }

        $criteria->addInCondition('id', $ids);

        $modelServicesUse = ServicesUse::model()->findAll($criteria);

        if (Yii::app()->session['isAdmin']) {
            $total = 0;
            foreach ($modelServicesUse as $key => $value) {
                $total += $modelServicesUse[0]->idServices->price;
                if ($value->id_user != $modelServicesUse[0]->id_user) {
                    $this->render('payservicelink', array(
                        'model'   => $model,
                        'message' => 'Your cannot process service payment of diferent users.',
                    ));
                    exit;
                }
            }

            if (!count($modelServicesUse)) {
                $this->render('payservicelink', array(
                    'model'   => $model,
                    'message' => 'This service was paid or canceled.',
                ));
                exit;
            } else if ($modelServicesUse[0]->idUser->credit >= $total) {
                ServicesProcess::activation(array(
                    'id_services' => $ids,
                    'id_user'     => (int) $modelServicesUse[0]->id_user,
                    'id_method'   => null,
                ));
                $this->render('payservicelink', array(
                    'model'   => $model,
                    'message' => 'Your services are actived!',
                ));

                return;
            } else {
                $this->render('payservicelink', array(
                    'model'   => $model,
                    'message' => 'User not have enogth credit to pay the services.',
                ));
                exit;
            }

        }

        if (!count($modelServicesUse)) {
            $this->render('payservicelink', array(
                'model'   => $model,
                'message' => 'Your selection not have any service pending.',
            ));
            exit;
        }

        if ($_POST) {

            $total = explode(" ", $_POST['ServicesUse']['total']);
            $total = $total[1];
            $total = preg_replace("/,/", '', $total[1]);
            if (isset($_POST['ServicesUse']['use_credit']) && $_POST['ServicesUse']['use_credit'] == 1) {

                if ($modelServicesUse[0]->idUser->credit >= $total) {
                    ServicesProcess::activation(array(
                        'id_services' => $ids,
                        'id_user'     => (int) Yii::app()->session['id_user'],
                        'id_method'   => (int) $_POST['ServicesUse']['id_method'],
                    ));
                    $this->render('payservicelink', array(
                        'model'   => $model,
                        'message' => 'Your services are actived!',
                    ));

                    return;
                } else {
                    $total -= $modelServicesUse[0]->idUser->credit;
                }
            }

            if ($_POST['ServicesUse']['id_method'] < 1) {
                $model->addError('id_method', Yii::t('yii', 'Group no allow for Agent users'));

            } else {

                if (isset($_GET['id_service_use'])) {
                    $link         = $_SERVER['HTTP_REFERER'] . "index.php/buyCredit/payServiceLink/?id_service_use=" . $_GET['id_service_use'];
                    $mail         = new Mail(Mail::$TYPE_SERVICES_PENDING, $modelServicesUse[0]->id_user);
                    $serviceNames = '';
                    foreach ($modelServicesUse as $key => $value) {
                        $serviceNames .= $value->idServices->name . ', ';
                    }

                    $mail->replaceInEmail(Mail::$SERVICE_NAME, $serviceNames);
                    $mail->replaceInEmail(Mail::$SERVICE_PRICE, $total);
                    $mail->replaceInEmail(Mail::$SERVICE_PENDING_URL, $link);
                    try {
                        @$mail->send();
                    } catch (Exception $e) {
                        //error SMTP
                    }
                }

                $modelMethodPay = Methodpay::model()->findByPk((int) $_POST['ServicesUse']['id_method']);
                $total          = $modelMethodPay->payment_method == 'Pagseguro' ? intval($total) : $total;

                $this->redirect(array(
                    'buyCredit/method',
                    'amount'    => $total,
                    'id_method' => (int) $_POST['ServicesUse']['id_method'],
                    'id_user'   => $modelServicesUse[0]->id_user,
                )
                );

            }
        }

        $modelMethodPay = Methodpay::model()->findAll('id_user = :key AND active = 1',
            array(':key' => $modelServicesUse[0]->idUser->id_user));

        $this->render('payservicelink', array(
            'model'            => $model,
            'modelMethodPay'   => $modelMethodPay,
            'modelServicesUse' => $modelServicesUse,
            'currency'         => Yii::app()->session['currency'],
        ));
    }
}
