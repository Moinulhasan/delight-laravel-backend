New enquiry from the {{ $company }} website
Received: {{ $received }}

@foreach ($rows as $label => $value)
{{ $label }}: {!! $value !!}
@endforeach

@foreach ($source as $label => $value)
{{ $label }}: {!! $value !!}
@endforeach
