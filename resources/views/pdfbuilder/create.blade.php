@extends('layouts.app')

@section('page_title', 'PDF Builder - Create Template')

@section('content')
<div class="container-fluid p-0">
    @include('pdfbuilder.partials.form-partial', [
        'action' => route('pdfbuilder.generate'),
        'template' => null
    ])
</div>
@endsection
