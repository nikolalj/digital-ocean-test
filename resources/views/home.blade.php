@extends('layouts.app')

@section('content')
    <div class="panel panel-default">
        <div class="panel-heading text-center">
            <b>ICECAST SERVER SETUP</b>
        </div>

        <div class="panel-body">

            <div class="row text-center">

                <br>
                @if(session()->has('error-message'))
                    {{ session('error-message') }}
                @else
                    Please Authorize the Application to use your Digital Ocean Account!
                @endif
                <br>
                <br>
                <a href="digitalocean" class="btn btn-warning" type="submit">Authorize</a>

            </div>

        </div>
    </div>
@stop