<?php

namespace Codificar\Finance\Http\Rules;

use Illuminate\Contracts\Validation\Rule;

class CheckEnabledSettings implements Rule
{

    protected $userId;
    protected $providerId;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($userId, $providerId) {
        $this->userId = $userId;
        $this->providerId = $providerId;
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
        if($this->userId && !\Settings::showUserBalance()) {
            return false;
        } else if($this->providerId && !\Settings::showProviderBalance()) {
            return false;
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message() {
        return trans('finance.disabled_show_balance');
    }
}