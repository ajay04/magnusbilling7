<?php
/**
 * Actions of module "GroupUser".
 *
 * MagnusBilling <info@magnusbilling.com>
 * 15/04/2013
 */

class GroupUserController extends Controller
{
    public $attributeOrder          = 't.id';
    public $titleReport             = 'GroupUser';
    public $subTitleReport          = 'GroupUser';
    public $nameModelRelated        = 'GroupModule';
    public $extraFieldsRelated      = array('show_menu', 'action', 'id_module', 'createShortCut', 'createQuickStart');
    public $extraValuesOtherRelated = array('idModule' => 'text');
    public $nameFkRelated           = 'id_group';
    public $nameOtherFkRelated      = 'id_module';
    public $extraValues             = array('idUserType' => 'name');

    public $filterByUser = false;

    public function init()
    {
        $this->instanceModel        = new GroupUser;
        $this->abstractModel        = GroupUser::model();
        $this->abstractModelRelated = GroupModule::model();
        parent::init();
    }

    public function actionGetUserType()
    {
        $filter       = isset($_POST['filter']) ? $_POST['filter'] : null;
        $this->filter = $filter ? $this->createCondition(json_decode($filter)) : $this->defaultFilter;

        $modelGroupUser = $this->abstractModel->find(array(
            'condition' => $this->filter,
            'params'    => $this->paramsFilter,
        ));

        echo json_encode(array(
            $this->nameRoot => $modelGroupUser->id_user_type == 1 ? true : false,
        ));
    }

    public function actionIndex()
    {
        $filter       = isset($_POST['filter']) ? $_POST['filter'] : null;
        $this->filter = $filter ? $this->createCondition(json_decode($filter)) : $this->defaultFilter;
        //AND t.id_user_type = 2
        $modelGroupUser = $this->abstractModel->findAll(array(
            'condition' => $this->filter,
            'params'    => $this->paramsFilter,
        ));
        $ids = array();

        foreach ($modelGroupUser as $value) {
            $ids[] = $value->id;
        }

        echo json_encode(array(
            $this->nameRoot => $ids,
        ));
    }

    public function actionClone()
    {
        if (!Yii::app()->session['isAdmin']) {
            exit;
        }

        $success          = false;
        $this->msgSuccess = 'invalid group';
        if (isset($_POST['id'])) {
            $modelGroupUser = $this->abstractModel->findByPk((int) $_POST['id']);
            if (count($modelGroupUser)) {
                $this->instanceModel->name         = $modelGroupUser->name . ' Cloned';
                $this->instanceModel->id_user_type = $modelGroupUser->id_user_type;
                $this->instanceModel->save();
                $newGroupId = $this->instanceModel->id;

                $modelGroupModule = $this->abstractModelRelated->findAll('id_group = :key', array(':key' => $modelGroupUser->id));
                foreach ($modelGroupModule as $groupModule) {
                    $modelGroupModuleNew             = new GroupModule();
                    $modelGroupModuleNew->attributes = $groupModule->getAttributes();
                    $modelGroupModuleNew->id_group   = $newGroupId;

                    try {
                        $success = $modelGroupModuleNew->save();
                    } catch (Exception $e) {
                        $this->msgSuccess = $this->getErrorMySql($e);
                    }
                }
            }

            if ($success) {
                $info = 'Group ' . $this->instanceModel->name;
                MagnusLog::insertLOG(4, $info);
            }

        }
        echo json_encode(array(
            $this->nameSuccess => $success,
            $this->nameMsg     => $this->msgSuccess,
        ));
    }
}
