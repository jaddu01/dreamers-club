<?php

namespace App\Traits;

use App\Models\Transaction;
use App\Models\WinningUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait CommonTrait
{
    //numbers
    public static function getNumbers(){
        $numbers = [];
        $file = File::get(public_path('assets/numbers.json'));
        $numbers = json_decode($file, true);
        return $numbers;
    }

    //get total amount
    public function getTotalAmount($json_data){
        $numbers = self::getNumbers();
        $total_amount = 0;
        foreach ($json_data['data'] as $key => $value) {
            switch ($value['game_type']){
                case 'single_ankda':
                    $numbers = $numbers['single_ankda'];
                    break;
                case 'jodi':
                    $numbers = $numbers['jodi'];
                    break;
                case 'family_jodi':
                    $numbers = $numbers['family_jodi'];
                    break;
                case 'red_jodi':
                    $numbers = $numbers['red_jodi'];
                    break;
                case 'red_family_jodi':
                    $numbers = $numbers['red_family_jodi'];
                    break;
                case 'sp_panel':
                    $numbers = $numbers['sp_panel'];
                    break;
                case 'dp_panel':
                    $numbers = $numbers['dp_panel'];
                    break;
                case 'tp_panel':
                    $numbers = $numbers['tp_panel'];
                    break;
                case 'family_panel':
                    $numbers = $numbers['family_panel'];
                    break;
                case 'cycle_panel':
                    $numbers = $numbers['cycle_panel'];
                    break;
                default:
                    break;
            }
            $numbersCount = count(explode(',',$value['numbers']));
            $total_amount += $value['point'] * $numbers[$value['type']][$value['number']];
        }
    }
    public function getSum($post){
        $sum = 0;
        foreach ($post as $key => $value) {
            $sum += $value['point'];
        }
        return number_format($sum,2,'.','');
    }
    public function getWinAmount($id,$family_number,$user_id,$game_type,$token_id){
        $win_amount = WinningUser::query()
            ->where('bid',$family_number)
            ->where('game_type',$game_type)
            ->where('user_id',$user_id)
            ->where('winning_distributed','Y')
            ->where('token_id',$token_id)->first();

        return $win_amount->win_amount ?? 0;
    }

    //generate transaction id
    public function generateTransactionId(){
        $transaction_id = "SM#".time().rand(000,999);
        if ($this->checkTransactionId($transaction_id)){
            $this->generateTransactionId();
        }
        return $transaction_id;
    }

    //check transaction id
    public function checkTransactionId($transaction_id){
        $transaction = Transaction::query()->where("transaction_id",$transaction_id)->first();
        if ($transaction){
            return true;
        }
        return false;
    }


    public function calculatePercentageCurrentWeekFromLastWeek($usersCollection){
        // Get the start and end dates for the last week
        $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
        $endOfLastWeek = Carbon::now()->subWeek()->endOfWeek();

        // Get the start and end dates for the current week
        $startOfCurrentWeek = Carbon::now()->startOfWeek();
        $endOfCurrentWeek = Carbon::now()->endOfWeek();

        // Query the database for registered users within the last week
        $registeredUsersLastWeek = $usersCollection->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->count();

        // Query the database for registered users within the current week
        $registeredUsersCurrentWeek = $usersCollection->whereBetween('created_at', [$startOfCurrentWeek, $endOfCurrentWeek])->count();

        // Calculate the percentage of users registered in the current week from the last week
        $percentageChange = 0;
        if ($registeredUsersCurrentWeek > 0) {
            $percentageChange = (($registeredUsersCurrentWeek - $registeredUsersLastWeek) / $registeredUsersCurrentWeek) * 100;
        }

        return $percentageChange > 0 ? number_format($percentageChange, 2) : 0.00;
    }
}
