{{-- Form template generated by FormGenerator --}}

{{ Form::errors($errors) }}

@if (isset($model))
{{ Form::model($model, ['route' => ['admin.<?php echo $moduleName ?>.update', $model->id]<?php echo $fileHandling ?>, 'method' => 'PUT']) }}
@else
{{ Form::open(['url' => 'admin/<?php echo $moduleName ?>'<?php echo $fileHandling ?>]) }}
@endif
    <?php foreach ($fields as $field) { ?>
    <?php echo $field ?>
    <?php } ?>
    
    {{ Form::actions() }}
{{ Form::close() }}