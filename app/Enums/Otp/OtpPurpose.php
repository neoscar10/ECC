<?php

namespace App\Enums\Otp;

enum OtpPurpose: string
{
    case SIGNUP = 'signup';
    case LOGIN = 'login';
    case PASSWORD_RESET = 'password_reset';
    case PHONE_CHANGE = 'phone_change';
}
