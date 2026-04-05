@extends('layouts.auth')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>
                <div class="card-header">                 
                    <form method='POST' action="/posts/">
                        @csrf
                        <label  for ="title"> Title </label >
                        <input type ="text" name="title"><br>
                        <label  for ="content"> Content </label >
                        <input type ="text" name="content"><br>
                        <input type ="hidden" name="user_id" value=0>
                        @method('POST')
                        <button type="submit" class="btn btn-danger float-right">
                            Add Post
                        </button>
                    </form>   
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</div>
@endsection