<?php

class AccountController {
    
    private $account = null;

    /*
    To do any operations on the Account class we need to authorize first. 
    If we try to construct an Account object with invalid information it should throw an error 
    If we need to keep longer time authorization on the mobile device we might want to replace this with a token
    The domain logic has been moved to the domain model
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
        $multiplied_money = $this->account->getMultipliedSummary();
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
        try {
            $status  = $this->account->deposit($amount);
            switch ($status) {
                case Account::SUCCESS: 
                    return "Success";
                case Account::ERROR_MAX_DEPOSIT_PER_DAY:
                    return "Error: Max deposit per day exceeded";
                case Account::ERROR_MAX_DEPOSITED_AMOUNT:
                    return "Error: Max deposited amount exceeded";
                case Account::ERROR_AMOUNT_NOT_AN_INTEGER:
                    return "Error: amount not an integer";
                case Account::ERROR_AUTHORIZATION_FAILURE:
                    return "Error: Authorization failure";
                default:
                    return "Error: Unknown error";                                
                }
        } catch(Exception $e) {
            return "Error: ". $e;
        }
    }

    function withdraw($amount) {
        if(!$this->account) {
            // We don't have a valid account so we should fail
            return "Error: No account found";
        }
        try {
            $status  = $this->account->withdraw($amount);
            switch ($status) {
                case Account::SUCCESS: 
                    // TODO Logic to actually pay out the money the user
                    return "Success";
                case Account::ERROR_NOT_ENOUGH_MONEY_IN_ACCOUNT:
                    return "Error: Not enough money in account to withdraw the requested amount";
                case Account::ERROR_AMOUNT_NOT_AN_INTEGER:
                    return "Error: amount not an integer";
                case Account::ERROR_AUTHORIZATION_FAILURE:
                    return "Error: Authorization failure";
                default:
                    return "Error: Unknown error";                                
            }
        } catch(Exception $e) {
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
        try {
            $status  = $this->account->authorizePayment($payment_request);
            switch ($status) {
                case Account::SUCCESS: 
                    return "Success";
                case Account::ERROR_NOT_ENOUGH_MONEY_IN_ACCOUNT:
                    return "Error: Not enough money in account to authorize payment";
                case Account::ERROR_AMOUNT_NOT_AN_INTEGER:
                    return "Error: amount in payment_request not an integer";
                case Account::ERROR_AUTHORIZATION_FAILURE:
                    return "Error: Authorization failure";
                default:
                    return "Error: Unknown error";                                
            }
        } catch(Exception $e) {
            return "Error: ". $e;
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
            $status = Account::addPromotion($accountID, $amount, $message); // This function should request an update of the summary amounts after it has run
            switch ($status) {
                case Account::SUCCESS: 
                    return "Success";
                case Account::ERROR_AMOUNT_NOT_AN_INTEGER:
                    return "Error: amount in not an integer";
                default:
                    return "Error: Unknown error";                                
            }
        } catch (Exception $e) {
            return "Error: ".$e;
        }
    }
 
}    
?>