@extends('layouts.app')
@section('title', 'Editar voluntario | Juventud Iskali')
@section('page-title', 'Editar voluntario')
@section('content')
@include('partials.forms.voluntarios', ['modoEdicion' => true])
@endsection
