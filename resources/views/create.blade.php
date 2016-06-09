@extends('layouts.app')

@section('content')
    <div class="panel panel-default">
        <div class="panel-heading text-center">
            <b>ICECAST SERVER SETUP</b>
        </div>

        <div class="panel-body">

            <div class="col-md-6 col-md-offset-3">

                <br>
                Account Connected! Please provide your stream password.
                <br>
                <br>

                <form class="form-horizontal" role="form" method="POST" action="{{ url('/create') }}">
                    {!! csrf_field() !!}

                    <!-- Stream Password Form Input -->
                    <div class="form-group{{ $errors->has('token') ? ' has-error' : '' }}">
                        <input placeholder="Stream Password" type="text" class="form-control" name="streampass" value="{{ old('streampass') }}">

                        @if ($errors->has('streampass'))
                            <span class="help-block">
                                <strong>{{ $errors->first('streampass') }}</strong>
                            </span>
                        @endif
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-default btn-block form-control">Setup Icecast Server</button>
                    </div>
                </form>

            </div>

        </div>
    </div>
@stop

@section('scripts')
    <script type="text/javascript">

        //disable button on submit
        $('form').submit(function(){
            $(this).find(':submit').attr('disabled','disabled');
        });

    </script>
@stop
