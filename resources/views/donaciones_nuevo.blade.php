@extends('layouts.app')
@section('title', 'Nuevo donación | Juventud Iskali')
@section('page-title', 'Nuevo donación')
@section('content')
@include('partials.forms.donaciones', ['modoEdicion' => false])
@endsection
