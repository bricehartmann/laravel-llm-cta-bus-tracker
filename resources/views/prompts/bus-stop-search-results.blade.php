@foreach($stops as $stop)
bus_stop_id: {{ $stop->stop_identifier }}, bus_stop_name: {{ $stop->name }}, bus_direction: {{ $stop->direction->name }}, bus_stop_position: {{ $stop->position->name }}
@endforeach
