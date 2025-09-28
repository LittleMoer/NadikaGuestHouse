{{-- Helper to dynamically pick layout based on role --}}
@php
  $layout = auth()->check() && auth()->user()->isAdmin() ? 'layouts.templateadmin' : 'layouts.templateowner';
@endphp
@extends($layout)
