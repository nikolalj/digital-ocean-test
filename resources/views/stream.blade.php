@extends('layouts.app')

@section('content')
    <div class="panel panel-default">
        <div class="panel-heading text-center">
            <b>Icecast Server succcesfully created</b>
        </div>

        <div class="panel-body">
            <br>
            <div class="col-md-12">
                Stream is available at <a href="http://{{ $droplet->networks[0]->ipAddress }}:8000/stream" target="_blank">http://{{ $droplet->networks[0]->ipAddress }}:8000/stream</a>
                (if the link doesn't open, please wait a minute and try again).

                <br>
                Stream password: <b>{{ $streampass }}</b>
            </div>

        </div>
    </div>
@stop