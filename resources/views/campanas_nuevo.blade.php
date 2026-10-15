@extends('layouts.app')
@section('title', 'Nuevo campaña | Juventud Iskali')
@section('page-title', 'Nuevo campaña')
@section('content')
@include('partials.forms.campanas', ['modoEdicion' => false])
@endsection
