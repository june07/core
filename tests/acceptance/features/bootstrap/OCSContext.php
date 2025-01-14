<?php
/**
 * ownCloud
 *
 * @author Artur Neumann <artur@jankaritech.com>
 * @copyright Copyright (c) 2019, ownCloud GmbH
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License,
 * as published by the Free Software Foundation;
 * either version 3 of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\Assert;
use TestHelpers\OcsApiHelper;
use TestHelpers\TranslationHelper;

require_once 'bootstrap.php';

/**
 * steps needed to send requests to the OCS API
 */
class OCSContext implements Context {

	/**
	 *
	 * @var FeatureContext
	 */
	private $featureContext;

	/**
	 * @When /^the user sends HTTP method "([^"]*)" to OCS API endpoint "([^"]*)"$/
	 *
	 * @param string $verb
	 * @param string $url
	 *
	 * @return void
	 */
	public function theUserSendsToOcsApiEndpoint($verb, $url) {
		$this->theUserSendsToOcsApiEndpointWithBody($verb, $url, null);
	}

	/**
	 * @Given /^the user has sent HTTP method "([^"]*)" to OCS API endpoint "([^"]*)"$/
	 *
	 * @param string $verb
	 * @param string $url
	 *
	 * @return void
	 */
	public function theUserHasSentToOcsApiEndpoint($verb, $url) {
		$this->theUserSendsToOcsApiEndpointWithBody($verb, $url, null);
		$this->featureContext->theHTTPStatusCodeShouldBeSuccess();
	}

	/**
	 * @When /^user "([^"]*)" sends HTTP method "([^"]*)" to OCS API endpoint "([^"]*)"$/
	 * @When /^user "([^"]*)" sends HTTP method "([^"]*)" to OCS API endpoint "([^"]*)" using password "([^"]*)"$/
	 *
	 * @param string $user
	 * @param string $verb
	 * @param string $url
	 * @param string $password
	 *
	 * @return void
	 */
	public function userSendsToOcsApiEndpoint($user, $verb, $url, $password = null) {
		$this->userSendsHTTPMethodToOcsApiEndpointWithBody(
			$user,
			$verb,
			$url,
			null,
			$password
		);
	}

	/**
	 * @Given /^user "([^"]*)" has sent HTTP method "([^"]*)" to API endpoint "([^"]*)"$/
	 *
	 * @param string $user
	 * @param string $verb
	 * @param string $url
	 * @param string $password
	 *
	 * @return void
	 */
	public function userHasSentToOcsApiEndpoint($user, $verb, $url, $password = null) {
		$this->userSendsHTTPMethodToOcsApiEndpointWithBody(
			$user,
			$verb,
			$url,
			null,
			$password
		);
		$this->featureContext->theHTTPStatusCodeShouldBeSuccess();
	}

	/**
	 * @param string $user
	 * @param string $verb
	 * @param string $url
	 * @param TableNode|array|null $body
	 * @param string|null $password
	 * @param array $headers
	 *
	 * @return void
	 */
	public function userSendsHTTPMethodToOcsApiEndpointWithBody(
		$user,
		$verb,
		$url,
		$body = null,
		$password = null,
		$headers = null
	) {
		/**
		 * array of the data to be sent in the body.
		 * contains $body data converted to an array
		 *
		 * @var array $bodyArray
		 */
		$bodyArray = [];
		if ($body instanceof TableNode) {
			$bodyArray = $body->getRowsHash();
		} elseif ($body !== null && \is_array($body)) {
			$bodyArray = $body;
		}

		if ($user !== 'UNAUTHORIZED_USER') {
			if ($password === null) {
				$password = $this->featureContext->getPasswordForUser($user);
			}
			$user = $this->featureContext->getActualUsername($user);
		} else {
			$user = null;
			$password = null;
		}
		$response = OcsApiHelper::sendRequest(
			$this->featureContext->getBaseUrl(),
			$user,
			$password,
			$verb,
			$url,
			$this->featureContext->getStepLineRef(),
			$bodyArray,
			$this->featureContext->getOcsApiVersion(),
			$headers
		);
		$this->featureContext->setResponse($response);
	}

