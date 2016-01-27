<?php

// Necessário para herdar métodos padrão
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_NewsController extends __Controller_Action
{
    public function indexAction()
    {
        $id = $this->_getParam('id');
        $newsTipo = self::getTipo();

        if ($id) {
            // Irá cachear uma página diferente para cada registro
            Jp7_Cache_Output::getInstance()->start((string) $id);

            $record = $newsTipo->findById($id, [
                'fields' => ['*', 'date_publish'],
            ]);
            if (!$record) {
                $this->_redirect($newsTipo->getUrl());
            }
            $record->subitens = $record->getSubitens([
                'fields' => ['*'],
            ]);
            /*
            $record->files = $record->getArquivosParaDownload(array(
                'fields' => array('name', 'file')
            ));
            */
            self::setRecord($record);
        } else {
            $archive = $this->_getParam('archive');

            // Irá cachear uma página diferente para cada registro
            Jp7_Cache_Output::getInstance()->start($archive.$this->_getParam('p_page'));

            $options = [
                'fields' => ['*', 'date_publish'],
            ];
            if ($archive) {
                $archiveArr = array_map('intval', explode('/', $archive));
                if (checkdate($archiveArr[1], 1, $archiveArr[0])) {
                    $this->view->archive = new Jp7_Date($archiveArr[0].'-'.$archiveArr[1].'-01');
                    $options['where'][] = 'YEAR(date_publish) = '.$archiveArr[0];
                    $options['where'][] = 'MONTH(date_publish) = '.$archiveArr[1];
                }
            }

            global $config;
            $paginationClassName = ucfirst($config->name_id.'_Pagination');
            if (class_exists($paginationClassName)) {
                $pagination = new $paginationClassName([
                    'records' => $newsTipo->count($options),
                ]);
            } else {
                $pagination = new Pagination([
                    'records' => $newsTipo->count($options),
                    'next_char' => 'Próxima',
                    'back_char' => 'Anterior',
                    'show_first_and_last' => true,
                ]);
            }

            $this->view->introductionItens = [];
            if (!$this->view->archive && $pagination->page == 1) {
                // Introdução na primeira página (Menos em página de Arquivo Mensal)
                if ($introductionTipo = $newsTipo->getFirstChildByModel('Introduction')) {
                    $this->view->introductionItens = $introductionTipo->find([
                        'fields' => '*',
                    ]);
                }
            }

            $this->view->news = $newsTipo->find($options + ['limit' => $pagination]);
            $this->view->pagination = $pagination;
        }
    }
}
