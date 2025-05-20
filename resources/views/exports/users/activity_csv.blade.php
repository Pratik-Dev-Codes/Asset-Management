"Date","Description","Module","Action","IP Address"
@foreach($activities as $activity)"{{ $activity->created_at->format('Y-m-d H:i:s') }}","{{ $activity->description }}","{{ $activity->log_name }}","{{ $activity->event }}","{{ $activity->properties->get('ip') ?? 'N/A' }}"
@endforeach