	/**
	 * @param string $verb
	 * @param string $url
	 * @param TableNode $body
	 *
	 * @return void
	 */
	public function adminSendsHttpMethodToOcsApiEndpointWithBody(
		$verb,
		$url,
		TableNode $body
	) {
		$admin = $this->featureContext->getAdminUsername();
		$this->userSendsHTTPMethodToOcsApiEndpointWithBody(
			$admin,
			$verb,
			$url,
			$body
		);
	}

	/**
	 * @param string $verb
	 * @param string $url
	 * @param TableNode $body
	 *
	 * @return void
	 */
	public function theUserSendsToOcsApiEndpointWithBody($verb, $url, $body) {
		$this->userSendsHTTPMethodToOcsApiEndpointWithBody(
			$this->featureContext->getCurrentUser(),
			$verb,
			$url,
			$body
		);
	}

	/**
	 * @When /^user "([^"]*)" sends HTTP method "([^"]*)" to OCS API endpoint "([^"]*)" with body$/
	 *
	 * @param string $user
	 * @param string $verb
	 * @param string $url
	 * @param TableNode|null $body
	 * @param string $password
	 *
	 * @return void
	 */
	public function userSendHTTPMethodToOcsApiEndpointWithBody(
		$user,
		$verb,
		$url,
		$body = null,
		$password = null
	) {
		$this->userSendsHTTPMethodToOcsApiEndpointWithBody(
			$user,
			$verb,
			$url,
			$body,
			$password
		);
	}

	/**
	 * @Given /^user "([^"]*)" has sent HTTP method "([^"]*)" to OCS API endpoint "([^"]*)" with body$/
	 *
	 * @param string $user
	 * @param string $verb
	 * @param string $url
	 * @param TableNode|null $body
	 * @param string $password
	 *
	 * @return void
	 */
	public function userHasSentHTTPMethodToOcsApiEndpointWithBody(
		$user,
		$verb,
		$url,
		$body = null,
		$password = null
	) {
		$this->userSendsHTTPMethodToOcsApiEndpointWithBody(
			$user,
			$verb,
			$url,
			$body,
			$password
		);
		$this->featureContext->theHTTPStatusCodeShouldBeSuccess();
	}

	/**
	 * @When the administrator sends HTTP method :verb to OCS API endpoint :url
	 * @When the administrator sends HTTP method :verb to OCS API endpoint :url using password :password
	 *
	 * @param string $verb
	 * @param string $url
	 * @param string $password
	 *
	 * @return void
	 */
	public function theAdministratorSendsHttpMethodToOcsApiEndpoint(
		$verb,
		$url,
		$password = null
	) {
		$admin = $this->featureContext->getAdminUsername();
		$this->userSendsToOcsApiEndpoint($admin, $verb, $url, $password);
	}

	/**
	 * @When /^user "([^"]*)" sends HTTP method "([^"]*)" to OCS API endpoint "([^"]*)" with headers$/
	 *
	 * @param string $user
	 * @param string $verb
	 * @param string $url
	 * @param TableNode $headersTable
	 *
	 * @return void
	 * @throws Exception
	 */
	public function userSendsToOcsApiEndpointWithHeaders(
		$user,
		$verb,
		$url,
		TableNode $headersTable
	) {
		$user = $this->featureContext->getActualUsername($user);
		$password = $this->featureContext->getPasswordForUser($user);
		$this->userSendsToOcsApiEndpointWithHeadersAndPassword(
			$user,
			$verb,
			$url,
			$password,
			$headersTable
		);
	}

	/**
	 * @When /^the administrator sends HTTP method "([^"]*)" to OCS API endpoint "([^"]*)" with headers$/
	 *
	 * @param string $verb
	 * @param string $url
	 * @param TableNode $headersTable
	 *
	 * @return void
	 * @throws Exception
	 */
	public function administratorSendsToOcsApiEndpointWithHeaders(
		$verb,
		$url,
		TableNode $headersTable
	) {
		$this->userSendsToOcsApiEndpointWithHeaders(
			$this->featureContext->getAdminUsername(),
			$verb,
			$url,
			$headersTable
		);
	}

