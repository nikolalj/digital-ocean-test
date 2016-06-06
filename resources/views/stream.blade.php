@extends('layouts.app')

@section('content')
    <div class="panel panel-default">
        <div class="panel-heading text-center">
            <b>{{ ! empty($droplet) ? 'Icecast Server succcesfully created' : 'Icecast Server setup failed' }}</b>
        </div>

        <div class="panel-body">
            <br>
            @if( ! empty($droplet))
                <div class="col-md-12">
                    @if(count($droplet->networks))
                        Stream is available at <a href="http://{{ $droplet->networks[0]->ipAddress }}:8000/stream" target="_blank">http://{{ $droplet->networks[0]->ipAddress }}:8000/stream</a>
                        (if the link doesn't open, please wait a minute and try again).
                    @else
                        Ip Address still not assigned. Please wait 20 seconds and refresh the page!
                    @endif

                    <br>
                    Stream password: <b>streampass</b>
                </div>
            @else
                Please go to <a href="/">setup page</a>
            @endif

        </div>
    </div>
@stop