@extends('layouts.app')
@section('title', 'Editar entrega | Juventud Iskali')
@section('page-title', 'Editar entrega')
@section('content')
@include('partials.forms.entregas', ['modoEdicion' => true])
@endsection