	/**
	 * @When /^user "([^"]*)" sends HTTP method "([^"]*)" to OCS API endpoint "([^"]*)" with headers using password "([^"]*)"$/
	 *
	 * @param string $user
	 * @param string $verb
	 * @param string $url
	 * @param string $password
	 * @param TableNode $headersTable
	 *
	 * @return void
	 * @throws Exception
	 */
	public function userSendsToOcsApiEndpointWithHeadersAndPassword(
		$user,
		$verb,
		$url,
		$password,
		TableNode $headersTable
	) {
		$this->featureContext->verifyTableNodeColumns(
			$headersTable,
			['header', 'value']
		);
		$user = $this->featureContext->getActualUsername($user);
		$headers = [];
		foreach ($headersTable as $row) {
			$headers[$row['header']] = $row ['value'];
		}

		$response = OcsApiHelper::sendRequest(
			$this->featureContext->getBaseUrl(),
			$user,
			$password,
			$verb,
			$url,
			$this->featureContext->getStepLineRef(),
			[],
			$this->featureContext->getOcsApiVersion(),
			$headers
		);
		$this->featureContext->setResponse($response);
	}

	/**
	 * @When /^the administrator sends HTTP method "([^"]*)" to OCS API endpoint "([^"]*)" with headers using password "([^"]*)"$/
	 *
	 * @param string $verb
	 * @param string $url
	 * @param string $password
	 * @param TableNode $headersTable
	 *
	 * @return void
	 * @throws Exception
	 */
	public function administratorSendsToOcsApiEndpointWithHeadersAndPassword(
		$verb,
		$url,
		$password,
		TableNode $headersTable
	) {
		$this->userSendsToOcsApiEndpointWithHeadersAndPassword(
			$this->featureContext->getAdminUsername(),
			$verb,
			$url,
			$password,
			$headersTable
		);
	}

	/**
	 * @When the administrator sends HTTP method :verb to OCS API endpoint :url with body
	 *
	 * @param string $verb
	 * @param string $url
	 * @param TableNode|null $body
	 *
	 * @return void
	 */
	public function theAdministratorSendsHttpMethodToOcsApiEndpointWithBody(
		$verb,
		$url,
		TableNode $body
	) {
		$this->adminSendsHttpMethodToOcsApiEndpointWithBody(
			$verb,
			$url,
			$body
		);
	}

	/**
	 * @Given the administrator has sent HTTP method :verb to OCS API endpoint :url with body
	 *
	 * @param string $verb
	 * @param string $url
	 * @param TableNode|null $body
	 *
	 * @return void
	 */
	public function theAdministratorHasSentHttpMethodToOcsApiEndpointWithBody(
		$verb,
		$url,
		TableNode $body
	) {
		$this->adminSendsHttpMethodToOcsApiEndpointWithBody(
			$verb,
			$url,
			$body
		);
		$this->featureContext->theHTTPStatusCodeShouldBeSuccess();
	}

	/**
	 * @When /^the user sends HTTP method "([^"]*)" to OCS API endpoint "([^"]*)" with body$/
	 *
	 * @param string $verb
	 * @param string $url
	 * @param TableNode $body
	 *
	 * @return void
	 */
	public function theUserSendsHTTPMethodToOcsApiEndpointWithBody($verb, $url, $body) {
		$this->theUserSendsHTTPMethodToOcsApiEndpointWithBody(
			$verb,
			$url,
			$body
		);
	}

	/**
	 * @Given /^the user has sent HTTP method "([^"]*)" to OCS API endpoint "([^"]*)" with body$/
	 *
	 * @param string $verb
	 * @param string $url
	 * @param TableNode $body
	 *
	 * @return void
	 */
	public function theUserHasSentHTTPMethodToOcsApiEndpointWithBody($verb, $url, $body) {
		$this->theUserSendsHTTPMethodToOcsApiEndpointWithBody(
			$verb,
			$url,
			$body
		);
		$this->featureContext->theHTTPStatusCodeShouldBeSuccess();
	}

	/**
	 * @When the administrator sends HTTP method :verb to OCS API endpoint :url with body using password :password
	 *
	 * @param string $verb
	 * @param string $url
	 * @param string $password
	 * @param TableNode $body
	 *
	 * @return void
	 */
	public function theAdministratorSendsHttpMethodToOcsApiWithBodyAndPassword(
		$verb,
		$url,
		$password,
		TableNode $body
	) {
		$admin = $this->featureContext->getAdminUsername();
		$this->userSendsHTTPMethodToOcsApiEndpointWithBody(
			$admin,
			$verb,
			$url,
			$body,
			$password
		);
	}

