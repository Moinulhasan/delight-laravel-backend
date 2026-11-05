<div class="flex w-full shrink-0 flex-wrap items-center justify-between gap-2 sm:w-auto sm:justify-end">
    <span @class(['badge', 'badge-live' => $active])>{{ $active ? 'Live' : 'Hidden' }}</span>
    <button type="button" data-action="toggle" class="btn-ghost">{{ $active ? 'Deactivate' : 'Activate' }}</button>
    <button type="button" data-action="edit" class="btn-ghost">Edit</button>
    <button type="button" data-action="delete" class="btn-ghost btn-danger">Delete</button>
</div>
