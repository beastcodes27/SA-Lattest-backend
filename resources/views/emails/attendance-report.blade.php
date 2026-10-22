@extends('emails.layout')

@section('title', $reportTitle)
@section('preheader', $reportPeriod)
@section('badge', 'Report')

@section('content')
    <h1 style="margin:0 0 10px;font-size:22px;line-height:1.3;letter-spacing:-0.4px;color:#200F35;">{{ $reportTitle }}</h1>

    <p style="margin:0 0 4px;">Hello,</p>

    <p style="margin:0 0 4px;">Your requested attendance report is attached to this email as a PDF.</p>

    @include('emails.partials.details', ['rows' => [
        'Organization' => $organizationName,
        'Report' => $reportTitle,
        'Period' => $reportPeriod,
        'Prepared by' => $preparedBy,
        'Attachment' => $filename,
    ]])

    <p style="margin:0;font-size:13px;color:#6B7280;">You can also export reports directly from the SmartAttend app whenever you need a fresh copy.</p>
@endsection
