@extends('layouts.app')
@section('title', 'Nuevo voluntario | Juventud Iskali')
@section('page-title', 'Nuevo voluntario')
@section('content')
@include('partials.forms.voluntarios', ['modoEdicion' => false])
@endsection
