<?php

namespace App\Http\Controllers;

use App\ShopeeUser;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ShopeeController extends Controller
{
    private $client;
    private $partner_id = 1000676;
    private $url = "https://partner.test-stable.shopeemobile.com";
    private $key = "ff9c69776a3ea6f359379b4b76dfb74802a208b9cf5d27c06cdbc51fc646b8e6";

    public function __construct()
    {
        $this->client = new Client(['base_uri' => $this->url]);
    }

    public function doAuthenticate(Request $request)
    {
        $shop_id = $request->input('shop_id');
        $code = $request->input('code');

        $path = "/api/v2/auth/token/get";
        $timestamp = Carbon::now()->timestamp;
        $signature = $this->getSignature($path, $timestamp);

        $parameter = new \stdClass();
        $parameter->code = $code;
        $parameter->partner_id = $this->partner_id;
        $parameter->shop_id = intval($shop_id);
        $json = json_encode($parameter);
        $response = $this->client->post($path, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'query' => ['partner_id' => $this->partner_id, 'timestamp' => $timestamp, 'sign' => $signature],
            'body' => $json
        ]);
        $result = $response->getBody()->getContents();
        $data = json_decode($result);
        $error = $data->error;
        $message = $data->message;

        if ($error == "") {
            $data = array();
            $data['external_id'] = "1";
            $data['shop_id'] = $shop_id;
            $data['refresh_token'] = $data->refresh_token;
            $data['access_token'] = $data->access_token;
            $data['expired_time'] = Carbon::now()->addSeconds($data->expire_in);
            ShopeeUser::create($data);
        }
    }

    public function doRefresh($refresh_token, $shop_id)
    {
        $shop_id = intval($shop_id);

        $path = "/api/v2/auth/access_token/get";
        $timestamp = Carbon::now()->timestamp;
        $signature = $this->getSignature($path, $timestamp);

        $parameter = new \stdClass();
        $parameter->refresh_token = $refresh_token;
        $parameter->partner_id = $this->partner_id;
        $parameter->shop_id = intval($shop_id);
        $json = json_encode($parameter);
        $response = $this->client->post($path, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'query' => ['partner_id' => $this->partner_id, 'timestamp' => $timestamp, 'sign' => $signature],
            'body' => $json
        ]);
        $result = $response->getBody()->getContents();
        $data = json_decode($result);

        $refresh = $data->refresh_token;
        $expired = $data->expire_in;
        $token = $data->access_token;
        $error = $data->error;
        $message = $data->message;
        echo 'mantap';
        dd($token . ' = ' . $shop_id);
    }

    function getOrderList()
    {
        $token = "456a6f6c5042526f565746746e7a6b6b";
        $shop = "7229";
        $path = "/api/v2/order/get_order_list";
        $timestamp = Carbon::now()->timestamp;
        $timestamp2 = Carbon::now()->addDays(1)->timestamp;
        $signature = $this->getSignature2($path, $timestamp, $token, $shop);

        $result = $this->client->get($path, [
            'query' => ['time_range_field' => 'create_time', 'time_from' => $timestamp, 'time_to' => $timestamp2,
                'page_size' => 20, 'order_status' => 'READY_TO_SHIP', 'response_optional_fields' => 'order_status',
                'shop_id' => $shop, 'access_token' => $token,
                'partner_id' => $this->partner_id, 'timestamp' => $timestamp, 'sign' => $signature, 'redirect' => 'http://127.0.0.1:8000/api/authenticate'],
        ])->getBody()->getContents();
        dd($result);
    }


    public static function createURL(?string $scheme, ?string $authority, string $path, ?string $query, ?string $fragment): string
    {
        $uri = '';

        // weak type checks to also accept null until we can add scalar type hints
        if ($scheme != '') {
            $uri .= $scheme . ':';
        }

        if ($authority != '' || $scheme === 'file') {
            $uri .= '//' . $authority;
        }

        $uri .= $path;

        if ($query != '') {
            $uri .= '?' . $query;
        }

        if ($fragment != '') {
            $uri .= '#' . $fragment;
        }

        return $uri;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function getSignature($path, $timestamp): string
    {

        $baseSign = $this->partner_id . $path . $timestamp;
        return hash_hmac('sha256', $baseSign, $this->key, false);
    }

    function getSignature2($path, $timestamp, $token, $shop): string
    {

        $baseSign = $this->partner_id . $path . $timestamp . $token . $shop;
        return hash_hmac('sha256', $baseSign, $this->key, false);
    }

    public function index()
    {

        $path = "/api/v2/shop/auth_partner";
        $timestamp = Carbon::now()->timestamp;
        $signature = $this->getSignature($path, $timestamp);

        $this->client->get($path, [
            'query' => ['partner_id' => $this->partner_id, 'timestamp' => $timestamp, 'sign' => $signature, 'redirect' => 'http://127.0.0.1:8000/api/authenticate'],
            'on_stats' => function (TransferStats $stats) use (&$finalUrl) {
                $url = $stats->getEffectiveUri();
                $finalUrl = self::createURL($url->getScheme(), $url->getAuthority(), $url->getPath(), $url->getQuery(), $url->getFragment());
            }
        ])->getBody()->getContents();
        return Redirect::to($finalUrl);
//        $content = $response->getBody()->getContents();
//        dd($content);
    }
}
