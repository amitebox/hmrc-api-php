<?php

namespace HMRC\Test\VAT;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use HMRC\Exceptions\InvalidVariableValueException;
use HMRC\Oauth2\AccessToken as HMRCAccessToken;
use HMRC\Request\RequestMethod;
use HMRC\Test\Request\RequestTest;
use HMRC\VAT\RetrieveVATObligationsGovTestScenario;
use HMRC\VAT\RetrieveVATObligationsRequest;
use HMRC\VAT\RetrieveVATObligationStatus;
use League\OAuth2\Client\Token\AccessToken;

class RetrieveVATObligationsRequestTest extends RequestTest
{
    private $vrn;

    private $from;

    private $to;

    public function __construct()
    {
        parent::__construct();

        $this->vrn = uniqid();
        $this->from = '2018-01-01';
        $this->to = '2019-01-01';
    }

    /** @test */
    public function it_throws_exception_when_given_wrong_government_test_scenario()
    {
        $this->expectException(InvalidVariableValueException::class);

        $request = new RetrieveVATObligationsRequest($this->vrn, $this->from, $this->to);
        $request->setGovTestScenario('WRONG');
    }

    /** @test */
    public function it_doesnt_throws_exception_when_given_correct_government_test_scenario()
    {
        $request = new RetrieveVATObligationsRequest($this->vrn, $this->from, $this->to);
        $request->setGovTestScenario(RetrieveVATObligationsGovTestScenario::MONTHLY_THREE_MET);

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function it_throws_exception_when_given_wrong_status()
    {
        $this->expectException(InvalidVariableValueException::class);

        new RetrieveVATObligationsRequest($this->vrn, $this->from, $this->to, 'A');
    }

    /** @test */
    public function it_doesnt_throws_exception_when_given_correct_status()
    {
        new RetrieveVATObligationsRequest($this->vrn, $this->from, $this->to, RetrieveVATObligationStatus::OPEN);
        new RetrieveVATObligationsRequest($this->vrn, $this->from, $this->to, RetrieveVATObligationStatus::FULFILLED);

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function it_calls_correct_endpoint()
    {
        // Setup access token
        $accessToken = uniqid();
        HMRCAccessToken::set(new AccessToken([
            'access_token' => $accessToken,
        ]));

        // Setup mocked client
        $container = [];
        $stack = HandlerStack::create(new MockHandler([
            new Response(200),
        ]));
        $stack->push(Middleware::history($container));
        $mockedClient = new Client(['handler' => $stack]);

        // Call the API
        $status = RetrieveVATObligationStatus::OPEN;
        (new RetrieveVATObligationsRequest($this->vrn, $this->from, $this->to, $status))
            ->setClient($mockedClient)
            ->fire();

        // Asserts
        $this->assertCount(1, $container);

        /** @var Request $guzzleRequest */
        $guzzleRequest = $container[0]['request'];
        $this->assertUri($guzzleRequest);
        $this->assertAuthorizationHeader($guzzleRequest, $accessToken);
        $this->assertAcceptHeader($guzzleRequest);
        $this->assertMethod($guzzleRequest);
        $this->assertQuery($guzzleRequest, [
            'from'   => $this->from,
            'to'     => $this->to,
            'status' => $status,
        ]);
    }

    protected function getCorrectPath()
    {
        return "/organisations/vat/{$this->vrn}/obligations";
    }

    protected function getCorrectMethod()
    {
        return RequestMethod::GET;
    }
}
