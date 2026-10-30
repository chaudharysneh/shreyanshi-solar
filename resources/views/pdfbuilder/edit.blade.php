@extends('layouts.app')

@section('page_title', 'PDF Builder - Edit Template')

@section('content')
<div class="container-fluid p-0">
    @include('pdfbuilder.partials.form-partial', [
        'action' => route('pdfbuilder.update', $template->id),
        'template' => $template,
        'edit_mode' => true,
        'before_blocks' => $before_blocks,
        'after_blocks' => $after_blocks
    ])
</div>
@endsection
