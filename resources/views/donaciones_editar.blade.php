@extends('layouts.app')
@section('title', 'Editar donación | Juventud Iskali')
@section('page-title', 'Editar donación')
@section('content')
@include('partials.forms.donaciones', ['modoEdicion' => true])
@endsection
