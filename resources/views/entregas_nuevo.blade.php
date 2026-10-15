@extends('layouts.app')
@section('title', 'Nuevo entrega | Juventud Iskali')
@section('page-title', 'Nuevo entrega')
@section('content')
@include('partials.forms.entregas', ['modoEdicion' => false])
@endsection