	/**
	 * @When /^user "([^"]*)" sends HTTP method "([^"]*)" to OCS API endpoint "([^"]*)" with body using password "([^"]*)"$/
	 *
	 * @param string $user
	 * @param string $verb
	 * @param string $url
	 * @param string $password
	 * @param TableNode $body
	 *
	 * @return void
	 */
	public function userSendsHTTPMethodToOcsApiEndpointWithBodyAndPassword(
		$user,
		$verb,
		$url,
		$password,
		$body
	) {
		$this->userSendsHTTPMethodToOcsApiEndpointWithBody(
			$user,
			$verb,
			$url,
			$body,
			$password
		);
	}

	/**
	 * @When user :user requests these endpoints with :method using password :password about user :ofUser
	 *
	 * @param string $user
	 * @param string $method
	 * @param string $password
	 * @param string $ofUser
	 * @param TableNode $table
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function userSendsRequestToTheseEndpointsWithOutBodyUsingPassword(
		$user,
		$method,
		$password,
		$ofUser,
		TableNode $table
	) {
		$this->userSendsRequestToTheseEndpointsWithBodyUsingPassword(
			$user,
			$method,
			null,
			$password,
			$ofUser,
			$table
		);
	}

	/**
	 * @When user :user requests these endpoints with :method including body :body using password :password about user :ofUser
	 *
	 * @param string $user
	 * @param string $method
	 * @param string $body
	 * @param string $password
	 * @param string $ofUser
	 * @param TableNode $table
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function userSendsRequestToTheseEndpointsWithBodyUsingPassword(
		$user,
		$method,
		$body,
		$password,
		$ofUser,
		TableNode $table
	) {
		$user = $this->featureContext->getActualUsername($user);
		$ofUser = $this->featureContext->getActualUsername($ofUser);
		$this->featureContext->verifyTableNodeColumns($table, ['endpoint'], ['destination']);
		$this->featureContext->emptyLastHTTPStatusCodesArray();
		$this->featureContext->emptyLastOCSStatusCodesArray();
		foreach ($table->getHash() as $row) {
			$row['endpoint'] = $this->featureContext->substituteInLineCodes(
				$row['endpoint'],
				$ofUser
			);
			$header = [];
			if (isset($row['destination'])) {
				$header['Destination'] = $this->featureContext->substituteInLineCodes(
					$this->featureContext->getBaseUrl() . $row['destination'],
					$ofUser
				);
			}
			$this->featureContext->authContext->userRequestsURLWithUsingBasicAuth(
				$user,
				$row['endpoint'],
				$method,
				$password,
				$body,
				$header
			);
			$this->featureContext->pushToLastStatusCodesArrays();
		}
	}

	/**
	 * @When user :user requests these endpoints with :method including body :body about user :ofUser
	 *
	 * @param string $user
	 * @param string $method
	 * @param string $body
	 * @param string $ofUser
	 * @param TableNode $table
	 *
	 * @return void
	 * @throws Exception
	 */
	public function userSendsRequestToTheseEndpointsWithBody($user, $method, $body, $ofUser, TableNode $table) {
		$header = [];
		if ($method === 'MOVE' || $method === 'COPY') {
			$header['Destination'] = '/path/to/destination';
		}

		$this->sendRequestToTheseEndpointsAsNormalUser(
			$user,
			$method,
			$ofUser,
			$table,
			$body,
			null,
			$header,
		);
	}

	/**
	 * @When /^user "([^"]*)" requests these endpoints with "([^"]*)" to (?:get|set) property "([^"]*)" about user "([^"]*)"$/
	 *
	 * @param string $user
	 * @param string $method
	 * @param string $property
	 * @param string $ofUser
	 * @param TableNode $table
	 *
	 * @return void
	 * @throws Exception
	 */
	public function userSendsRequestToTheseEndpointsWithProperty($user, $method, $property, $ofUser, TableNode $table) {
		$this->sendRequestToTheseEndpointsAsNormalUser(
			$user,
			$method,
			$ofUser,
			$table,
			null,
			$property
		);
	}

