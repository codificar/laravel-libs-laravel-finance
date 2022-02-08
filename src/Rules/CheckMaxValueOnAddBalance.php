<?php

namespace Codificar\Finance\Rules;

use Codificar\Finance\Models\LibModel;
use Illuminate\Contracts\Validation\Rule;

class CheckMaxValueOnAddBalance implements Rule
{
    public $message = 'Max value error';

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        try {
            $this->message = trans('financeTrans::finance.max_value', [
                'value' => currency_format(currency_converted($value))
            ]);
    
            $setting = LibModel::getMaxValueOnAddBalanceSetting();
    
            if ($setting == 0) {
                return true;
            } else if ($setting > 0 && $setting > $value) {
                return true;
            } else {
                return false;
            }
        } catch (\Throwable $th) {
            return true;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
