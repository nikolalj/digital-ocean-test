@extends('layouts.app')

@section('content')
    <div class="panel panel-default">
        <div class="panel-heading text-center">
            <b>ICECAST SERVER SETUP</b>
        </div>

        <div class="panel-body">

            <div class="col-md-6 col-md-offset-3">

                <br>
                Please enter your DigitalOcean Personal Access Token. If you don't have one please create it <a href="https://cloud.digitalocean.com/settings/api/tokens" target="_blank">here</a>.
                <br>
                <br>

                <form class="form-horizontal" role="form" method="POST" action="{{ url('/setup') }}">
                    {!! csrf_field() !!}

                    <!-- Auth Key Form Input -->
                    <div class="form-group{{ $errors->has('token') ? ' has-error' : '' }}">
                        <input placeholder="token" type="text" class="form-control" name="token" value="{{ old('token') }}">

                        @if ($errors->has('token'))
                            <span class="help-block">
                                <strong>{{ $errors->first('token') }}</strong>
                            </span>
                        @endif
                    </div>

                    <!-- Setup Icecast Server -->
                    <div class="form-group">
                        <button type="submit" class="btn btn-default btn-block form-control">Setup Icecast Server</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
@stop