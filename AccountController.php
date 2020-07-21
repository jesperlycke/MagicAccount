<?php

class AccountController {
    
    const MAGIC_MULTIPLIER = 3;
    const MAX_DEPOSITED_AMOUNT = 50000; // Using integer not float for amounts
    const MAX_DEPOSIT_PER_DAY = 10000; // Using integer not float for amounts
    const CURRENCY_FORMAT = "da_DK";
    private $account = null;
    private $current_fmt = numfmt_create( CURRENCY_FORMAT, NumberFormatter::CURRENCY );


    /*
    To do any operations on the Account class we need to authorize first. 
    If we try to construct an Account object with invalid information it should throw an error 
    If we need to keep longer time authorization on the mobile device we might want to replace this with a token
    A lot of this logic could and probably should be in the Account class but the controller would be quite boring if all domain logic was in the domain model so it is here for show
    Return messages are just placeholders. There a try/catch in many places in case we need error messages sent back to an api
    */

    function authorize($accountID, $username, $password) {
        if (!Account::doesAccountExist($accountID)) {
            // We don't have an account with that AccountID so we should fail
            return "Error: Invalid account";
        } else {
            try {
                $account = new Account($accountID, $username, $password); 
            } catch(Exception $e) {
                // The credentials doesn't match the account so we should fail. Other errors could occured as well (e.g. database connectivity)
                $this->account = null;
                return "Error: ". $e;
            }
            // Everything is good
            $this->account = $account;
            return "Success";
        }
    }

    /*
    Return the balances from the Account table. No need to recalculate for this.
    I assume that this is requested by the mobile app very frequestly so this is stored in the Account table
    */
    function getBalances() {
        if(!$this->account) {
            // We don't have a valid account so we should fail
            return "Error: No account found";
        }
        $deposited_money = $this->account->getDepositedSummary();
        $multiplied_money = $deposited_money * MAGIC_MULTIPLIER;
        $promotion_money = $this->account->getPromotionSummary();
        $return_data = Array("deposited" => $deposited_money, "multiplied" => $multiplied_money, "promotion" => $promotion_money);
        return $return_data;
    }

    /*
    Let's deposit some money into the account. 
    */
    function deposit($amount) {
        if(!$this->account) {
            // We don't have a valid account so we should fail
            return "Error: No account found";
        }
        // Checking to see if the deposit would break any of the business rules
        if($amount > MAX_DEPOSIT_PER_DAY)  {
            return "Error: Not allowed to deposit more that ". $this->current_fmt(MAX_DEPOSIT_PER_DAY/100) ." per day";
        }
        $deposited_today = $this->account->getDepositedToday(); // The Deposited-amount that went into the account this calendar day
        if(($amount + $deposited_today) > MAX_DEPOSIT_PER_DAY) {
            return "Error: Not allowed to deposit more that ". $this->current_fmt(MAX_DEPOSIT_PER_DAY/100) ." per day. Already a deposit of ". $this->current_fmt($deposited_today/100) ." to this account today";
        } 
        $current_deposit = $this->account->getCurrentDepositBalance(); // The Deposited-amount that is currently in the account
        if(($amount + $current_deposit) > MAX_DEPOSITED_AMOUNT) {
            return "Error: The account is not allowed to hold more than ". $this->current_fmt(MAX_DEPOSITED_AMOUNT/100);
        }
        // Everything looks good
        try {
            $this->account->deposit($amount); // This function should request an update of the summary amounts after it has run
            return "Success";
        } catch(Exception $e) {
            return "Error: ". $e;
        }
    }

    function withdraw($amount) {
        if(!$this->account) {
            // We don't have a valid account so we should fail
            return "Error: No account found";
        }
        $current_deposit = $this->account-getCurrentDepositBalance(); // The Deposited-amount that is currently in the account
        if($amount > $current_deposit) {
            return "Error: Not enough money in the account to withdraw ". $this->current_fmt($amount/100);
        }
        try {
            $this->account->withdraw($amount); // This function should request an update of the summary amounts after it has run
            // TODO Logic to actually pay out the money the user
            return "Success";
        } catch (Exception $e) {
            return "Error: ". $e;
        }
        
    }

    /*
    Authorize a payment request and deduct the money from the account. If there are promtion money we should deduct that first. 
    Promotion money is never multiplied.
    If the request is created before midnight we should use the multiplier. 
    The logic for allowing the multiplier should come from the payment requests as we don't know when the venue opened. Also this could allow for some flexibility on behalf of the venue manager if the bar is very busy
    There are potential rounding errors here
    */
    function authorizePayment($payment_request) {
        if(!$this->account) {
            // We don't have a valid account so we should fail
            return "Error: No account found";
        }
        $request_amount = $payment_request->amount;
        $multipler_allowed = $payment_request->multiplier_allowed; 
        $promotion_money = $this->account->getCurrentPromotionBalance();
        $money = $this->account->getCurrentDepositBalance();
        if($multipler_allowed) {
            $money = $money * MAGIC_MULTIPLIER;
        }
        if($request_amount > ($promotion_money + $money)) {
            return "Error: not enough money in account to pay request";
        }
        try {
            if($promotion_money > 0) {
                // There is promotion money in the account so we need to use that first
                if($request_amount > $promotion_money) {
                    // The requested amount is more that the promotion money in the account so we first take from promotion then from deposited-amount
                    $this->account->payFromPromotion($promotion_money, $payment_request);
                    $request_amount_remainder = $request_amount - $promotion_money;
                    if($multipler_allowed) {
                        $this->account->payFromDeposit(round($request_amount_remainder / MAGIC_MULTIPLIER), $payment_request);
                    } else {
                        $this->account->payFromDeposit($request_amount_remainder, $payment_request);
                    }
                } else {
                    // There is enough promotion money in the account to pay for the request
                    $this->account->payFromPromotion($request_amount, $payment_request);
                }
            } else {
                // There are no promotion money in the account
                if($multipler_allowed) {
                    $this->account->payFromDeposit(round($request_amount / MAGIC_MULTIPLIER), $payment_request);
                } else {
                    $this->account->payFromDeposit($request_amount, $payment_request);
                }
            }
            
   
        } catch(Exception $e) {
            return "Error: ".$e;
        }
    }

    /*
    This function should only be accessible to an admin so we need to implement an authorization function here
    */
    function addPromotion($accountID, $amount, $message) {
        if (!Account::doesAccountExist($accountID)) {
            // We don't have an account with that AccountID so we should fail
            return "Error: Invalid account";
        }
        try {
            Account::addPromotion($accountID, $amount, $message); // This function should request an update of the summary amounts after it has run
            return "Success";
        } catch (Exception $e) {
            return "Error: ".$e;
        }
    }
 
}    
?>