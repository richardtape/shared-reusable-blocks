<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor {

	use _generated\AcceptanceTesterActions;

	/**
	 * @Given I am on the home page
	 */
	public function iAmOnTheHomePage() {

		$this->amOnPage( '/' );
		// throw new \Codeception\Exception\Incomplete( "Step `I am on the home page` is not defined" );

	}

	/**
	* @When I click the log in button
	*/
	public function iClickTheLogInButton() {
		$this->click( 'Log in' );
	}

	/**
	* @Then I should be on the log in page
	*/
	public function iShouldBeOnTheLogInPage() {
		$this->seeInCurrentUrl( 'wp-login.php' );
	}
}
