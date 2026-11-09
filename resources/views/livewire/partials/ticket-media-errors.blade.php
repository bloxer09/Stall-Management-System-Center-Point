<p class="text-xs text-base-content">{{ \App\Rules\TicketMedia::DESCRIPTION }}</p>
@foreach($errors->getMessages() as $field => $messages)
    @if(str_starts_with($field, 'incomingAttachments') || str_starts_with($field, 'attachments'))
        @foreach($messages as $error)
            <p role="alert" class="text-sm text-error">{{ $error }}</p>
        @endforeach
    @endif
@endforeach
