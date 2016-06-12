<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Log;

class DigitalOceanController extends Controller
{

    /**
     * Redirect the user to the DigitalOcean authentication page.
     *
     * @return Response
     */
    public function redirectToProvider()
    {
        // request access code
        $url = 'https://cloud.digitalocean.com/v1/oauth/authorize?response_type=code&client_id='
            . env('DIGITALOCEAN_KEY') . '&redirect_uri=' . env('DIGITALOCEAN_REDIRECT_URI') . '&scope=read+write';

        return redirect($url);
    }

    /**
     * Obtain the user information from DigitalOcean.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function handleProviderCallback(Request $request)
    {
        if( ! $request->has('code'))
        {
            return redirect('/');
        }

        // get the code
        $code = $request->get('code');
        session()->put('code', $code);

        // request access token
        try {
            $token = $this->getAccessToken();
        } catch (ClientException $e) {
            Log::info($this->readTitle($e->getResponse()->getBody()->getContents()));
            session()->flash('error-message','Problem in communication with DigitalOcean. Please try again.');
            return redirect('/');
        }

        if(empty($token))
        {
            return redirect('/');
        }

        session()->put('token', $token);
        return view('create');
    }

    /**
     * Creates a new Droplet using the user's Access Token and Stream Password
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(Request $request)
    {
        $this->validate($request,[
            'streampass' => 'required',
        ]);

        // if there is no token redirect to home page
        if( ! session()->has('token'))
        {
            return redirect('/');
        }

        $streampass = $request->get('streampass');

        //create a new droplet
        $droplet = $this->createDroplet($streampass);

        if(empty($droplet))
        {
            sleep(10);
            Log::info('Droplet creation failed! Trying one more time...');
            $droplet = $this->createDroplet($streampass);
        }

        if(empty($droplet))
        {
            session()->flash('error-message','Droplet creation failed! Please try again.');
            return redirect('/');
        }

        //wait white droplet is created and IP address is assigned to it
        while( ! count($droplet['networks']['v4']))
        {
            sleep(10);
            $droplet = $this->getDropletById($droplet['id']);
        }

        $ipAddress = $droplet['networks']['v4'][0]['ip_address'];
        return view('stream', compact('ipAddress','streampass'));
    }

    /**
     * Create a new Droplet
     *
     * @param $streampass
     * @return mixed|null
     */
    private function createDroplet($streampass)
    {
        try {

            //find the max id of the icecast droplets
            $droplets = $this->getAllDroplets();

            $maxId=0;
            foreach($droplets as $droplet)
            {
                if(strpos($droplet['name'], 'creek-icecast') !== FALSE)
                {
                    $nameArray = explode('-',$droplet['name']);
                    if(count($nameArray)>2 && $nameArray[2]>$maxId)
                    {
                        $maxId = $nameArray[2];
                    }
                }
            }
            $maxId++;

            // droplet settings
            $names = 'creek-icecast-' . $maxId;
            $region = 'nyc1';
            $size = '512mb';
            $image = 'ubuntu-14-04-x64';
            $backups = false;
            $ipv6 = false;
            $privateNetworking = false;
            $sshKeys = [];
            $userData = '#cloud-config
                    runcmd:
                    - apt-get -y install wget
                    - wget -q http://r.creek.fm/icecast-server/install.sh -O icecast-install.sh; bash icecast-install.sh -p ' . $streampass;

            // create a droplet
            $droplet = $this->createNewDroplet($names, $region, $size, $image, $backups, $ipv6, $privateNetworking, $sshKeys, $userData);
            return $droplet;

        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return null;
        }

    }

    /**
     * Get the Access Token after the code has been obtained
     *
     * @return bool
     */
    public function getAccessToken()
    {
        $client = new Client(['base_uri' => 'https://cloud.digitalocean.com/v1/oauth/']);

        $response = $client->request('POST', 'token', [
            'form_params' => [
                'client_id' => env('DIGITALOCEAN_KEY'),
                'client_secret' => env('DIGITALOCEAN_SECRET'),
                'code' => session('code'),
                'grant_type' => 'authorization_code',
                'redirect_uri' => env('DIGITALOCEAN_REDIRECT_URI'),
            ]
        ]);

        $jsonResponse = json_decode($response->getBody()->getContents(), true);

        if( ! isset($jsonResponse['access_token']))
        {
            Log::info($response->getBody()->getContents());
            return null;
        }

        return $jsonResponse['access_token'];
    }

    /**
     * Get Droplet by id from Digital Ocean
     *
     * @param $id
     * @return mixed
     */
    private function getDropletById($id)
    {
        $token = session('token');
        $client = new Client(['base_uri' => 'https://api.digitalocean.com/v2/']);

        $response = $client->request('GET', 'droplets/' . $id, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $jsonResponse = json_decode($response->getBody()->getContents(), true);
        return $jsonResponse['droplet'];
    }

    /**
     * Get all Droplets from Digital Ocean
     *
     * @return bool
     */
    private function getAllDroplets()
    {
        $token = session('token');
        $client = new Client(['base_uri' => 'https://api.digitalocean.com/v2/']);

        $response = $client->request('GET', 'droplets', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $jsonResponse = json_decode($response->getBody()->getContents(), true);
        return $jsonResponse['droplets'];
    }

    /**
     * Create a new Droplet on Digital Ocean
     *
     * @param $name
     * @param $region
     * @param $size
     * @param $image
     * @param $backups
     * @param $ipv6
     * @param $privateNetworking
     * @param $sshKeys
     * @param $userData
     * @return mixed
     */
    private function createNewDroplet($name, $region, $size, $image, $backups, $ipv6, $privateNetworking, $sshKeys, $userData)
    {
        $token = session('token');
        $client = new Client(['base_uri' => 'https://api.digitalocean.com/v2/']);

        $response = $client->request('POST', 'droplets', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'form_params' => [
                'name' => $name,
                'region' => $region,
                'size' => $size,
                'image' => $image,
                'backups' => $backups,
                'ipv6' => $ipv6,
                'private_networking' => $privateNetworking,
                'ssh_keys' => $sshKeys,
                'user_data' => $userData,
            ],
        ]);

        $jsonResponse = json_decode($response->getBody()->getContents(), true);
        return $jsonResponse['droplet'];
    }

    /**
     * Extracts the title in the HTML response. If no
     * title found return the response back
     *
     * @param $response
     * @return string
     */
    private function readTitle($response)
    {
        $startsAt = strpos($response, "<title>") + strlen("<title>");

        if($startsAt == false)
        {
            return $response;
        }

        $endsAt = strpos($response, "</title>", $startsAt);

        if($endsAt == false)
        {
            return $response;
        }

        $result = substr($response, $startsAt, $endsAt - $startsAt);

        if($result == false)
        {
            return $response;
        }

        return $result;
    }
}