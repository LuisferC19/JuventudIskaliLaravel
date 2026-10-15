@extends('layouts.app')
@section('title', 'Editar campaña | Juventud Iskali')
@section('page-title', 'Editar campaña')
@section('content')
@include('partials.forms.campanas', ['modoEdicion' => true])
@endsection
