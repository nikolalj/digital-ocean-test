<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DigitalOceanV2\Adapter\GuzzleHttpAdapter;
use DigitalOceanV2\DigitalOceanV2;
use App\Http\Requests;

class DigitalOceanController extends Controller
{

    /**
     * Setup a new Droplet
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function setup(Request $request)
    {
        $this->validate($request, [
            'token' => 'required'
        ]);

        $token = $request->get('token');
        $droplet = $this->createDroplet($token);

        if( ! empty($droplet))
        {
            session()->put('token',$token);
        }
        else
        {
            abort(400,'Droplet setup failed!');
        }

        //wait for the droplet to be created
        sleep(20);
        return redirect('stream/'.$droplet->id);
    }

    /**
     * Show the Stream link for the Droplet with the specified id
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function stream($id)
    {
        // check if token exists
        if( ! session()->has('token'))
        {
            abort(401,'Token not found');
        }

        // create an adapter with the access token
        $adapter = new GuzzleHttpAdapter(session('token'));

        // create a digital ocean object with the previous adapter
        $digitalocean = new DigitalOceanV2($adapter);

        $droplet = $digitalocean->droplet()->getById($id);
        return view('stream', compact('droplet'));
    }

    /**
     * Creates a new Droplet using the user's Personal Access Token
     *
     * @param $token
     * @return \DigitalOceanV2\Entity\Droplet|null
     */
    private function createDroplet($token)
    {
        // create an adapter with the access token
        $adapter = new GuzzleHttpAdapter($token);

        // create a digital ocean object with the previous adapter
        $digitalocean = new DigitalOceanV2($adapter);

        // droplet settings
        $names = 'icecast';
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
                    - wget -q http://r.creek.fm/icecast-server/install.sh -O icecast-install.sh; bash icecast-install.sh -p streampass';

        // create a droplet
        $droplet = $digitalocean->droplet()->create($names, $region, $size, $image, $backups, $ipv6, $privateNetworking, $sshKeys, $userData);

        return $droplet;
    }
}
