<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use DigitalOceanV2\Adapter\GuzzleHttpAdapter;
use DigitalOceanV2\DigitalOceanV2;
use App\Http\Requests;

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
            dd($e->getMessage());
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
        $token = session('token');

        //create a new droplet
        $droplet = $this->createDroplet($token, $streampass);

        if(empty($droplet))
        {
            sleep(10);
            \Log::info('Droplet creation failed! Trying one more time...');
            $droplet = $this->createDroplet($token, $streampass);
        }

        if(empty($droplet))
        {
            session()->flash('error-message','Droplet creation failed! Please try again.');
            return redirect('/');
        }

        //wait white droplet is created and IP address is assigned to it
        while( ! count($droplet->networks))
        {
            sleep(10);
            $droplet = $this->getDropletById($droplet->id);
        }

        return view('stream', compact('droplet','streampass'));
    }

    /**
     * Creates a new Droplet
     *
     * @param $token
     * @param $streampass
     * @return \DigitalOceanV2\Entity\Droplet|null
     */
    private function createDroplet($token, $streampass)
    {
        // create an adapter with the access token
        $adapter = new GuzzleHttpAdapter($token);

        // create a digital ocean object with the previous adapter
        $digitalocean = new DigitalOceanV2($adapter);

        try {

            //find the max id of the icecast droplets
            $droplets = $digitalocean->droplet()->getAll();

            $maxId=0;
            foreach($droplets as $droplet)
            {
                if(strpos($droplet->name, 'creek-icecast') !== FALSE)
                {
                    $nameArray = explode('-',$droplet->name);
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
            $droplet = $digitalocean->droplet()->create($names, $region, $size, $image, $backups, $ipv6, $privateNetworking, $sshKeys, $userData);

            return $droplet;

        } catch (\Exception $e) {
            return null;
        }

    }

    /**
     * Get Droplet by id
     *
     * @param $id
     * @return \DigitalOceanV2\Entity\Droplet|null
     */
    private function getDropletById($id)
    {
        $token = session('token');

        // create an adapter with the access token
        $adapter = new GuzzleHttpAdapter($token);

        // create a digital ocean object with the previous adapter
        $digitalocean = new DigitalOceanV2($adapter);

        return $digitalocean->droplet()->getById($id);
    }

    /**
     * Get the Access Token after the code has been obtained
     *
     * @return bool
     */
    public function getAccessToken()
    {
        $client = new Client();
        $response = $client->request('POST', 'https://cloud.digitalocean.com/v1/oauth/token', [
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
            dd($response->getBody()->getContents());
        }

        return $jsonResponse['access_token'];
    }

}