	/**
	 * @param string $user
	 * @param string $method
	 * @param string $ofUser
	 * @param TableNode $table
	 * @param string|null $body
	 * @param string|null $property
	 * @param Array|null $header
	 *
	 * @return void
	 * @throws Exception
	 */
	public function sendRequestToTheseEndpointsAsNormalUser(
		$user,
		$method,
		$ofUser,
		$table,
		$body = null,
		$property = null,
		$header = null
	) {
		$user = $this->featureContext->getActualUsername($user);
		$ofUser = $this->featureContext->getActualUsername($ofUser);
		$this->featureContext->verifyTableNodeColumns($table, ['endpoint']);
		$this->featureContext->emptyLastHTTPStatusCodesArray();
		$this->featureContext->emptyLastOCSStatusCodesArray();
		if (!$body && $property) {
			$body = $this->featureContext->getBodyForOCSRequest($method, $property);
		}
		foreach ($table->getHash() as $row) {
			$row['endpoint'] = $this->featureContext->substituteInLineCodes(
				$row['endpoint'],
				$ofUser
			);
			$this->featureContext->authContext->userRequestsURLWithUsingBasicAuth(
				$user,
				$row['endpoint'],
				$method,
				$this->featureContext->getPasswordForUser($user),
				$body,
				$header
			);
			$this->featureContext->pushToLastStatusCodesArrays();
		}
	}

	/**
	 * @When user :asUser requests these endpoints with :method including body :body using the password of user :user
	 *
	 * @param string $asUser
	 * @param string $method
	 * @param string $body
	 * @param string $user
	 * @param TableNode $table
	 *
	 * @return void
	 * @throws Exception
	 */
	public function userRequestsTheseEndpointsWithUsingThePasswordOfUser($asUser, $method, $body, $user, TableNode $table) {
		$asUser = $this->featureContext->getActualUsername($asUser);
		$userRenamed = $this->featureContext->getActualUsername($user);
		$this->featureContext->verifyTableNodeColumns($table, ['endpoint']);
		$this->featureContext->emptyLastHTTPStatusCodesArray();
		$this->featureContext->emptyLastOCSStatusCodesArray();
		foreach ($table->getHash() as $row) {
			$row['endpoint'] = $this->featureContext->substituteInLineCodes(
				$row['endpoint'],
				$userRenamed
			);
			$this->featureContext->authContext->userRequestsURLWithUsingBasicAuth(
				$asUser,
				$row['endpoint'],
				$method,
				$this->featureContext->getPasswordForUser($user),
				$body
			);
			$this->featureContext->pushToLastStatusCodesArrays();
		}
	}

	/**
	 * @Then /^the OCS status code should be "([^"]*)"$/
	 *
	 * @param int|int[]|string|string[] $statusCode
	 * @param string $message
	 *
	 * @return void
	 * @throws Exception
	 */
	public function theOCSStatusCodeShouldBe($statusCode, $message = "") {
		$responseStatusCode = $this->getOCSResponseStatusCode(
			$this->featureContext->getResponse()
		);
		if (\is_array($statusCode)) {
			if ($message === "") {
				$message = "OCS status code is not any of the expected values " . \implode(",", $statusCode) . " got " . $responseStatusCode;
			}
			Assert::assertContainsEquals(
				$responseStatusCode,
				$statusCode,
				$message
			);
		} else {
			if ($message === "") {
				$message = "OCS status code is not the expected value " . $statusCode . " got " . $responseStatusCode;
			}

			Assert::assertEquals(
				$statusCode,
				$responseStatusCode,
				$message
			);
		}
	}

	/**
	 * @Then /^the OCS status code should be "([^"]*)" or "([^"]*)"$/
	 *
	 * @param int|string $statusCode1
	 * @param int|string $statusCode2
	 *
	 * @return void
	 */
	public function theOcsStatusCodeShouldBeOr($statusCode1, $statusCode2) {
		$this->theOCSStatusCodeShouldBe(
			[$statusCode1, $statusCode2]
		);
	}

	/**
	 * Check the text in an OCS status message
	 *
	 * @Then /^the OCS status message should be "([^"]*)"$/
	 * @Then /^the OCS status message should be "([^"]*)" in language "([^"]*)"$/
	 *
	 * @param string $statusMessage
	 * @param string $language
	 *
	 * @return void
	 */
	public function theOCSStatusMessageShouldBe($statusMessage, $language=null) {
		$language = TranslationHelper::getLanguage($language);
		$statusMessage = $this->getActualStatusMessage($statusMessage, $language);

		Assert::assertEquals(
			$statusMessage,
			$this->getOCSResponseStatusMessage(
				$this->featureContext->getResponse()
			),
			'Unexpected OCS status message :"' . $this->getOCSResponseStatusMessage(
				$this->featureContext->getResponse()
			) . '" in response'
		);
	}

