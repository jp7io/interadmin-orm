<?php
/*
LARAVEL 4
*/
namespace Jp7\Former;

use Jp7\Interadmin\Record;
use Request;
use Redirect;
use Validator;
use Input;
use Route;
use Exception;

/**
 * Handles validation and redirection.
 */
class FormRequest
{
    protected $validator;

    protected $input;
    protected $model;

    public function __construct(Record $model)
    {
        $this->model = $model;
    }

    public function save()
    {
        if (!$this->validator()->fails()) {
            $backupLogUser = Record::setLogUser('site - '.Request::header('user-agent'));

            $saved = $this->model
                ->fill($this->input())
                ->save();

            // Revert variable
            Record::setLogUser($backupLogUser);

            return $saved;
        }
    }

    public function errors()
    {
        $validator = $this->validator();
        if (Request::wantsJson()) {
            return $validator->errors()->all();
        } else {
            return Redirect::to($this->backUrl())
                ->withInput()
                ->withErrors($validator);
        }
    }

    public function input()
    {
        if (is_null($this->input)) {
            $this->input = Input::all();
        }

        return $this->input;
    }

    public function setInput($input)
    {
        $this->input = $input;
    }

    public function validator()
    {
        if (is_null($this->validator)) {
            $this->validator = Validator::make(
                $this->input(),
                $this->model->getRules()
            );
        }

        return $this->validator;
    }

    protected function backUrl()
    {
        $actionName = Route::getCurrentRoute()->getActionName();
        $action = explode('@', $actionName)[1];

        if ($action === 'store') {
            return $this->model->getUrl('create');
        } elseif ($action === 'update') {
            return $this->model->getUrl('edit');
        }
        throw new Exception('Unknown action.');
    }
}
