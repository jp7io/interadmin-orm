<?php

namespace Jp7\Laravel5;

use App\Http\Requests\Request;

class FormRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;  // Allows all users in
    }
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $scope = \Controller::getCurrentController()->getScope();
        return $scope->build()->getRules();
    }
}
