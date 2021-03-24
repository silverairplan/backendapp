@extends('layouts.app')

@section('content')
	<div cass="section__content section__content--p30">
	    <div class="container-fluid">
	        <div class="row">
	            <div class="col-lg-12">
	            	<div class="card">
	            		<div class="card-header">Article</div>
	            		<div class="card-body">
	            			<form action="{{route('article')}}" method="post">
	            				<div class="form-group">
	            					<label for="title" class="control-label mb-1">Title</label>
	            					<input type="text" name="title" id="title" class="form-control" required/>
	            				</div>
	            				<div class="form-group">
	            					<label for="content" class="control-label mb-1">Content</label>
	            					<textarea class="form-control" name="content" id="content" required=""></textarea>
	            				</div>
	            				<div>
	            					<a href="{{route('articles')}}" class="btn btn-secondary">Cancel</a>
	            					<button type="submit" class="btn btn-primary">Submit</button>
	            				</div>
	            			</form>
	            		</div>
	            	</div>
	            </div>
	        </div>
	    </div>
	</div>
@endsection