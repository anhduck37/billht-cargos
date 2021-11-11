<?php


namespace App\Services;


use App\User;

class UserService
{

    public function getTrackingCode() {
        while (true) {
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $numbers    = '0123456789';

            $tracking_code_character    = substr(str_shuffle(str_repeat($characters, 3)), 0, 3);
            $tracking_code_numbers      = substr(str_shuffle(str_repeat($numbers, 3)), 0, 3);

            $tracking_code = $tracking_code_character.$tracking_code_numbers;

            $checkTrackingCode = User::where('tracking_code', $tracking_code)->count();
            if($checkTrackingCode == 0) {
                return $tracking_code;
            }
        }
    }

}
