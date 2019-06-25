<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use SoapBox\Formatter\Formatter;
use Response;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GRequest;


class EnrollmentController extends Controller
{
    //
    public function validateMember(Request $request){
        try{
            \Log::debug(
                'CALLED ROUTE '.url()->current()
            );

            $clm_server = 'http://enoc-s.test';
            $phone = '09568946600s';
            $loyaltyid = null;
            $bearer = 'HOQxdNIMXUuyxB3mzjKmIJWDE2OLljqrPWgwzYSMYwbLSfAEzJZmK0ri7I9N';

            $json = json_encode([
                'phone' => $phone,
                'loyaltyid' => $loyaltyid
            ]);
            
            $client = new Client(); //GuzzleHttp\Client  
            $response = new GRequest(
                'GET', 
                ''.$clm_server.'/api/validate-member',
                [
                    'Authorization' => 'Bearer '.$bearer,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                $json
            );  
            $response = $client->send($response);
            $response = $response->getBody()->getContents();
            $response = json_decode($response);



            // RETURNING A XML TO WINSERVE
            $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Header/></soapenv:Envelope>');
            //header('Content-Type: text/xml'); 
            $xml->addAttribute('version', '1.0');
            $body = $xml->addChild('soapenv:Body');  
            $body->addChild('loyaltyId', $response->loyaltyId);
            $body->addChild('accountId', $response->accountId);
            $body->addChild('customerId', $response->customerId);
            $body->addChild('mainPointsBalance', $response->mainPointsBalance);
            $body->addChild('mainPointsBalanceInMoney', $response->mainPointsBalanceInMoney);
            $body->addChild('status', $response->status);
            $body->addChild('statusName', $response->statusName);
            // if($json_data['success'] == true){
            //     $body->addChild('ORDNUM', $json_data['data']['ORDNUM']);
            //     $body->addChild('NETAMOUNT', $json_data['data']['NETAMOUNT']);
            //     $body->addChild('CREATED_AT', $json_data['data']['CREATED_AT']);
            //     $body->addChild('STATUS', $json_data['data']['STATUS']); 
            // } 
            $response = Response::make($xml->asXML(), 200);
            $response->header('Content-Type', 'application/xml');
            return $response; 

            // loyaltyId: (number)
            // Member's loyalty id (identifierNo).

            // accountId: (number)
            // Account unique id

            // customerId: (number)
            // Unique customer id

            // mainPointsBalance: (number)
            // Number of main point balance on the account

            // mainPointsBalanceInMoney: (number)
            // Number of main point balance on the account in money (calculated basing on the ratio defined in CLM, as a system parameter)

            // status: (string - maxLength: 1)
            // Code of account status, from dictionary ACCOUNT_STATUSES

            // statusName: (string)
            // Name of account status, from dictionary ACCOUNT_STATUSES

        }catch( \Exception $e){
            \Log::error($e->getMessage());
            dd($e->getMessage());
        }
    }
}
