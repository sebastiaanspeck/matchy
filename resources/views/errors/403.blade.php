@extends('errors.illustrated-layout')

@section('code', '403')
@section('title', __('Forbidden'))

@section('message', __($e->getMessage()))
