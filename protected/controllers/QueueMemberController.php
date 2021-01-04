<?php
/**
 * Acoes do modulo "QueueMember".
 *
 * =======================================
 * ###################################
 * MagnusBilling
 *
 * @package MagnusBilling
 * @author Adilson Leffa Magnus.
 * @copyright Copyright (C) 2005 - 2021 MagnusSolution. All rights reserved.
 * ###################################
 *
 * This software is released under the terms of the GNU Lesser General Public License v2.1
 * A copy of which is available from http://www.gnu.org/copyleft/lesser.html
 *
 * Please submit bug reports, patches, etc to https://github.com/magnusbilling/mbilling/issues
 * =======================================
 * Magnusbilling.com <info@magnusbilling.com>
 * 17/08/2012
 */

class QueueMemberController extends Controller
{
    public $attributeOrder = 'id';
    public $extraValues    = array('idUser' => 'username');

    public $fieldsFkReport = array(
        'id_user' => array(
            'table'       => 'pkg_user',
            'pk'          => 'id',
            'fieldReport' => 'username',
        ),
    );

    public function init()
    {
        $this->instanceModel = new QueueMember;
        $this->abstractModel = QueueMember::model();
        $this->titleReport   = Yii::t('zii', 'Queue Member');
        parent::init();
    }

    public function beforeSave($values)
    {
        $this->checkRelation($values);

        if (isset($values['interface'])) {
            $modelSip = Sip::model()->find("id = :id OR name = :id",
                array('id' => $values['interface'])
            );

            $values['id_user']   = $modelSip->id_user;
            $values['interface'] = 'SIP/' . $modelSip->name;
        }
        if (isset($values['queue_name'])) {
            $modelQueue = Queue::model()->find("id = :id OR name = :id",
                array('id' => $values['queue_name'])
            );
            $values['queue_name'] = $modelQueue->name;
        }

        return $values;
    }

    public function checkRelation($values)
    {

        if ($this->isNewRecord) {

            $modelSip   = Sip::model()->findByPk((int) $values['interface']);
            $modelQueue = Queue::model()->findByPk((int) $values['queue_name']);

            if ($modelSip->id_user != $modelQueue->id_user) {
                echo json_encode(array(
                    'success' => false,
                    'rows'    => array(),
                    'errors'  => ['interface' => ['The SIP ACCOUNT must belong to the QUEUE owner']],
                ));
                exit;
            }

        } else {
            if (isset($values['id'])) {

                $modelQueueMember = QueueMember::model()->findByPk((int) $values['id']);

                $modelSip   = Sip::model()->findByPk((int) $values['interface']);
                $modelQueue = Queue::model()->find('name = :key', [':key' => $modelQueueMember['queue_name']]);

                if ($modelSip->id_user != $modelQueue->id_user) {
                    echo json_encode(array(
                        'success' => false,
                        'rows'    => array(),
                        'errors'  => ['interface' => ['The SIP ACCOUNT must belong to the QUEUE owner']],
                    ));
                    exit;
                }
            }
        }
    }

    public function generateQueueFile()
    {

        $select = '`name`, `language`, `musiconhold`, `announce`, `context`, `timeout`, `announce-frequency`, `announce-round-seconds`, `announce-holdtime`, `announce-position`, `retry`, `wrapuptime`, `maxlen`, `servicelevel`, `strategy`, `joinempty`, `leavewhenempty`, `eventmemberstatus`, `eventwhencalled`, `reportholdtime`, `memberdelay`, `weight`, `timeoutrestart`, `periodic-announce`, `periodic-announce-frequency`, `ringinuse`, `setinterfacevar`, `setqueuevar`, `setqueueentryvar`';
        $model  = Queue::model()->findAll(
            array(
                'select' => $select,
            ));

        if (count($model)) {
            AsteriskAccess::instance()->writeAsteriskFile($model, '/etc/asterisk/queues_magnus.conf', 'name');
        }

    }
    public function afterSave($model, $values)
    {
        $this->generateQueueFile();
    }
    public function afterUpdateAll($strIds)
    {
        $this->generateQueueFile();
        return;
    }

    public function afterDestroy($values)
    {
        $this->generateQueueFile();
    }

}
