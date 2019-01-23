@extends('errors.illustrated-layout')

@section('code', '403')
@section('title', __('Forbidden'))

@section('message')
    {{ (isset($e) ? __($e->getMessage()) : __($message)) }}
@endsection
