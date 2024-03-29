@extends('layouts.app')

@section('content')
<div cass="section__content section__content--p30">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="table-responsive table--no-card m-b-30">
                    <table class="table table-borderless table-striped table-earning">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Content</th>
                                <th>Created At</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($articles as $article)
                                <tr>
                                    <td>{{$article->title}}</td>
                                    <td>{{$article->content}}</td>
                                    <td>{{$article->created_at}}</td>
                                    <td>
                                    	<div class="table-data-feature">
                                    		<a class="item" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit" href="{{route('article.edit',['id'=>$article->id])}}">
                                                <i class="zmdi zmdi-edit"></i>
                                            </a>
                                            <a class="item delete" data-toggle="tooltip" data-placement="top" title="" data-original-title="Edit" href="{{route('article.delete',['id'=>$article->id])}}">
                                                <i class="zmdi zmdi-delete"></i>
                                            </a>
                                    	</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection