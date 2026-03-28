@extends('layouts.admin')
@section('title', 'Bingo - ' . $table->name)
@section('content')
<script>
window.__bingo = {
    tableId:   {{ $table->id }},
    tableName: {!! json_encode($table->name) !!},
    entryFee:  {{ $table->entry_fee }},
    userId:    {{ $userId }},
    userName:  {!! json_encode($userName) !!},
    routes: {
        back:  '{{ route('entertainment.bingo') }}',
        join:  '{{ route('entertainment.bingo.join', $table) }}',
        leave: '{{ route('entertainment.bingo.leave', $table) }}',
        ready: '{{ route('entertainment.bingo.ready', $table) }}',
        state: '{{ route('entertainment.bingo.state', $table) }}',
    },
    csrf: '{{ csrf_token() }}',
};
</script>
<bingo-room
    :table-id="window.__bingo.tableId"
    :table-name="window.__bingo.tableName"
    :entry-fee="window.__bingo.entryFee"
    :user-id="window.__bingo.userId"
    :user-name="window.__bingo.userName"
    :routes="window.__bingo.routes"
    :csrf="window.__bingo.csrf"
></bingo-room>
@endsection
