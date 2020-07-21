#Tests
All these tests are on the AccountController

##Authentication
First we need to test that we can't access the functions without being authorized first
###authorize($accountID, $username, $password)
* Test with invalid accountID
* Test with valid accountID but invalid username/password
* Test with valid credentials

###Test other functions without having authorized first
* getBalances()
* deposit($amount)
* withdraw($amount)
* authorizePayment($payment_request)

###Test other functions with invalid authorization
* getBalances()
* deposit($amount)
* withdraw($amount)
* authorizePayment($payment_request)

###getBalance()
Here we need to check that the values that are returned matches the data in the Account Table

###deposit($amount)
We need to check that the Transaction table is modified when required and the Account Table is updated if Transaction Table has changed.
Also we need to check the business rules for deposites are enforced. 
* Try to deposit amount that is a string
* Try to deposit amount that is a float
* Try to deposit amount that is negative
* Try to deposit amount that is null
* Try to deposit an amount above the max deposit for one day
* Try to deposit an amount that bringes the total deposit for today above the max
* Try to deposit an amount that would bring the total over max allowed for the account
* Try to deposit a legal amount and check that the tables reflect this deposit

###withdraw($amount)
We need to check that the Transaction table is modified when required and the Account Table is updated if Transaction Table has changed.
* Try to withdraw an amount that is a string
* Try to withdraw an amount that is a float
* Try to withdraw an amount that is negative
* Try to withdraw an amount that is null
* Try to withdraw an amount above the deposited-amount in the account
* Try to withdraw a legal amount. Check that both Transaction Table and Account Table is updated

###authorizePayment($payment_request)
We need to check that the Transaction table is modified when required and the Account Table is updated if Transaction Table has changed.
* Try to authorize an amount that is a string
* Try to authorize an amount that is a float
* Try to authorize an amount that is negative
* Try to authorize an amount that is null
* Try to authorize an amount that is fully covered by promotion money
* Try to authorize an amount that is partially covered by promotion money and before midnight
* Try to authorize an amount that is partially covered by promotion money and after midnight
* Try to authorize an amount without promotion money and before midnight
* Try to authorize an amount without promotion money and after midnight
####Additionally we need other tests to validate checks on the payment request

###addPromotion($accountID, $amount, $message)
* Try to add a promotion amount that is a string
* Try to add a promotion amount that is a float
* Try to add a promotion amount that is negative
* Try to add a promotion amount that is null
* Try to add a legal amount and see that it is reflected in both Transaction Table and Account Table
####Additionally we need other test to establish that the user is an admin