	/**
	 * Check the text in an OCS status message
	 *
	 * @Then /^the OCS status message about user "([^"]*)" should be "([^"]*)"$/
	 *
	 * @param string $user
	 * @param string $statusMessage
	 *
	 * @return void
	 */
	public function theOCSStatusMessageAboutUserShouldBe($user, $statusMessage) {
		$user = \strtolower($this->featureContext->getActualUsername($user));
		$statusMessage = $this->featureContext->substituteInLineCodes(
			$statusMessage,
			$user
		);
		Assert::assertEquals(
			$statusMessage,
			$this->getOCSResponseStatusMessage(
				$this->featureContext->getResponse()
			),
			'Unexpected OCS status message :"' . $this->getOCSResponseStatusMessage(
				$this->featureContext->getResponse()
			) . '" in response'
		);
	}

	/**
	 * Check the text in an OCS status message.
	 * Use this step form if the expected text contains double quotes,
	 * single quotes and other content that theOCSStatusMessageShouldBe()
	 * cannot handle.
	 *
	 * After the step, write the expected text in PyString form like:
	 *
	 * """
	 * File "abc.txt" can't be shared due to reason "xyz"
	 * """
	 *
	 * @Then /^the OCS status message should be:$/
	 *
	 * @param PyStringNode $statusMessage
	 *
	 * @return void
	 */
	public function theOCSStatusMessageShouldBePyString(
		PyStringNode $statusMessage
	) {
		Assert::assertEquals(
			$statusMessage->getRaw(),
			$this->getOCSResponseStatusMessage(
				$this->featureContext->getResponse()
			),
			'Unexpected OCS status message: "' . $this->getOCSResponseStatusMessage(
				$this->featureContext->getResponse()
			) . '" in response'
		);
	}

	/**
	 * Parses the xml answer to get ocs response which doesn't match with
	 * http one in v1 of the api.
	 *
	 * @param ResponseInterface $response
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getOCSResponseStatusCode($response) {
		$responseXml = $this->featureContext->getResponseXml($response, __METHOD__);
		if (isset($responseXml->meta[0], $responseXml->meta[0]->statuscode)) {
			return (string) $responseXml->meta[0]->statuscode;
		}
		throw new \Exception(
			"No OCS status code found in responseXml"
		);
	}

	/**
	 * Parses the xml answer to get ocs response message which doesn't match with
	 * http one in v1 of the api.
	 *
	 * @param ResponseInterface $response
	 *
	 * @return string
	 */
	public function getOCSResponseStatusMessage($response) {
		return (string) $this->featureContext->getResponseXml($response, __METHOD__)->meta[0]->message;
	}

	/**
	 * convert status message in the desired language
	 *
	 * @param $statusMessage
	 * @param $language
	 *
	 * @return string
	 */
	public function getActualStatusMessage($statusMessage, $language) {
		if ($language !== null) {
			$multiLingualMessage = \json_decode(
				\file_get_contents(__DIR__ . "/../../fixtures/multiLanguageErrors.json"),
				true
			);

			if (isset($multiLingualMessage[$statusMessage][$language])) {
				$statusMessage = $multiLingualMessage[$statusMessage][$language];
			}
		}
		return $statusMessage;
	}

	/**
	 * check if the HTTP status code and the OCS status code indicate that the request was successful
	 * this function is aware of the currently used OCS version
	 *
	 * @param string $message
	 *
	 * @return void
	 * @throws Exception
	 */
	public function assertOCSResponseIndicatesSuccess($message = "") {
		$this->featureContext->theHTTPStatusCodeShouldBe('200', $message);
		if ($this->featureContext->getOcsApiVersion() === 1) {
			$this->theOCSStatusCodeShouldBe('100', $message);
		} else {
			$this->theOCSStatusCodeShouldBe('200', $message);
		}
	}

	/**
	 * This will run before EVERY scenario.
	 * It will set the properties for this object.
	 *
	 * @BeforeScenario
	 *
	 * @param BeforeScenarioScope $scope
	 *
	 * @return void
	 */
	public function before(BeforeScenarioScope $scope) {
		// Get the environment
		$environment = $scope->getEnvironment();
		// Get all the contexts you need in this context
		$this->featureContext = $environment->getContext('FeatureContext');
	}
}
