<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EquipmentCategory
 *
 * @author alex
 */
require_once ('Controller.php');
require_once(INCLUDE_DIR . 'class.dynamic_forms.php');

class EquipmentItem extends Controller {

    protected function getEntityClassName() {
        return 'Equipment';
    }

    protected function getListTemplateName() {
        return 'categories_list.html.twig';
    }

    protected function getViewTemplateName() {
        return 'item_view.html.twig';
    }

    public function newAction($category_id) {
        $viewargs = array();
        if ($category_id > 0) {
            $category = Equipment_Category::lookup($category_id);
            $viewargs['category'] = $category;
            $this->viewAction(0, $viewargs);
        } else {
            $this->setFlash
                    ('error', 'Unable to create new item!', 'invalid category specified!');
            $this->viewAction(0);
        }
    }
    
    protected function defaultAction()
    {
        $category = Equipment_Category::lookup($_POST['category_id']);
        $viewargs['category'] = $category;
        $this->viewAction($_POST['id'], $viewargs);
    }

    public function getDynamicForm($id = 0) {
        $form_id = EquipmentPlugin::getCustomForm();
        if(isset($form_id))
        {
            $form = DynamicForm::lookup($form_id);
            if($id > 0)
            {
                $data = Equipment::getDynamicData($id);
                $one = $data->one();
                if( isset($one))
                {
                    $one->getSaved();
                    return $one->getForm()->render(true);
                }
            }
            if(isset($form))
            {
                return $form->getForm()->render(true);
            }
        }
    }

    public function publishAction() {
        $id = $_POST['item_id'];
        $result = false;
        if (isset($id)) {
            $equipment = new Equipment($id);
            $publish = $_POST['item_publish'];
            if ($publish) {
                $result = $equipment->publish();
            } else {
                $result = $equipment->unpublish();
            }
        }
        if ($result) {
            $this->setFlash('info', 'Success', 'Item Updated!');
        } else {
            $this->setFlash('error', 'Error', 'Failed to Update Item!');
        }
    }

    public function activateAction() {
        $id = $_POST['item_id'];
        $result = false;
        if (isset($id)) {
            $equipment = new Equipment($id);
            $activate = $_POST['item_activate'];
            if ($activate) {
                $result = $equipment->activate();
            } else {
                $result = $equipment->retire();
            }
        }
        if ($result) {
            $this->setFlash('info', 'Success', 'Item Updated!');
        } else {
            $this->setFlash('error', 'Error', 'Failed to Update Item!');
        }
    }

    public function openTicketsJsonAction($item_id) {
        $tickets = $this->ticketsAction('open', $item_id);
        echo json_encode($tickets);
    }

    public function closedTicketsJsonAction($item_id) {
        $tickets = $this->ticketsAction('closed', $item_id);
        echo json_encode($tickets);
    }

    private function ticketsAction($type, $item_id) {
        $ticket_id = Equipment::getTicketList($type, $item_id);
        $tickets = array();
        foreach ($ticket_id as $id) {
            $ticket = Ticket::lookup($id['ticket_id']);
            $equipment = new Equipment($id['equipment_id']);
            if (isset($ticket) && isset($equipment)) {
                $ticket_data = array(
                    'id' => $ticket->getId(),
                    'number' => $ticket->getNumber(),
                    'equipment' => $equipment->getAssetId(),
                    'create_date' => Format::db_datetime($ticket->getCreateDate()),
                    'subject' => $ticket->getSubject(),
                    'name' => $ticket->getName()->getFull(),
                    'priority' => $ticket->getPriority(),
                );

                if ($type == 'closed') {
                    $ts_open = strtotime($ticket->getCreateDate());
                    $ts_closed = strtotime($ticket->getCloseDate());
                    $ticket_data['close_date'] = Format::db_datetime($ticket->getCloseDate());
                    $ticket_data['closed_by'] = $ticket->getStaff()->getUserName();
                    $ticket_data['elapsed'] = Format::elapsedTime($ts_closed - $ts_open);
                }

                $tickets[] = $ticket_data;
            }
        }
        return $tickets;
    }
}