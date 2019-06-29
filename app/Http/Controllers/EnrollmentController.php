<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use SoapBox\Formatter\Formatter;
use Response;

use GuzzleHttp\Exception\GuzzleException;
use Guzzle\Http\Exception\ClientErrorResponseException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GRequest;
use function GuzzleHttp\json_decode;

class EnrollmentController extends Controller
{
    //
    public function validateMember(Request $request){
        try{
            \Log::debug(
                'CALLED ROUTE '.url()->current()
            );

            $clm_server = $request->get('clm_server');

            $phone              = $request->get('phone');
            $loyaltyid          = $request->get('loyaltyid');
            $siteno             = $request->get('siteno');
            $cashier_username   = $request->get('cashier_username');
            $bearer             = $request->get('api_token');

            $json = json_encode([
                'phone'             => $phone,
                'loyaltyId'         => $loyaltyid,
                'SiteNo'            => $siteno,
                'CurrentDateTime'   => now(),
                'CashierUserName'   => $cashier_username
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
            $body->addChild('status_code', 200);
            $body->addChild('status_description', 'SUCCESS');
            $body->addChild('accountId', $response->accountId); 
            $body->addChild('loyaltyId', $response->loyaltyId); 
            $body->addChild('customerId', $response->customerId);
            $body->addChild('mainPointsBalance', $response->mainPointsBalance);
            $body->addChild('mainPointsBalanceInMoney', $response->mainPointsBalanceInMoney);
            $body->addChild('status', $response->status);
            $body->addChild('statusName', $response->statusName); 
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

        }catch (\GuzzleHttp\Exception\ServerException $e){ 
            $status_code     = $e->getResponse()->getStatusCode();
            $status_description = null;

            $response   = $e->getResponse()->getBody()->getContents();
            $res        =  json_decode($response);  

            if($status_code == 401){
                $status_description = $res->message;
            }else{
                $status_description = $res->error_code;
            }
            
            $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Header/></soapenv:Envelope>');
            //header('Content-Type: text/xml'); 
            $xml->addAttribute('version', '1.0');
            $body = $xml->addChild('soapenv:Body');  
            $body->addChild('status_code', $status_code);
            $body->addChild('status_description', $status_description);  
            $response = Response::make($xml->asXML(), 200);
            $response->header('Content-Type', 'application/xml');
            return $response; 
            
            //return $res->error_code;
        }catch(\GuzzleHttp\Exception\ClientException $e){
            $status_code        = $e->getResponse()->getStatusCode();
            $status_description = null;

            $response   = $e->getResponse()->getBody()->getContents();
            $res        =  json_decode($response); 

            if($status_code == 401){
                $status_description = $res->message;
            }
            
            $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Header/></soapenv:Envelope>');
            //header('Content-Type: text/xml'); 
            $xml->addAttribute('version', '1.0');
            $body = $xml->addChild('soapenv:Body');  
            $body->addChild('status_code',          $status_code);
            $body->addChild('status_description',   $status_description);  
            $response = Response::make($xml->asXML(), 200);
            $response->header('Content-Type', 'application/xml');
            return $response; 
        }
    }

    public function softEnrollment(Request $request){
        // "phone_number" => "1"
        // "site_no" => "2"
        // "current_date_time" => "3"
        // "cashier_user_name" => "4"
        // "business_date" => "5"
        // "enoc_brand" => "6"
        // "clm_server" => "http://enoc-s.local"
        // "api_token" => "f9jTOTJTKptpyeCxXjvY4HbN2m5BQRduOUnMVhMhXqDCNiCNFEkbEgPa7Ucc"

        
        try{
            $data = $request->except(['clm_server', 'api_token']);
            $client = new Client(); //GuzzleHttp\Client  
            $response = new GRequest(
                'POST', 
                ''.$request->get('clm_server').'/api/soft-enrollment',
                [
                    'Authorization'     => 'Bearer '.$request->get('api_token'),
                    'Accept'            => 'application/json',
                    'Content-Type'      => 'application/json'
                ],
                json_encode($data)
            );
            $response = $client->send($response);
             
            if( $response->getStatusCode() == 201 ){
                $response = $response->getBody()->getContents();
                $response = json_decode($response);

                // RETURNING A XML TO WINSERVE
                $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Header/></soapenv:Envelope>');
                //header('Content-Type: text/xml'); 
                $xml->addAttribute('version', '1.0');
                $body = $xml->addChild('soapenv:Body');  
                $body->addChild('status_code', 200);
                $body->addChild('status_description', 'SUCCESS');
                $body->addChild('Location',     $response->Location); 
                $body->addChild('LoyaltyId',    $response->LoyaltyId); 
                $body->addChild('CustomerId',   $response->CustomerId); 
                $response = Response::make($xml->asXML(), 200);
                $response->header('Content-Type', 'application/xml');
                return $response; 
            } 

        }catch (\GuzzleHttp\Exception\ServerException $e){ 
            $status_code     = $e->getResponse()->getStatusCode();
            $status_description = null;

            $response   = $e->getResponse()->getBody()->getContents();
            $res        =  json_decode($response);   
            if($status_code == 401){
                $status_description = $res->message;
            }else if($status_code === 409){
                $status_description = $res->error_code;
            }else{
                $status_description = $res->error_code;
            }
            
            $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Header/></soapenv:Envelope>');
            //header('Content-Type: text/xml'); 
            $xml->addAttribute('version', '1.0');
            $body = $xml->addChild('soapenv:Body');  
            $body->addChild('status_code', $status_code);
            $body->addChild('status_description', $status_description);  
            $response = Response::make($xml->asXML(), 200);
            $response->header('Content-Type', 'application/xml');
            return $response; 
            
            //return $res->error_code;
        }catch(\GuzzleHttp\Exception\ClientException $e){
            
            $status_code        = $e->getResponse()->getStatusCode();
            $status_description = null;

            $response   = $e->getResponse()->getBody()->getContents();
            $res        =  json_decode($response); 

            if($status_code == 401){
                $status_description = $res->message;
            }

            if($status_code === 409){
                $status_description = $res->error_code;
            }
            
            $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Header/></soapenv:Envelope>');
            //header('Content-Type: text/xml'); 
            $xml->addAttribute('version', '1.0');
            $body = $xml->addChild('soapenv:Body');  
            $body->addChild('status_code',          $status_code);
            $body->addChild('status_description',   $status_description);  
            $response = Response::make($xml->asXML(), 200);
            $response->header('Content-Type', 'application/xml');
            return $response; 
        }

    }
}
